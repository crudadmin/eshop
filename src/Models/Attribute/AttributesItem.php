<?php

namespace AdminEshop\Models\Attribute;

use Admin;
use AdminEshop\Admin\Buttons\ShowProductsWithAttribute;
use AdminEshop\Admin\Rules\CastAttributeItemValue;
use AdminEshop\Contracts\Concerns\HasUnit;
use AdminEshop\Models\Attribute\Pivot\AssignedProductsPivot;
use AdminEshop\Models\Products\Product;
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

    protected $buttons = [
        ShowProductsWithAttribute::class,
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
            'code' => 'name:Importný kód|inaccessible|max:60|index',
        ];
    }

    public function mutateFields($fields)
    {
        if ( config('admineshop.attributes.types.colors', false) === true ){
            $fields->push([
                'color' => 'name:Farba|type:color',
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

    public function setListingResponse()
    {
        $this->makeHidden('attribute');

        return $this;
    }

    public function setDetailResponse()
    {
        $this->makeHidden('attribute');

        return $this;
    }

    public function getAttributeItemValue($attribute, $value = null)
    {
        $value = $value ?: $this->getValue('name');
        $space = !$attribute || $attribute->hasUnitSpace ? ' ' : '';
        $prepend = $attribute && $attribute->hasUnitPrepend;
        $unitName = $attribute ? $attribute->unitName : '';

        if ( $attribute->unitFormat == 'decimal' ){
            $value = str_replace('.', ',', (string)$value);
        }

        return $prepend ? $unitName.$space.$value : $value.$space.$unitName;
    }

    public function products()
    {
        return $this->belongsToMany(get_class(Admin::getModel('Product')), 'attributes_item_product_attributes_items');
    }

    public function scopeWithTextAttributes($query)
    {
        return $query
            ->with('attribute')
            ->whereHas('attribute', function($query){
                if ( config('admineshop.attributes.attributesText', false) ) {
                    $query->orWhere('product_info', 1);
                }

                if ( config('admineshop.attributes.attributesVariants', false) ) {
                    $query->orWhere('variants', 1);
                }
            });
    }

    public function scopeWithListingItems($query)
    {
        $query->select($this->getAttributesItemsColumns());
    }

    public function scopeWithResponse($query, $type)
    {
        $query->select($this->getAttributesItemsColumns());
    }

    public function scopeFilterByProducts($query, $options = [])
    {
        $query->whereHas('products', function($query) use ($options) {
            $query->setFilterOptions(array_merge($options ?: [], [
                '$ignore.filter.attributes' => true,
                'variants.extract' => true,
            ]));

            $query->applyQueryFilter();
        });
    }
}