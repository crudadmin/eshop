<?php

namespace AdminEshop\Models\Import;

use AdminEshop\Admin\Rules\ImportRule;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;

class ImportsFile extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2021-11-16 18:47:25';

    /*
     * Template name
     */
    protected $name = 'Import dát';

    protected $publishable = false;

    protected $sortable = false;

    protected $group = 'store';

    protected $icon = 'fa-file-import';

    protected $rules = [
        ImportRule::class,
    ];

    protected $settings = [
        'refresh_interval' => 0,
        'buttons.insert' => 'Uložiť a importovať',
        'buttons.update' => 'Uložiť a importovať',
        'columns.last_import.name' => 'Posledný import',
    ];

    /*
     * Automatic form and database generator by fields list
     * :name - field name
     * :type - field type (string/text/editor/select/integer/decimal/file/password/date/datetime/time/checkbox/radio)
     * ... other validation methods from laravel
     */
    public function fields()
    {
        return [
            'name' => 'name:Názov importu',
            'type' => 'name:Typ importu|type:select|option::name|default:'.($this->getImportClassNameTypes()[0] ?? '').'|required',
            'file' => 'name:Importny súbor (.xlsx)|type:file|extensions:xlsx,csv|required',
        ];
    }

    public function options()
    {
        return [
            'type' => $this->getTypeOptions(),
        ];
    }

    public function setAdminAttributes($attributes)
    {
        $attributes['last_import'] = ($this->updated_at ?: $this->created_at)->format('d.m.Y H:i');

        return $attributes;
    }

    public function getImports()
    {
        return collect(config('admineshop.import'));
    }

    public function getImporter()
    {
        return $this->getImports()
                    ->first(fn($import) => strpos($import['class'], $this->type) !== false);
    }

    private function getImportClassNameTypes()
    {
        return $this->getImports()->pluck('class')->map(function($classname){
            return class_basename($classname);
        })->toArray();
    }

    private function getTypeOptions()
    {
        return array_combine($this->getImportClassNameTypes(), $this->getImports()->toArray());
    }
}