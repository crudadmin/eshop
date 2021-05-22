<?php

namespace AdminEshop\Models\Products;

use Admin;
use AdminEshop\Eloquent\CartEloquent;
use AdminEshop\Eloquent\Concerns\HasAttributesSupport;
use AdminEshop\Eloquent\Concerns\HasProductAttributes;
use AdminEshop\Eloquent\Concerns\HasProductFilter;
use AdminEshop\Eloquent\Concerns\HasProductImage;
use AdminEshop\Eloquent\Concerns\HasProductResponses;
use AdminEshop\Eloquent\Concerns\HasStock;
use AdminEshop\Eloquent\Concerns\HasVariantColors;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Store;

class ProductsVariant extends CartEloquent implements HasAttributesSupport
{
    use HasProductAttributes,
        HasStock,
        HasProductImage,
        HasProductFilter,
        HasProductResponses,
        HasVariantColors;

    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2018-01-12 17:33:15';

    /*
     * Template name
     */
    protected $name = 'Varianty produktu';

    /*
     * Template title
     * Default ''
     */
    protected $title = '';

    protected $inTab = true;

    protected $withoutParent = true;

    protected $sluggable = 'name';

    protected $icon = 'fa-bars';

    /*
     * This items will be selected frm db for cart items
     */
    protected $cartSelect = [
        'id', 'product_id', 'name', 'image', 'code', 'stock_quantity',
    ];

    /*
     * Should be filter in caregory response applied also for selected variants?
     */
    protected $applyFilterOnVariants = true;

    /**
     * Model constructor
     *
     * @param  array  $options
     */
    public function __construct(array $options = [])
    {
        $this->append($this->getStockAttributes());

        parent::__construct($options);
    }

    public function belongsToModel()
    {
        return get_class(Admin::getModel('Product'));
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
            'Nastavenie varianty' => Group::tab([
                'product_type' => 'type:imaginary|component:AddTypeFieldIntoRequest',
                Group::fields([
                    'name' => 'name:Názov varianty|limit:40|required'.(Store::isEnabledLocalization() ? '|locale' : ''),
                    'image' => 'name:Obrázok|image',
                ])->inline(),
                Group::fields([
                    'ean' => 'name:EAN varianty|hidden',
                    'code' => 'name:Kód varianty',
                ])->inline(),
            ])->grid(5)->icon('fa-pencil')->id('general'),
            'Popis' => Group::tab([
                'description' => 'name:Popis varianty|type:editor|hidden'.(Store::isEnabledLocalization() ? '|locale' : ''),
            ])->icon('fa-file-text-o'),
            'Cena' => Group::tab([
                'Cena' => Group::fields([
                    'vat' => 'name:Sazba DPH|belongsTo:vats,:name (:vat%)|defaultByOption:default,1|required|canAdd|hidden',
                    'price' => 'name:Cena bez DPH|type:decimal|default:0|component:PriceField|required_unless:product_type,'.implode(',', Store::orderableProductTypes()),
                ])->width(8)->id('price'),
                'Zľava' => Group::fields([
                    'discount_operator' => 'name:Typ zľavy|type:select|required_with:discount|hidden',
                    'discount' => 'name:Výška zľavy|type:decimal|hideFieldIfIn:discount_operator,NULL,default|required_if:discount_operator,'.implode(',', array_keys(operator_types())).'|hidden',
                ])->width(4)->id('discount'),
            ])->icon('fa-money'),
            'Sklad' => Group::tab([
                'stock_quantity' => 'name:Počet na sklade|type:integer|default:0',
            ])->grid(7)->icon('fa-gear'),
            $this->hasAttributesEnabled() ? Group::tab( ProductsAttribute::class ) : [],
        ];
    }

    public function settings()
    {
        return [
            'increments' => env('APP_DEBUG') == true,
            'title.insert' => 'Nová varianta',
            'title.update' => 'Úprava varianty :name',
            'title.rows' => 'Zoznam variant',
            'grid' => [
                'default' =>'full',
                'disabled' => true,
            ],
            'columns.attributes' => [
                'hidden' => $this->hasAttributesEnabled() ? false : true,
                'name' => 'Atribúty',
                'before' => 'code',
            ],
            'buttons' => [
                'insert' => 'Nová varianta',
                'update' => 'Uložiť variantu',
                'create' => 'Pridať variantu',
            ],
            'autoreset' => false,
        ];
    }

    public function options()
    {
        return [
            'vat_id' => Store::getVats(),
            'discount_operator' => [ 'default' => 'Žiadna zľava' ] + operator_types(),
        ];
    }

    public function scopeAdminRows($query)
    {
        //Load all attributes data
        if ( $this->hasAttributesEnabled() == true ) {
            $query->with('attributesItems');
        }
    }

    public function setAdminAttributes($attributes)
    {
        $attributes['attributes'] = $this->attributesText;

        return $attributes;
    }

    /**
     * Variant product is all the time regular type
     *
     * @param  bool  $type
     * @return  bool
     */
    public function isType($type)
    {
        return 'regular' == $type;
    }

    /**
     * Returns on stock variants with product table
     *
     * @return  void
     */
    public function scopeWithParentProductData($query)
    {
        $query->select('products_variants.*', 'products.stock_type', 'products.stock_sold', 'products.image as product_image')
              ->leftJoin('products', 'products.id', '=', 'products_variants.product_id');
    }

    public function mutateCategoryResponse()
    {
        if ( config('admineshop.attributes.types.colors', false) === true ){
            $this->addColorsInResponse();
        }
    }

    public function gallery()
    {
        return $this->hasMany(get_class(Admin::getModel('ProductsGallery')));
    }
}