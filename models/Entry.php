<?php namespace Dstokesy\Forms\Models;

use Model;
use Event;
use Backend;
use System\Classes\PluginManager;
use RainLab\Translate\Models\Message as TranslateMessage;

/**
 * Entry Model
 */
class Entry extends Model
{
    use \October\Rain\Database\Traits\SoftDelete;

    public $form_fields;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'dstokesy_forms_entries';

    /**
     * @var array Behaviours Implemented
     */
    public $implement = [
        '@Dstokesy.Franchises.Behaviors.FranchisableModel'
    ];


    /**
     * @var array Date fields
     */
    protected $dates = ['deleted_at'];

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [
        'form_id',
        'data',
    ];

    public $jsonable = ['data'];

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $hasMany = [];
    public $belongsTo = [
        'form' => [
            'Dstokesy\Forms\Models\Form'
        ],
        'dealt_with_backend_user' => [
            'Backend\Models\User'
        ],
    ];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];

    /**
     * Attributes
     */

    public function getNameColumnAttribute()
    {
        $data = $this->data;
        $name = '';

        if (isset($data['name'])) {
            $name = $data['name'];
        } else {
            if (isset($data['first_name'])) {
                $name = $data['first_name'];
            }

            if (isset($data['last_name'])) {
                $name .= ' ' . $data['last_name'];
            }

            if (isset($data['surname'])) {
                $name .= ' ' . $data['surname'];
            }
        }

        return $name;
    }

    public function getBackendUrlAttribute()
    {
        return Backend::url('dstokesy/forms/entries/update/' . $this->id);
    }

    public function getSubmissionAttribute()
    {
        if ($submission_data = $this->data) {
            $return_str = '';
            $this->form_fields = $this->getFormFieldsArray();

            foreach ($submission_data as $field => $value){
                if (isset($this->form_fields[$field])) {
                	if (!is_array($value)) {
	                    $label = $this->getFieldLabel($field, $value);

	                    if(PluginManager::instance()->hasPlugin('RainLab.Translate')) {
	                        $label = TranslateMessage::trans($label);
	                    }

	                    $return_str .= $label . ': ' . "\n" . $this->getFieldValue($field, $value)."\n"."\n";
                	}
                }
            }

            Event::fire('dstokesy.forms.entry.afterGetSubmissionAttribute', [$this, &$return_str]);

            return $return_str;
        }
    }

    /**
     * Scopes
     */

    public function scopeIsUnRead($query)
    {
        return $query->where(function ($query) {
            $query->where('is_read', 0)
                ->orWhereNull('is_read');
        });
    }

    public function scopeIsRead($query)
    {
        return $query->where('is_read', 1);
    }

    /**
     * Methods
     */

    public function getFormFieldsArray()
    {
        if ($form = Form::find($this->form_id)) {
            if ($fields = $form->fields) {
                foreach ($fields as $field) {
                    foreach ($field as $key => $value){
                        if (isset($field['name']))
                            $array[$field['name']][$key] = $value;
                    }
                }
                return $array;
            }
        }
    }

    public function getFieldLabel($field_name, $value)
    {
        $field_names = [];

        if ($form = Form::find($this->form_id)) {

            $fields = collect($form->fields);

            $field = $fields->where('name', $field_name)->first();

            if ($field) {

                if (isset($field['label']) || isset($field['placeholder'])) {

                    $labelField = isset($field['placeholder']) && $field['placeholder'] != '' && $field['placeholder'] != '#' ? 'placeholder' : 'label';

                    if ($field['_group'] == 'radios') {

                        $field_names[$field['name']] = $field['label'];

                    } elseif ($field['_group'] == 'checkbox') {
                        if ($value == 1) {
                            $field_names[$field['name']] = '&#9745; ' . $field['label'];
                        } else {
                        }

                    } else {

                        $field_names[$field['name']] = $field[$labelField];
                    }
                }
            }

            return isset($field_names[$field_name])
                ? $field_names[$field_name]
                : false;

        } else {
            return $field_name;
        }
    }

    public function getFieldValue($field_name, $value)
    {
        if (!isset($this->form_fields[$field_name]))
            return;

        if (($this->form_fields[$field_name]['_group'] == 'radios') || ($this->form_fields[$field_name]['_group'] == 'select')) {

            if (isset($this->form_fields[$field_name]['options'])) {
                foreach ($this->form_fields[$field_name]['options'] as $option_arr) {
                    $dropdown_options[$option_arr['value']] = $option_arr['name'];
                }

                return isset($dropdown_options[$value])
                    ? $dropdown_options[$value]
                    : $value;
            } else {
                return $value;
            }

        } elseif ($this->form_fields[$field_name]['_group'] == 'checkbox') {
            return;
        } elseif ($this->form_fields[$field_name]['_group'] == 'fileupload') {
            $file = \System\Models\File::find($value);
            return '<a target="_blank" href="' . $file->getPath() . '">' . $file->file_name . '</a>';
        } else {
            return $value;
        }
    }

    public static function getQueryWithFilters($filters)
    {
        $query = self::query();
        
        foreach ($filters as $filter => $value) {
			switch ($filter) {
				case 'scope-form':
                    if (is_array($value)) {
					    $query->whereIn('form_id', array_keys($value));
                    }
					break;
				case 'scope-is_read':
					if ($value === '1') {
                        $query->where('is_read', '!=', '1');
                    } else if ($value === '2') {
                        $query->where('is_read', '1');
                    }
					break;
				case 'scope-dealt_with':
					if ($value === '1') {
                        $query->where(function ($q) {
                            $q->where('dealt_with', '!=', '1')
                                ->orWhereNull('dealt_with');
                        });
                    } else if ($value === '2') {
                        $query->where('dealt_with', '1');
                    }
					break;
				default:
					break;
			}
		}

        return $query;
    }
}
