<?php

namespace AdminEshop\Models\Store;

use AdminEshop\Contracts\Concerns\HasUnit;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;

class AttributesItem extends AdminModel
{
    use HasUnit;

    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2018-01-11 17:48:15';

    /*
     * Template name
     */
    protected $name = 'Hodnoty atribútu';

    /*
     * Template title
     * Default ''
     */
    protected $title = '';

    protected $belongsToModel = Attribute::class;

    protected $inTab = true;
    protected $withoutParent = true;
    protected $publishable = false;
    protected $reversed = true;

    protected $sluggable = 'name';

    protected $hidden = ['pivot'];

    public function settings()
    {
        return [
            'title.insert' => 'Nová hodnota atribútu',
            'title.update' => ':name',
            'columns.id.hidden' => env('APP_DEBUG') == false,
        ];
    }

    /*
     * Automatic form and database generation
     * @name - field name
     * @placeholder - field placeholder
     * @type - field type | string/text/editor/select/integer/decimal/file/password/date/datetime/time/checkbox/radio
     * ... other validation methods from laravel
     */
    public function fields()
    {
        return [
            'name' => 'name:Hodnota atribútu|required',
        ];
    }

    public function getAttributesItemsColumns()
    {
        return [
            'attributes_items.id',
            'attributes_items.attribute_id',
            'attributes_items.name',
            'attributes_items.slug',
        ];
    }

    public function getProductAttributesItemsColumns()
    {
        return [
            'attributes_items.id',
            'attributes_items.attribute_id',
            'attributes_items.name',
            'attributes_items.slug',
        ];
    }
}