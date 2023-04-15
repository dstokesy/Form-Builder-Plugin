<?php namespace Dstokesy\Forms\Components;

use Auth;
use Config;
use Event;
use Input;
use Mail;
use Request;
use Redirect;
use Validator;
use AjaxException;
use ApplicationException;
use ValidationException;
use Cms\Classes\ComponentBase;
use Cms\Classes\Page as CmsPage;
use System\Classes\PluginManager;
use RainLab\Pages\Classes\Page as StaticPage;
use Dstokesy\Forms\Models\Form as FormModel;
use Dstokesy\Forms\Models\Entry as EntryModel;
use Dstokesy\User\Models\Activity;
use ReCaptcha\ReCaptcha;

class Form extends ComponentBase
{
    public $result;
    public $form_id;
    public $entry;
    public $formData;
    public $formRules;
    public $formRulesMessages;
    public $recaptchaEnabled;

    public function componentDetails()
    {
        return [
            'name'        => 'Form Component',
            'description' => 'Displays a single form'
        ];
    }

    public function defineProperties()
    {
        $properties = [
            'form_id' => [
                'title'     => 'Form',
                'type'      => 'dropdown',
                'default'   => false,
            ],
            'deferLoading' => [
				'title'         => 'Defer loading',
				'type'          => 'checkbox',
			],
        ];

        Event::fire('dstokesy.forms.form.beforeDefineProperties', [$this, &$properties]);

        return $properties;
    }

    public function getForm_IdOptions()
    {
        if ($forms = FormModel::isLive()) {
            Event::fire('dstokesy.forms.beforeFormIdPropertyOptions', [$this, &$forms]);
            return $forms->lists('name', 'id');
        }
    }

    public function prepareVars()
    {
        foreach ($this->properties as $property => $value) {
            $this->$property = input($property) ? input($property) : $value;
        }

        $this->recaptchaEnabled = Config::get('dstokesy.forms::recaptcha.enabled');

        Event::fire('dstokesy.forms.form.afterPrepareVars', [$this]);

        return $this;
    }

    public function onRun()
    {
		$this->prepareVars();

    	if ($this->property('deferLoading') != 1) {
			$this->loadForm();
		}
    }

    public function loadForm()
    {
        $query = FormModel::where('id', $this->form_id)
            ->isLive();

        Event::fire('dstokesy.forms.form.beforeLoad', [$this, &$query]);

        $this->result = $query->first();

        return $this;
    }

    public function onSubmit()
    {
        $this->prepareVars();

        if(Request::ajax()) {

            try {

                if ($this->recaptchaEnabled) {
                    // run recaptcha
                    $recaptchaResponse = $this->validateReCaptcha();
                    if (! $recaptchaResponse->isSuccess()) {
                        throw new AjaxException($recaptchaResponse->getErrorCodes());
                    }
                }

                $this->entry = new EntryModel();

                $this->prepareVars()
                    ->loadForm();

                // throw error if we don't have a form
                if (!$this->result) {
                    throw new AjaxException('Form loading error');
                }

                $data = array_merge(post(), Input::allFiles());

                $this->setFormData($data)
                    ->setFormValidation();

                Event::fire('dstokesy.forms.beforeValidation', [$this]);

                $validator = Validator::make($this->formData, $this->formRules, $this->formRulesMessages);

                if ($validator->fails()) {
                    throw new ValidationException($validator);
                }

                if ($inputFiles = Input::allFiles()) {

                    foreach ($inputFiles as $file) {
                        $this->validateFile($file);
                    }

                    foreach ($inputFiles as $field_name => $file) {
                        $this->saveFile($file, $field_name);
                    }
                }

                $this->removeValidationFieldsFromFormData();

                $this->saveEntry();

                if ($this->result->user_success_email_checkbox && $this->getUserEmailRecipient()) {
                    $this->sendUserEmail();
                }

                if ($this->result->admin_success_email_checkbox) {
                    $this->sendAdminEmail();
                }

                Event::fire('dstokesy.forms.onSuccess', [$this]);

                if ($this->result->success_page) {
                    $redirectPage = $this->getRedirectSuccessPage();
                    if ($redirectPage) {
						$redirectUrl = $redirectPage->url;
						Event::fire('dstokesy.forms.onSuccess.beforeRedirect', [$this, &$redirectUrl]);
                        return Redirect::to($redirectUrl);
                    }
                }

            } catch (Exception $ex) {
                trace_log($ex);
				throw new ApplicationException('Sorry there was an error, please try again');
            }
        }
    }

    public function removeValidationFieldsFromFormData()
    {
        $validationFields = [
            '_resolution',
            '_cpt_mtime',
            '_cpt_hpot'
        ];

        if ($this->formData && $validationFields) {
            foreach ($this->formData as $field => $value) {
                if (in_array($field, $validationFields)) {
                    unset($this->formData[$field]);
                }
            }
        }

        return $this;
    }

    public function saveEntry()
    {
        $this->entry->form_id = $this->property('form_id');
        $this->entry->url = Request::fullUrl();
        $this->entry->data = $this->formData;

        Event::fire('dstokesy.forms.form.beforeSaveEntry', [$this]);

        $this->entry->save();
    }

    /**
    *   Removes Session Key and Token from Raw Form Data Submission (for storing as JSON in data field)
    */

    public function setFormData($formData)
    {
        if ($formData) {
            unset($formData['_session_key']);
            unset($formData['_token']);

            Event::fire('dstokesy.forms.form.beforeSetFormData', [$this, &$formData]);

            $this->formData = $formData;
        }

        return $this;
    }

    public function setFormValidation()
    {
        if ($this->result) {
            $validationData = $this->result->getValidationData();
            $this->formRules = $validationData['rules'];
            $this->formRulesMessages = $validationData['messages'];
        }

        return $this;
    }

    public function sendUserEmail()
    {
        $sender = [
            'email' => Config::get('mail.from.address'),
            'name'  => Config::get('mail.from.name')
        ];

        $recipient = $this->getUserEmailRecipient();

        $subject = ($this->result->user_email_subject ? $this->contentReplacer($this->result->user_email_subject) : false);

        $emailTags = [];
		$emailTags['content'] = ($this->result->user_email_content ? $this->contentReplacer($this->result->user_email_content) : false);

        $bccEmails = $this->result->user_success_email_bcc_array;

        $userTemplate = 'dstokesy.forms::user_email';

	    Event::fire('dstokesy.forms.beforeUserEmailSend', [$this, &$userTemplate, &$bccEmails, &$emailTags]);

        Mail::send($userTemplate, $emailTags, function($message) use ($recipient, $sender, $subject, $bccEmails) {
            $message->from($sender['email'], $sender['name']);
            $message->to($recipient);

            if ($subject) {
                $message->subject($subject);
            }

            if ($bccEmails) {
                $message->bcc($bccEmails, 'Forms');
            }
        });

        if (Mail::failures()) {
            throw new AjaxException('Email error');
        }

        return $this;
    }

    public function sendAdminEmail()
    {
        $sender = [
            'email' => Config::get('mail.from.address'),
            'name'  => Config::get('mail.from.name')
        ];

        $recipient = $this->result->admin_email_recipient_array;

        $subject = ($this->result->admin_email_subject ? $this->contentReplacer($this->result->admin_email_subject) : false);

        $ccEmails = $this->result->admin_email_cc_array;
        $bccEmails = $this->result->admin_email_bcc_array;

        $adminTemplate = 'dstokesy.forms::admin_email';

        if ($this->result->admin_email_link_only == 1) {
            $emailContent = $this->entry->backend_url;
        } else {
            $emailContent = 'Page: ' . "\n" . $this->entry->url . "\n" . "\n";
            $emailContent .= $this->entry->getSubmissionAttribute();
        }


        $emailTags = ['content' => $emailContent];

        Event::fire('dstokesy.forms.beforeAdminEmailSend', [$this, &$adminTemplate, &$recipient, &$emailTags, &$subject, &$ccEmails, &$bccEmails]);

        if (!empty($recipient)) {

            Mail::send($adminTemplate, $emailTags, function($message) use ($recipient, $sender, $subject, $ccEmails, $bccEmails) {
                $message->from($sender['email'], $sender['name']);
                $message->to($recipient);

                if ($subject) {
                    $message->subject($subject);
                }

                if ($ccEmails) {
                    $message->cc($ccEmails);
                }

                if ($bccEmails) {
                    $message->bcc($bccEmails, 'Forms');
                }
            });

            if (Mail::failures()) {
                throw new AjaxException('Email error');
            }
        }


        return $this;
    }

    public function contentReplacer($content)
    {
    	$entryData = $this->entry->data;

		if ($entryData) {
			foreach ($entryData as $key => $entryItem) {
				if (is_array($entryItem)) {
					unset($entryData[$key]);
				}
			}
		}

        $content = str_replace($this->getEmailVariables(), $entryData, $content);

        return $content;
    }

    public function getUserEmailRecipient()
    {
        $responseEmail = false;

        if ($this->result) {
            if ($responseEmailField = $this->result->getRecipientEmailField()) {
                $responseEmail = ($this->entry->data[$responseEmailField['name']] ?: false);
            }
        }

        return $responseEmail;
    }

    public function getEmailVariables()
    {
        $keys = [];

        if ($this->entry->data) {
            foreach ($this->entry->data as $key => $value) {
            	if (!is_array($value)) {
                	$keys[] = '{{ '.$key.' }}';
                }
            }
        }

        return $keys;
    }

    public function getRedirectSuccessPage()
    {
    	$page = false;

    	if (PluginManager::instance()->hasPlugin('RainLab.Pages')) {
        	$page = StaticPage::find($this->result->success_page);
        }

        if (!$page) {
            $page = CmsPage::find($this->result->success_page);
        }

        return $page;
    }

    /**
     * Validate Recaptcha
     * @return (array) Recaptcha response
     */
    public function validateReCaptcha()
    {
        $recaptcha = new ReCaptcha(Config::get('dstokesy.forms::recaptcha.secretKey'));
        $response = $recaptcha->verify(post('reCaptcha'), Request::ip());

        return $response;
    }

    public function validateFile($file)
    {
        $data['document'] = $file;
        $rules['document'] = 'max:5000|mimes:jpeg,png,pdf,docx,doc,mp4,mpeg4,webm,ogv,mts';

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    public function saveFile($file, $field_name)
    {
        $systemFile                     = new \System\Models\File;
        $systemFile->data               = $file;
        $systemFile->is_public          = true;
        $systemFile->attachment_type    = 'Dstokesy\Forms\Models\Entry';
        $systemFile->field              = $field_name;
        $systemFile->save();

        $this->formData[$field_name]    = $systemFile->id;
    }
}
