<?php

namespace AdminEshop\Models\Attribute;

use AdminEshop\Admin\Rules\CastAttributeItemValue;
use AdminEshop\Contracts\Concerns\HasUnit;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Store;

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

    protected $rules = [
        CastAttributeItemValue::class,
    ];

    public function settings()
    {
        return [
            'title.insert' => 'Nová hodnota atribútu',
            'title.update' => ':item_name',
            'columns.id.hidden' => env('APP_DEBUG') == false,
            'columns.item_name.name' => 'Hodnota atribútu',
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
            'name' => 'name:Hodnota atribútu|hidden|component:AttributeItemValue|validate_attribute_unit|required'.(Store::isEnabledLocalization() ? '|locale' : ''),
        ];
    }

    public function mutateFields($fields)
    {
        if ( config('admineshop.attributes.types.colors', false) === true ){
            $fields->push([
                'color' => 'name:Farba|type:color|required_if:attribute_id,'.env('ATTR_COLOR_ID').'|inaccessibleIfNotIn:attribute_id,'.env('ATTR_COLOR_ID'),
            ]);
        }

        if ( config('admineshop.attributes.types.images', false) === true ){
            $fields->push([
                'image' => 'name:Obrázok|type:file|image|required_if:attribute_id,'.env('ATTR_IMAGES_ID').'|inaccessibleIfNotIn:attribute_id,'.env('ATTR_IMAGES_ID'),
            ]);
        }
    }

    public function scopeAdminRows($query)
    {
        $query->leftJoin('attributes', 'attributes.id', '=', 'attributes_items.attribute_id')
              ->selectRaw('attributes_items.*, attributes.unit_id');
    }

    public function setAdminAttributes($attributes)
    {
        $attributes['item_name'] = $this->getValue('name').' '.$this->unitName;
        $attributes['unit_id'] = $this->unit_id;

        return $attributes;
    }

    public function getAdminModelInitialData()
    {
        return [
            'store_units' => Store::getUnits(),
        ];
    }

    /**
     * Load this attributes into attributes list
     */
    public function getAttributesItemsColumns()
    {
        $columns = [
            'attributes_items.id',
            'attributes_items.attribute_id',
            'attributes_items.name',
            'attributes_items.slug',
        ];

        if ( config('admineshop.attributes.types.colors', false) === true ){
            $columns[] = 'attributes_items.color';
        }

        return $columns;
    }
}