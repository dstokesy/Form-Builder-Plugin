<?php namespace Dstokesy\Forms\Models;

use Model;
use RainLab\Pages\Classes\Page as StaticPage;
use Dstokesy\Pages\Models\Page as RejPage;
use Cms\Classes\Page as CmsPage;
use Cms\Classes\Theme;
use System\Models\MailTemplate;
use System\Classes\PluginManager;
use Event;

/**
 * Form Model
 */
class Form extends Model
{
    use \October\Rain\Database\Traits\SoftDelete;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'dstokesy_forms';

    /**
     * @var array Behaviours Implemented
     */
    public $implement = [
        '@RainLab.Translate.Behaviors.TranslatableModel',
        '@Dstokesy.Translate.Behaviors.TranslatableModel',
        '@Dstokesy.Franchises.Behaviors.FranchisableModel'
    ];

    /**
     * @var array Date fields
     */
    protected $dates = ['deleted_at'];

    /**
     * @var array Jsonable fields
     */
    protected $jsonable = ['fields', 'event_tracking'];

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Translated fields
     */
    public $translatable = ['user_email_subject', 'user_email_content'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $hasMany = [
        'entries' => [
            'Dstokesy\Forms\Models\Entry'
        ]
    ];
    public $belongsTo = [];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    public function scopeIsLive($query)
    {
        return $query->where('is_live', 1);
    }

    public function getSuccessPageOptions()
    {
        $result = [
            '' => 'None'
        ];

        $theme = Theme::getActiveTheme();

        if (PluginManager::instance()->hasPlugin('RainLab.Pages')) {
            $staticPages = StaticPage::listInTheme($theme, true);
            if ($staticPages) {
                foreach ($staticPages as $staticPage) {
                    $result[$staticPage->fileName] = $staticPage->title;
                }
            }
        }

        if (PluginManager::instance()->hasPlugin('Dstokesy.Pages')) {
            $query = RejPage::select(['id', 'title'])
            	->isNotHidden();

            Event::fire('dstokesy.forms.form.extendRejPageSuccessPageOptionsQuery', [$this, &$query]);

            $rejPages = $query->get();

            if ($rejPages) {
                foreach ($rejPages as $rejPage) {
                    $result[$rejPage->id] = $rejPage->title;
                }
            }
        }

        $cmsPages = CmsPage::listInTheme($theme, true);

        if ($cmsPages) {
            foreach ($cmsPages as $cmsPage) {
                $result[$cmsPage->fileName] = $cmsPage->title;
            }
        }

        return $result;
    }

    public function getUserSuccessEmailTemplateOptions()
    {
        $templates = MailTemplate::all();
        $result = [];

        if ($templates) {
            foreach ($templates as $template) {
                $result[$template->id] = $template->code;
            }
        }

        return $result;
    }

    public function getSubmissionsAttribute()
    {
        return count($this->entries);
    }

    public function getValidationData()
    {
        $data = [
            'rules' => [],
            'messages' => [],
        ];

        if ($fields = $this->fields) {
            $fields = collect($fields);

            $normalRequiredFields = $fields->where('_group', '!=', 'email')
                ->where('required', 1);

            if ($normalRequiredFields) {
                foreach ($normalRequiredFields as $field) {

                    $data['rules'][$field['name']] = ['required'];

                    if ($field['validation_message']) {
                        $data['messages'][$field['name'] . '.required'] = $field['validation_message'];
                    }
                }
            }

            $emailRequiredFields = $fields->where('_group', 'email')
                ->where('required', 1);

            if ($emailRequiredFields) {
                foreach ($emailRequiredFields as $field) {
                    $data['rules'][$field['name']] = ['required', 'email'];

                    if ($field['validation_message']) {
                        $data['messages'][$field['name'] . '.required'] = $field['validation_message'];
                        $data['messages'][$field['name'] . '.email'] = $field['validation_message'];
                    }
                }
            }

            $emailFields = $fields->where('_group', 'email')
                ->where('required', 0);

            if ($emailFields) {
                foreach ($emailFields as $field) {
                    $data['rules'][$field['name']] = ['email'];

                    if ($field['validation_message']) {
                        $data['messages'][$field['name'] . '.email'] = $field['validation_message'];
                    }
                }
            }

        }

        return $data;
    }

    public function getRecipientEmailField()
    {
        $fields = collect($this->fields);
        return $fields->where('response_email', 1)->first();
    }

    public function getUserSuccessEmailBccArrayAttribute()
    {
        $emailsArray = [];

        if ($this->user_email_bcc) {
			if(str_contains($this->user_email_bcc, ',')) {
				$emailString = str_replace(' ', '', $this->user_email_bcc);
				$emailsArray = explode(',', $emailString);
			} else {
				$emailsArray = [$this->user_email_bcc];
			}
        }

        return $emailsArray;
    }

    public function getAdminEmailRecipientArrayAttribute()
    {
        $emailsArray = [];

		if ($this->admin_email_recipient) {
			if(str_contains($this->admin_email_recipient, ',')) {
				$emailString = str_replace(' ', '', $this->admin_email_recipient);
				$emailsArray = explode(',', $emailString);
			} else {
				$emailsArray = [$this->admin_email_recipient];
			}
		}

        return $emailsArray;
    }

    public function getAdminEmailCcArrayAttribute()
    {
        $emailsArray = [];

		if ($this->admin_email_cc) {
			if(str_contains($this->admin_email_cc, ',')) {
				$emailString = str_replace(' ', '', $this->admin_email_cc);
				$emailsArray = explode(',', $emailString);
			} else {
				$emailsArray = [$this->admin_email_cc];
			}
		}

        return $emailsArray;
    }

    public function getAdminEmailBccArrayAttribute()
    {
        $emailsArray = [];

		if ($this->admin_email_bcc) {
			if(str_contains($this->admin_email_bcc, ',')) {
				$emailString = str_replace(' ', '', $this->admin_email_bcc);
				$emailsArray = explode(',', $emailString);
			} else {
				$emailsArray = [$this->admin_email_bcc];
			}
		}

        return $emailsArray;
    }
}
