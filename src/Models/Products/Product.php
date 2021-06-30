<?php

namespace AdminEshop\Models\Products;

use Admin;
use AdminEshop\Contracts\Collections\ProductsCollection;
use AdminEshop\Eloquent\CartEloquent;
use AdminEshop\Eloquent\Concerns\CanBeInCart;
use AdminEshop\Eloquent\Concerns\HasAttributesSupport;
use AdminEshop\Eloquent\Concerns\HasCart;
use AdminEshop\Eloquent\Concerns\HasCategoryTree;
use AdminEshop\Eloquent\Concerns\HasProductAttributes;
use AdminEshop\Eloquent\Concerns\HasProductFilter;
use AdminEshop\Eloquent\Concerns\HasProductImage;
use AdminEshop\Eloquent\Concerns\HasProductPaginator;
use AdminEshop\Eloquent\Concerns\HasProductResponses;
use AdminEshop\Eloquent\Concerns\HasStock;
use AdminEshop\Eloquent\Concerns\PriceMutator;
use AdminEshop\Models\Attribute\Attribute;
use AdminEshop\Models\Attribute\AttributesItem;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;
use Store;

class Product extends CartEloquent implements HasAttributesSupport
{
    use HasRelationships,
        HasProductImage,
        HasProductAttributes,
        HasStock,
        HasProductFilter,
        HasProductPaginator,
        HasProductResponses,
        HasCategoryTree;

    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2018-01-07 17:47:15';

    /*
     * Template name
     */
    protected $name = 'Produkty';

    /*
     * Template title
     * Default ''
     */
    protected $title = '';

    protected $group = 'products';

    protected $sluggable = 'name';

    // protected $sortable = false;

    /*
     * This items will be selected from db for cart items
     */
    protected $cartSelect = [
        'id', 'product_type', 'slug', 'name', 'image', 'code',
        'stock_quantity', 'stock_type', 'stock_sold',
    ];

    /*
     * Should be variants loaded automatically
     */
    public $loadVariants = true;

    /*
     * Should be filter in caregory response applied also for selected variants?
     */
    public $applyFilterOnVariants = true;

    /*
     * Extensions for main product
     */
    public $mainProductAttributes = true;
    public $mainProductGallery = true;

    /*
     * Extension for variants
     */
    public $variantsAttributes = true;
    public $variantsGallery = true;

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
        return static::class;
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
            Group::tab([
                Group::fields([
                    'name' => 'name:Názov produktu|limit:30|required_unless:product_type,variant|'.(Store::isEnabledLocalization() ? '|locale' : '|index'),
                    'product_type' => 'name:Typ produktu|type:select|option:name|index|default:regular|hideFromFormIf:product_type,variant|sub_component:setProductType|required',
                ])->inline(),
                'image' => 'name:Obrázok|type:file|image',
                Group::inline([
                    'ean' => 'name:EAN|hidden',
                    'code' => 'name:Kód produktu',
                ])->attributes('hideFromFormIf:product_type,variant'),
                'attributes_items' => 'name:Atribúty|belongsToMany:attributes_items,:attribute_name - :name',
            ])->icon('fa-pencil')->id('general'),
            'Cena' => Group::tab([
                'Cena' => Group::fields([
                    'vat' => 'name:Sazba DPH|belongsTo:vats,:name (:vat%)|defaultByOption:default,1|canAdd|hidden',
                    'price' => 'name:Cena bez DPH|type:decimal|default:0|component:PriceField|required_if:product_type,'.implode(',', Store::orderableProductTypes()),
                ])->id('price')->width(8),
                'Zľava' => Group::fields([
                    'discount_operator' => 'name:Typ zľavy|type:select|required_with:discount|hidden',
                    'discount' => 'name:Výška zľavy|type:decimal|hideFieldIfIn:discount_operator,NULL,default|required_if:discount_operator,'.implode(',', array_keys(operator_types())).'|hidden',
                ])->id('discount')->width(4),
            ])->icon('fa-money')->id('priceTab')->attributes('hideFromFormIf:product_type,variants'),
            'Popis' => Group::tab([
                'description' => 'name:Popis produktu|type:editor|hidden'.(Store::isEnabledLocalization() ? '|locale' : ''),
            ])->icon('fa-file-text-o'),
            'Sklad' => Group::tab(array_filter(array_merge(
                [
                    'stock_quantity' => 'name:Sklad|type:integer|default:0|hideFromFormIf:product_type,variants',
                ],
                config('admineshop.stock.store_rules', true)
                    ? [ Group::fields([
                        'stock_type' => 'name:Možnosti skladu|default:default|type:select|index',
                        'stock_sold' => 'name:Text dostupnosti tovaru s nulovou skladovosťou|hideFromFormIfNot:stock_type,everytime'
                    ])->attributes('hideFromFormIf:product_type,variant') ] : [],
            )))->icon('fa-bars')->add('hidden')->attributes(config('admineshop.stock.store_rules', true) ? '' : 'hideFromFormIf:product_type,variants'),
            Group::tab(self::class)->attributes('hideFromFormIfNot:product_type,variants'),
            $this->hasAttributesEnabled() ? Group::tab(ProductsAttribute::class) : [],
            'Ostatné nastavenia' => Group::tab([
                'created_at' => 'name:Vytvorené dňa|default:CURRENT_TIMESTAMP|type:datetime|disabled',
                'published_at' => 'name:Publikovať od|default:CURRENT_TIMESTAMP|type:datetime',
            ])->id('otherSettings')->icon('fa-gear'),
        ];
    }

    public function mutateFields($fields)
    {
        if ( config('admineshop.categories.enabled') ){
            $fields->group('general', function($group){
                $group->push([
                    'categories' => 'name:Kategória|belongsToMany:categories,name|component:selectParentCategories|canAdd|removeFromFormIfNot:product_id,NULL',
                ]);
            });
        }
    }

    public function options()
    {
        $options = [
            'vat_id' => Store::getVats(),
            'product_type' => array_merge(config('admineshop.product_types', []), [
                'variant' => 'Varianta',
            ]),
            'discount_operator' => [ 'default' => 'Žiadna zľava' ] + operator_types(),
            'stock_type' => [
                'default' => 'Preberať z globalných nastavení eshopu',
                'show' => 'Zobraziť vždy s možnosťou objednania len ak je skladom',
                'everytime' => 'Zobrazit a objednat vždy, bez ohľadu na sklad',
                'hide' => 'Zobrazit a mať možnost objednat len ak je skladom',
            ],
            'attributes_items' => $this->getAttributesList(),
        ];

        if ( config('admineshop.categories.enabled') ) {
            $options['categories'] = $this->getCategoriesOptions();
        }

        return $options;
    }

    public function settings()
    {
        return [
            'title.insert' => 'Nový produkt',
            'title.update' => ':name',
            'grid.default' => 'full',
            'recursivity.name' => 'Varianty produktu',
            'recursivity.max_depth' => 1,
            'columns.attributes' => [
                'hidden' => $this->hasAttributesEnabled() ? false : true,
                'name' => 'Atribúty',
                'before' => 'code',
            ],
        ];
    }

    public function scopeAdminRows($query)
    {
        //Load all attributes data
        if ( $this->hasAttributesEnabled() == true ) {
            // $query->with('attributesItems');
        }
    }

    public function scopeNonVariantProducts($query, $table = 'products')
    {
        $query->whereIn(implode('.', array_filter([$table, 'product_type'])), Store::nonVariantsProductTypes());
    }

    public function scopeVariantsProducts($query, $table = 'products')
    {
        $query->whereIn(implode('.', array_filter([$table, 'product_type'])), Store::variantsProductTypes());
    }

    public function scopeVariantProducts($query, $table = 'products')
    {
        $query->where(implode('.', array_filter([$table, 'product_type'])), 'variant');
    }

    public function scopeOrderableProducts($query, $table = 'products')
    {
        $query->whereIn(implode('.', array_filter([$table, 'product_type'])), Store::orderableProductTypes());
    }

    public function scopeNonOrderableProducts($query, $table = 'products')
    {
        $query->whereNotIn(implode('.', array_filter([$table, 'product_type'])), Store::orderableProductTypes());
    }

    /**
     * Check if product is given type
     *
     * @param  string  $type
     * @return bool
     */
    public function isType($type)
    {
        if ( ! array_key_exists($type, config('admineshop.product_types')) ) {
            abort(500, 'Type '.$type.' does not exists.');
        }

        return $this->product_type == $type;
    }

    public function mutateCategoryResponse()
    {
        $visible = [ 'product_type' ];

        //If variants are enabled, and has been loaded before.
        //We does not want to push variants into product, when developer did not fetch variants from database
        if ( count(Store::variantsProductTypes()) && $this->relationLoaded('variants') ){
            $visible[] = 'variants';

            $this->variants->each->setCategoryResponse();
        }

        $this->makeVisible($visible);
    }

    public function mutateDetailResponse()
    {
        //If variants are enabled
        if ( count(Store::variantsProductTypes()) ){
            $this->variants->each->setDetailResponse();
        }
    }

    public function getCheapestVariantClientPriceAttribute()
    {
        if ( !$this->relationLoaded('variants') ){
            return 0;
        }

        $prices = [];

        foreach ($this->variants as $variant) {
            $prices[] = $variant->getAttribute('clientPrice');
        }

        asort($prices);

        return count($prices) ? $prices[0] : 0;
    }

    public function setAdminAttributes($attributes)
    {
        //TODO: add attributes into table
        // $attributes['attributes'] = $this->attributesText;

        return $attributes;
    }

    public function variants()
    {
        return $this->hasMany(get_class(Admin::getModel('Product')), 'product_id');
    }

    /**
     * TODO: complete
     * Returns on stock variants with product table
     *
     * @return  void
     */
    public function scopeWithParentProductData($query)
    {
        $selectColumns = ['products.*'];

        $selectColumns[] = 'pm.image as main_image';

        if ( config('admineshop.stock.store_rules', true) ) {
            $selectColumns = array_merge($selectColumns, [
                'pm.stock_type as main_stock_type', 'pm.stock_sold as main_stock_sold',
            ]);
        }

        $query->addSelect($selectColumns)
              ->leftJoin('products as pm', 'products.product_id', '=', 'pm.id');
    }

    private function getAttributesList()
    {
        $attribute = new Attribute;

        return AttributesItem::select('attributes_items.id', 'attributes_items.name', 'attributes.name as attribute_name')
                ->leftJoin('attributes', 'attributes_items.attribute_id', '=', 'attributes.id')
                ->orderBy('attributes.id', 'ASC')
                ->get()
                ->each->setLocalizedResponse()
                ->map(function($item) use ($attribute) {
                    $item->attribute_name = $attribute->setRawAttributes([ 'name' => $item->attribute_name ])->name;

                    return $item;
                });
    }
}