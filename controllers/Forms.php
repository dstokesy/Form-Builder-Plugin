<?php namespace Dstokesy\Forms\Controllers;

use BackendMenu;
use Dstokesy\Boilerplate\Controllers\RejController;
use Dstokesy\Forms\Models\Form;
use Redirect;
use Backend;
use Event;

/**
 * Forms Back-end Controller
 */
class Forms extends RejController
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController',
        'Dstokesy.Behaviors.ToolbarButtonsController',
    ];

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Dstokesy.Forms', 'forms', 'forms');

        $this->restoreModel = Form::class;
		$this->restoreField = 'name';
    }

    public function formAfterCreate($model)
    {
        parent::formAfterCreate($model);
    }

    public function formAfterDelete($model)
    {
        parent::formAfterDelete($model);
    }

    public function formAfterSave($model)
    {
    	//$this->saveRedirect($model);
    }

    public function formAfterUpdate($model)
    {
        parent::formAfterUpdate($model);
    }

    public function formBeforeCreate($model)
    {}

    public function formBeforeSave($model)
    {
    	//$this->saveMetaInfo($model);
        //$this->redirectPrepareData($model);
        parent::formBeforeSave($model);
    }

    public function formBeforeUpdate($model)
    {}

    public function onDuplicate()
    {
        $form = Form::find($this->params[0]);

        if ($form) {
            $newForm = $form->replicate();
            $newForm->name = $newForm->name . ' - copy';
            $newForm->save();

            Event::fire('dstokesy.forms.forms.afterDuplicate', [$this, &$newForm]);

            return Redirect::to(Backend::url('dstokesy/forms/forms/update/' . $newForm->id));
        }
    }
}
