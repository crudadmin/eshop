<?php

namespace AdminEshop\Eloquent\Concerns;

use Admin;
use AdminEshop\Eloquent\Concerns\HasAttributesSupport;
use AdminEshop\Models\Products\Product;
use Admin\Eloquent\AdminModel;
use Admin\Eloquent\Modules\SeoModule;
use Arr;
use Illuminate\Support\Facades\DB;
use Store;

trait HasProductResponses
{
    protected $temporaryFilterPrefix = null;
    protected $temporaryFilterOptions = [];

    static $filterOptions = [
        'filter' => [],
        'scope' => null, //only parent product
        'scope.variants' => null, //only variants
        'scope.product' => null, //only regular product or variant
        'listing.attributes' => false,
        'listing.price_ranges' => false,
        'listing.variants' => false,
        'listing.variants.filter' => true,
        'listing.variants.extract' => false,
        'listing.variants.attributes' => false,
        'detail.gallery' => false,
        'detail.variants' => false,
        'detail.variants.attributes' => false,
        'detail.variants.gallery' => false,
        'cart.attributes' => false,
        'cart.variants.attributes' => false,
    ];

    public function scopeSetFilterOptions($query, $options, $prefix = null)
    {
        if ( $prefix ){
            $this->setFilterPrefix($prefix);

            foreach ($options as $key => $value) {
                //Skip adding filter into scoped settings
                if ( in_array($key, ['filter']) ){
                    continue;
                }

                $options[$prefix.'.'.$key] = $value;
                unset($options[$key]);
            }
        }

        $this->temporaryFilterOptions = array_merge($this->temporaryFilterOptions, $options);
    }

    public function getFilterOption($key, $default = null)
    {
        $options = $this->getFilterOptions();

        //Use prefix key instead of global key.
        if ( $prefix = $this->getFilterPrefix() ) {
            $prefixKey = $prefix.'.'.$key;

            if ( Arr::has($options, $prefixKey) ) {
                $key = $prefixKey;
            }
        }

        return Arr::get($options, $key, $default);
    }

    public function scopeSetGlobalFilterOptions($query, $options)
    {
        self::$filterOptions = array_merge(self::$filterOptions, $options);
    }

    public function scopeSetFilterPrefix($query, $prefix)
    {
        $this->temporaryFilterPrefix = $prefix;
    }


    public function getFilterPrefix()
    {
        return $this->temporaryFilterPrefix;
    }

    public function scopeCloneModelFilter($query, AdminModel $model)
    {
        $this->setFilterOptions(
            $model->getFilterOptions()
        );

        if ( $prefix = $model->getFilterPrefix() ) {
            $this->setFilterPrefix($prefix);
        }
    }

    public function getFilterOptions()
    {
        return array_merge(self::$filterOptions, $this->temporaryFilterOptions);
    }

    /**
     * Which price fields should be exposed into request
     *
     * @return  array
     */
    public function getVisiblePriceAttributes()
    {
        return ['initialPriceWithVat', 'defaultPriceWithVat', 'priceWithVat', 'priceWithoutVat'];
    }

    /**
     * Which stock attributes should be exposed into request
     *
     * @return  array
     */
    public function getVisibleStockAttributes()
    {
        $columns = ['stock_quantity', 'stockText', 'stockNumber', 'hasStock'];

        if ( config('admineshop.stock.store_rules', true) ) {
            $columns[] = 'canOrderEverytime';
        }

        return $columns;
    }

    /**
     * This columns are shared between product an variant.
     * You can replace this method in model.
     *
     * @return  array
     */
    public function visibleOrderableColumns()
    {
        $columns = ['id', 'slug', 'name', 'thumbnail'];

        if ( config('admineshop.stock.store_rules', true) ) {
            $columns[] = 'stock_type';
        }

        if ( config('admineshop.attributes.attributesText', false) == true ) {
            $columns[] = 'attributesText';
        }

        //In main product we does not need this properties, because they are present in each variant
        if ( in_array($this->getAttribute('product_type'), Store::variantsProductTypes()) === false ){
            $columns = array_merge($columns, $this->getVisiblePriceAttributes());
            $columns = array_merge($columns, $this->getVisibleStockAttributes());
        }

        return $columns;
    }

    public function setListingResponse()
    {
        $this->append($this->getPriceAttributes());

        $this->setVisible(
            $this->visibleOrderableColumns()
        );

        $this->append([
            'thumbnail',
        ]);

        if ( $this->relationLoaded('variants') ){
            $this->makeVisible(['variants']);

            $this->variants->each->setVariantListingResponse();
        }

        if ( $this->relationLoaded('attributesItems') ) {
            $this->makeVisible(['attributesList']);
            $this->append(['attributesList']);

            $this->attributesList->each->setListingResponse();
        }

        $this->makeVisible([
            'product_type'
        ]);

        return $this;
    }

    /**
     * Which columns should be appended
     * You can replace this method in your model
     *
     * @return  array
     */
    public function setDetailResponse()
    {
        $this->setListingResponse();

        //If variants are enabled
        if ( $this->relationLoaded('variants') ){
            $this->variants->each->setVariantDetailResponse();
        }

        $isVariant = $this->product_id ? true : false;

        if ( $this->relationLoaded('gallery') ) {
            $this->gallery->each->setDetailResponse();

            $this->makeVisible(['gallery']);
        }

        if ( $this->relationLoaded('attributesItems') ) {
            $this->makeVisible(['attributesList']);
            $this->append(['attributesList']);

            $this->attributesList->each->setDetailResponse();
        }

        if ( $this->getProperty('seo') == true ){
            $this->makeVisible([
                ...SeoModule::$metaKeys,
                'metaImageThumbnail',
            ]);

            $this->append([
                'metaImageThumbnail',
            ]);
        }


        $this->makeVisible([
            'description',
            'categories',
            'detailThumbnail',
            'code',
        ]);

        $this->append([
            'detailThumbnail',
        ]);

        if ( config('admineshop.attributes.attributesText', false) == true ) {
            $this->append(['attributesText']);
        }

        return $this;
    }

    public function setVariantDetailResponse()
    {
        $this->setDetailResponse();

        $this->makeVisible(['product_id']);
    }

    public function setVariantListingResponse()
    {
        $this->setListingResponse();

        $this->makeVisible(['product_id']);
    }

    /**
     * We can set cart response... we can append() or make hidden fields in this method here
     *
     * void
     */
    public function setCartResponse()
    {
        $this->setListingResponse();

        return $this;
    }

    public function setSearchResponse()
    {
        $this->setListingResponse();

        return $this;
    }

    public function setFavouriteResponse()
    {
        return $this;
    }

    /**
     * Display products into category response
     * Also filter products and variants
     *
     * @param  Builder  $query
     * @param  array    $options
     */
    public function scopeWithListingResponse($query, $options = [])
    {
        $this->setFilterOptions($options, 'listing');

        $query->withBlockedStock();

        //We need specify select for
        $query->addSelect('products.*');

        $query->applyQueryFilter();

        $query->withProductModules();

        $query->sortByParams();
    }

    public function scopeWithDetailResponse($query, $options = [])
    {
        $this->setFilterOptions($options, 'detail');

        //We need specify select for
        $query->addSelect('products.*');

        $query->withProductModules();

    }

    public function scopeWithFavouriteResponse($query, $options = [])
    {
        $this->setFilterOptions($options, 'favourite');

        //We need specify select for
        $query->addSelect('products.*');

        $query->applyQueryFilter();

        $query->withProductModules();
    }

    public function scopeWithSearchResponse($query, $options = [])
    {
        $query->withListingResponse($options);
    }

    public function scopeWithProductModules($query, $prefix = null, $variants = false)
    {
        $prefix = $prefix ? $prefix.'.' : '';

        $query->withMainGalleryImage($variants ? true : false);

        if ( $this->getFilterOption($prefix.'price_ranges', false) === true ) {
            $query->withMinAndMaxVariantPrices();
        }

        if ( config('admineshop.prices.price_levels') ) {
            $query->withPriceLevels();
        }

        if (
            $variants === false
            && $this->getFilterOption($prefix.'variants.extract', false) === false
            && $this->getFilterOption($prefix.'variants', true) === true
            && count(Store::variantsProductTypes())
        ){
            $query->extendWith(['variants' => function($query) use ($prefix) {
                $query
                    ->select('products.*')
                    ->cloneModelFilter($this)
                    ->withParentProductData()
                    ->filterVariantProduct()
                    ->withBlockedStock();

                //We can deside if filter should be applied also on selected variants
                if ( $this->getFilterOption($prefix.'variants.filter', false) ) {
                    $query->filterProduct(
                        $this->getFilterOption('filter')
                    );
                }

                $query->withProductModules($prefix.'variants', true);
            }]);
        }

        if ( $attributesScope = $this->getFilterOption($prefix.'attributes', false) ) {
            $query->with([
                'attributesItems' => function($query) use ($attributesScope) {
                    $query->withResponse($this->getFilterPrefix());

                    if ( is_callable($attributesScope) ){
                        $attributesScope($query);
                    }

                    $query->with(['attribute' => function($query){
                        $query->select($query->getModel()->getAttributesColumns());
                    }]);
                }
            ]);
        }

        if ( $this->getFilterOption($prefix.'gallery', false) === true ) {
            $query->with(['gallery']);
        }
    }

    public function scopeWithCartResponse($query, $variant = false)
    {
        $this->setFilterOptions([], $variant ? 'cart.variants' : 'cart');

        $query->addSelect('products.*');
        // $query->select($this->getCartSelectColumns());

        $query->withProductModules();

        //If variants are not enabled in cart response, we need throw away relation
        $variantsIntoCart = array_filter(Store::variantsProductTypes(), function($key){
            return config('admineshop.product_types.'.$key.'.loadInCart', false) == true;
        });

        if ( count($variantsIntoCart) == 0 ){
            $query->without('variants');
        }
    }

    public function scopeWithMinAndMaxVariantPrices($query)
    {
        $query
            ->leftJoin('products as pv', 'pv.product_id', '=', 'products.id')
            ->whereNull('pv.deleted_at')
            ->addSelect(DB::raw('MIN(pv.price) as min_price, MAX(pv.price) as max_price'))
            ->groupBy('products.id');
    }

    public function scopeWithPriceLevels($query)
    {
        $query
            ->leftJoin('products_prices as pl', function($join){
                $join
                    ->on('pl.product_id', '=', 'products.id')
                    ->where('currency_id', Store::getCurrency()->getKey())
                    ->whereNotNull('pl.published_at')
                    ->whereNull('pl.deleted_at');
            })
            ->addSelect(DB::raw('
                COALESCE(pl.price, products.price) as price,
                COALESCE(pl.vat_id, products.vat_id) as vat_id,
                pl.currency_id
            '));
    }
}