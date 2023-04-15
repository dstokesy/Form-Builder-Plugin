<?php namespace Dstokesy\Forms\Controllers;

use Event;
use Yaml;
use Queue;
use BackendMenu;
use Backend\Classes\Controller;
use Carbon\Carbon;

/**
 * Entries Back-end Controller
 */
class Entries extends Controller
{

    public function __construct()
    {
        parent::__construct();

        if (input('form_id')) {
            BackendMenu::setContext('Dstokesy.Forms', 'forms', 'entries_form_' . input('form_id'));
        } else {
            BackendMenu::setContext('Dstokesy.Forms', 'forms', 'Entries');
        }

        Event::fire('dstokesy.forms.entries.afterConstruct', [$this, $this->action]);
    }

    public function listExtendQuery($query)
    {
        // if we have a form_id in query string then filter by form
        if ($form_id = input('form_id')) {
            $query->where('form_id', $form_id);
            Event::fire('dstokesy.forms.beforeListExtendQuery', [$this, $query, $form_id]);
        }
    }

    public function listFilterExtendScopes($filter)
    {
        // if we are already filtering by form_id then remove form filter
        if (input('form_id')) {
            $filter->removeScope('form');
        }
    }

    public function formExtendModel($model)
    {
        $model->is_read = 1;
        $model->save();
    }

    public function formBeforeSave($model)
    {
        if ($model->dealt_with == 0 && post('Entry')['dealt_with'] == 1) {
            $model->dealt_with_backend_user_id = $this->user->id;
            $model->dealt_with_at = Carbon::now();
        }

        if(post('mark_as_unread') == 1) {
            $model->is_read = NULL;
        } else {
            $model->is_read = 1;
        }

        parent::formBeforeSave($model);
    }

    public function onMarkAsRead()
    {
        $column = 'is_read';

        $this->getModelClass()->whereIn('id', $this->getCheckedIds())->update(['is_read' => 1]);

        if ($this->hasNestedSetup())
        {
            $this->getModelClass()->whereIn('parent_id', $this->getCheckedIds())->update(['is_read' => 1]);
        }

        return $this->listRefresh();
    }

    public function onMarkAsUnRead()
    {
        $column = 'is_read';

        $this->getModelClass()->whereIn('id', $this->getCheckedIds())->update(['is_read' => 0]);

        if ($this->hasNestedSetup())
        {
            $this->getModelClass()->whereIn('parent_id', $this->getCheckedIds())->update(['is_read' => 0]);
        }

        return $this->listRefresh();
    }

    /**
     * @return object
     */
    private function getModelClass()
    {
        $listConfig = $this->listGetConfig('list');
        $class = $listConfig->modelClass;
        return new $class;
    }

    /**
     * @return array
     */
    private function getCheckedIds()
    {
        if (($checkedIds = post('checked'))
            && is_array($checkedIds)
            && count($checkedIds)
        ) {
            return $checkedIds;
        }

        return [];
    }

    private function hasNestedSetup()
    {
        $traitsUsed = class_uses($this->getModelClass());
        return (bool) (in_array('October\Rain\Database\Traits\SimpleTree', $traitsUsed));
    }
}