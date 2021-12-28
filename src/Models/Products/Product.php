<?php

namespace AdminEshop\Models\Products;

use Admin;
use AdminEshop\Contracts\Collections\ProductsCollection;
use AdminEshop\Eloquent\CartEloquent;
use AdminEshop\Eloquent\Concerns\CanBeInCart;
use AdminEshop\Eloquent\Concerns\HasAttributesSupport;
use AdminEshop\Eloquent\Concerns\HasCart;
use AdminEshop\Eloquent\Concerns\HasCategoryTree;
use AdminEshop\Eloquent\Concerns\HasHeureka;
use AdminEshop\Eloquent\Concerns\HasProductAttributes;
use AdminEshop\Eloquent\Concerns\HasProductFields;
use AdminEshop\Eloquent\Concerns\HasProductFilter;
use AdminEshop\Eloquent\Concerns\HasProductImage;
use AdminEshop\Eloquent\Concerns\HasProductPaginator;
use AdminEshop\Eloquent\Concerns\HasProductResponses;
use AdminEshop\Eloquent\Concerns\HasProductSorter;
use AdminEshop\Eloquent\Concerns\HasStock;
use AdminEshop\Eloquent\Concerns\PriceMutator;
use AdminEshop\Models\Attribute\Attribute;
use AdminEshop\Models\Attribute\AttributesItem;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Store;

class Product extends CartEloquent implements HasAttributesSupport
{
    use HasProductImage,
        HasProductAttributes,
        HasStock,
        HasProductSorter,
        HasProductFilter,
        HasProductPaginator,
        HasProductResponses,
        HasCategoryTree,
        HasHeureka,
        HasProductFields;

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
     * Extensions for main product
     */
    public $mainProductAttributes = true;

    /*
     * Extension for variants
     */
    public $variantsAttributes = true;

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
            $this->getGeneralFields(),
            $this->getPriceFields(),
            $this->getDescriptionFields(),
            $this->getWarehouseFields(),
            $this->hasAttributesEnabled() ? Group::tab( ProductsAttribute::class ) : [],
            Group::tab(self::class)->attributes('hideFromFormIfNot:product_type,variants'),
            $this->getOtherSettingsFields(),
        ];
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
            'state_filter' => [
                'unassigned' => [ 'name' => 'Nezaradené', 'title' => 'Nezaradené do kategórii' ],
                'active' => [ 'name' => 'Aktívne' ],
                'inactive' => [ 'name' => 'Neaktívne' ],
            ]
        ];
    }

    public function getCartSelectColumns($columns = [])
    {
        $columns = [
            'id', 'product_id', 'product_type', 'slug', 'name', 'image', 'code',
            'stock_quantity',
        ];

        if ( config('admineshop.stock.store_rules', true) ) {
            $columns = array_merge($columns, ['stock_type', 'stock_sold']);
        }

        return parent::getCartSelectColumns($columns);
    }

    public function scopeAdminRows($query)
    {
        //Load all attributes data
        if ( $this->hasAttributesEnabled() == true ) {
            $query->with(['attributesItems' => function($query){
                $query->withTextAttributes();
            }]);
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
        return $this->product_type == $type;
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
        if ( config('admineshop.attributes.attributesVariants', false) == true ) {
            $attributes['attributes'] = $this->attributesVariantsText;
        } else if ( config('admineshop.attributes.attributesText', false) == true ) {
            $attributes['attributes'] = $this->attributesText;
        }

        return $attributes;
    }

    public function variants()
    {
        return $this->hasMany(self::class, 'product_id');
    }

    /**
     * TODO: complete
     * Returns on stock variants with product table
     *
     * @return  void
     */
    public function scopeWithParentProductData($query, $selectColumns = [])
    {
        $selectColumns = array_merge($selectColumns, ['main_product.image as main_image']);

        if ( config('admineshop.stock.store_rules', true) ) {
            $selectColumns = array_merge($selectColumns, [
                'main_product.stock_type as main_stock_type', 'main_product.stock_sold as main_stock_sold',
            ]);
        }

        $query->addSelect(array_unique($selectColumns))
              ->leftJoin('products as main_product', 'products.product_id', '=', 'main_product.id');
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

    public function scopeSetFilterProperty($query, $type)
    {
        if ( $type == 'unassigned' ){
            $query->whereDoesntHave('categories');
        } else if ( $type == 'active' ){
            $query->whereNotNull('published_at');
        } else if ( $type == 'inactive' ){
            $query->whereNull('published_at');
        }
    }
}