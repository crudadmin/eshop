<?php

namespace AdminEshop\Models\Products;

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
use AdminEshop\Eloquent\Concerns\HasStock;
use AdminEshop\Eloquent\Concerns\PriceMutator;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Store;

class Product extends CartEloquent implements HasAttributesSupport
{
    use HasProductImage,
        HasProductAttributes,
        HasStock,
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

    protected $layouts = [
        'form-top' => 'setProductTabs',
    ];

    /*
     * This items will be selected from db for cart items
     */
    protected $cartSelect = [
        'id', 'product_type', 'slug', 'name', 'image', 'code',
        'stock_quantity', 'stock_type', 'stock_sold',
    ];

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
            $this->getOtherSettingsFields(),
        ];
    }

    public function options()
    {
        $options = [
            'vat_id' => Store::getVats(),
            'product_type' => config('admineshop.product_types', []),
            'discount_operator' => [ 'default' => 'Žiadna zľava' ] + operator_types(),
            'stock_type' => [
                'default' => 'Preberať z globalných nastavení eshopu',
                'show' => 'Zobraziť vždy s možnosťou objednania len ak je skladom',
                'everytime' => 'Zobrazit a objednat vždy, bez ohľadu na sklad',
                'hide' => 'Zobrazit a mať možnost objednat len ak je skladom',
            ],
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
            'columns.attributes' => [
                'hidden' => $this->hasAttributesEnabled() ? false : true,
                'name' => 'Atribúty',
                'before' => 'code',
            ],
            'state_filter' => [
                'unassigned' => [ 'name' => 'Nezaradené', 'title' => 'Nezaradené do kategórii' ],
                'active' => [ 'name' => 'Aktívne' ],
                'inactive' => [ 'name' => 'Neaktívne' ],
            ],
            'decimals.round_without_vat' => config('admineshop.prices.round_without_vat', false),
        ];
    }

    public function scopeAdminRows($query)
    {
        //Load all attributes data
        if ( $this->hasAttributesEnabled() == true ) {
            $query->with('attributesItems');
        }
    }

    public function scopeNonVariantProducts($query)
    {
        $query->whereIn('product_type', Store::nonVariantsProductTypes());
    }

    public function scopeVariantProducts($query)
    {
        $query->whereIn('product_type', Store::variantsProductTypes());
    }

    public function scopeOrderableProducts($query)
    {
        $query->whereIn('product_type', Store::orderableProductTypes());
    }

    public function scopeNonOrderableProducts($query)
    {
        $query->whereNotIn('product_type', Store::orderableProductTypes());
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
        $prices = [];

        foreach ($this->variants as $variant) {
            $prices[] = $variant->getAttribute('clientPrice');
        }

        asort($prices);

        return count($prices) ? $prices[0] : 0;
    }

    public function setAdminRowsAttributes($attributes)
    {
        $attributes['attributes'] = $this->attributesText;

        //Remove uneccessary decimals
        $attributes['price'] = $this->price + 0;

        return $attributes;
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