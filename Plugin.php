<?php namespace Dstokesy\Forms;

use Backend;
use System\Classes\PluginBase;
use Event;
use Dstokesy\Forms\Models\Form;
use Dstokesy\Forms\Models\Entry;
use View;

/**
 * Forms Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * @var array Plugin dependencies
     */
    public $require = [
        'Dstokesy.Boilerplate'
    ];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Forms',
            'description' => 'Create forms',
            'author'      => 'Dstokesy',
            'icon'        => 'icon-check-square-o'
        ];
    }

    /**
     * Register method, called when the plugin is first registered.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {
        Event::listen('backend.page.beforeDisplay', function($controller, $action, $params) {
            $controller->addJs('/plugins/dstokesy/forms/assets/js/backend.js');
        });

        $this->addFormsToBackendNavigation();
        $this->extendEntriesBackendForm();
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return [
            'Dstokesy\Forms\Components\Form' => 'form',
        ];
    }

    public function registerPageSnippets()
    {
        return [
            'Dstokesy\Forms\Components\Form' => 'form',
        ];
    }

    public function registerMailTemplates()
    {
        return [
            'dstokesy.forms::user_email' => 'Users Email response template',
            'dstokesy.forms::admin_email' => 'Admin Email response template',
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return [
            'dstokesy.forms.manage_forms' => [
                'tab' => 'Forms',
                'label' => 'Manage Forms',
                'roles' => ['developer']
            ],
            'dstokesy.forms.access_entries' => [
                'tab' => 'Forms',
                'label' => 'Access Entries'
            ],
        ];
    }

    /**
     * Registers back-end navigation items for this plugin.
     *
     * @return array
     */
    public function registerNavigation()
    {
        $entries = Entry::isUnRead();

        Event::fire('dstokesy.forms.beforeRegisterNavigationUnReadCount', [$this, &$entries]);
        
        $unReadCount = $entries->count();

        return [
            'forms' => [
                'label'       => 'Forms',
                'count'       => $unReadCount,
                'url'         => Backend::url('dstokesy/forms/forms'),
                'icon'        => 'icon-check-square-o',
                'permissions' => ['dstokesy.forms.*'],
                'order'       => 500,

                'sideMenu' => [
                    'forms' => [
                        'label'       => 'Forms',
                        'icon'        => 'icon-check-square-o',
                        'url'         => Backend::url('dstokesy/forms/forms'),
                        'permissions' => ['dstokesy.forms.manage_forms']
                    ],
                    'Entries' => [
                        'label'       => 'Entries',
                        'count'       => $unReadCount,
                        'icon'        => 'icon-copy',
                        'url'         => Backend::url('dstokesy/forms/entries'),
                        'permissions' => ['dstokesy.forms.access_entries']
                    ],
                ]
            ],
        ];
    }

    public function registerListColumnTypes()
    {
        return [
            'isread' => function($value) {
                return View::make('dstokesy.forms::readColumn', [
                    'value' => (int) $value
                ]);
            }
        ];
    }

    public function registerReportWidgets()
    {
        return [
            'Dstokesy\Forms\ReportWidgets\Entries'=>[
                'label'   => 'Entry Notifications',
                'context' => 'dashboard'
            ],
        ];
    }

    public function addFormsToBackendNavigation()
    {
        Event::listen('backend.menu.extendItems', function($manager) {
            $menuItems = [];

            if ($forms = Form::all()) {

                Event::fire('dstokesy.forms.beforeAddFormsToBackendNavigation', [$this, &$forms]);

                foreach ($forms as $form) {
                    $unReadCount = $form->entries()->isUnRead()->count();

                    $menuItems['entries_form_' . $form->id] = [
                        'label'       => $form->name . ' Entries',
                        'count'       => $unReadCount,
                        'icon'        => 'icon-copy',
                        'url'         => Backend::url('dstokesy/forms/entries') . '?form_id=' . $form->id,
                        'permissions' => ['dstokesy.forms.access_entries']
                    ];
                }
            }

            $manager->addSideMenuItems('Dstokesy.Forms', 'Forms', $menuItems);
        });
    }

    public function extendEntriesBackendForm()
    {
        Event::listen('backend.form.extendFieldsBefore', function($widget) {
            $model = $widget->model;

            if ($model instanceof \Dstokesy\Forms\Models\Entry) {
                if ($model->dealt_with == 1 && $model->dealt_with_backend_user) {
                    $label = 'Dealt with by ' . $model->dealt_with_backend_user->first_name . ' ' . $model->dealt_with_backend_user->last_name . ' on ' . date('jS F Y', strtotime($model->dealt_with_at));

                    $widget->fields['dealt_with']['label'] = $label;
                }
            }
        });
    }
}
