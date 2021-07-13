<?php

namespace AdminEshop\Eloquent\Concerns;

use Admin;
use AdminEshop\Eloquent\Concerns\HasAttributesSupport;
use AdminEshop\Models\Products\Product;
use Admin\Eloquent\Modules\SeoModule;
use Illuminate\Support\Facades\DB;
use Store;

trait HasProductResponses
{
    static $filterOptions = [
        'filter' => [],
        'scope' => null,
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

    public function scopeSetFilterOptions($query, $options)
    {
        self::$filterOptions = array_merge(self::$filterOptions, $options);
    }

    public function getFilterOption($key, $default = null)
    {
        $value = self::$filterOptions[$key] ?? null;

        return is_null($value) ? $default : $value;
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
    }

    public function setVariantListingResponse()
    {
        $this->setListingResponse();
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

    /**
     * Display products into category response
     * Also filter products and variants
     *
     * @param  Builder  $query
     * @param  array    $options
     */
    public function scopeWithListingResponse($query)
    {
        //We need specify select for
        $query->addSelect('products.*');

        $query->applyQueryFilter();

        $query->sortByParams();

        $query->withProductModules('listing');
    }

    public function scopeWithDetailResponse($query)
    {
        //We need specify select for
        $query->addSelect('products.*');

        $query->withProductModules('detail');

    }

    public function scopeWithProductModules($query, $key, $variants = false)
    {
        $query->withMainGalleryImage($variants ? true : false);

        if ( $this->getFilterOption($key.'.price_ranges', false) === true ) {
            $query->withMinAndMaxVariantPrices();
        }

        if (
            $variants === false
            && $this->getFilterOption($key.'.variants.extract', false) === false
            && $this->getFilterOption($key.'.variants', true) === true
            && count(Store::variantsProductTypes())
        ){
            $query->extendWith(['variants' => function($query) use ($key) {
                $model = $query->getModel();

                $query->select('products.*');

                $query->withParentProductData();

                //We can deside if filter should be applied also on selected variants
                if ( $this->getFilterOption($key.'.variants.filter', false) ) {
                    $query->filterProduct(
                        $this->getFilterOption('filter')
                    );
                }

                $query->withProductModules($key.'.variants', true);
            }]);
        }

        if ( $this->getFilterOption($key.'.attributes', false) === true ) {
            $query->with([
                'attributesItems' => function($query){
                    $query->with(['attribute' => function($query){
                        $query->select($query->getModel()->getAttributesColumns());
                    }]);
                }
            ]);
        }

        if ( $this->getFilterOption($key.'.gallery', false) === true ) {
            $query->with(['gallery']);
        }
    }

    public function scopeWithCartResponse($query, $variant = false)
    {
        $query->addSelect('products.*');
        // $query->select($this->getCartSelectColumns());

        $query->withProductModules($variant ? 'cart.variants' : 'cart');


        //If variants are not enabled in cart response, we need throw away relation
        $variantsIntoCart = array_filter(Store::variantsProductTypes(), function($key){
            return config('admineshop.product_types.'.$key.'.loadInCart', false) == true;
        });

        if ( count($variantsIntoCart) == 0 ){
            $query->without('variants');
        }
    }

    public function scopeSortByParams($query)
    {
        $filterParams = $this->getFilterOption('filter');
        $extractVariants = $this->getFilterOption('listing.extract');

        if ( !($sortBy = $filterParams['_sort'] ?? null) ){
            return;
        }

        if ( in_array($sortBy, ['expensive', 'cheapest', 'latest']) ) {
            $expensive = $sortBy == 'expensive';
            $latest = $sortBy == 'latest';
            $agregatedColumn = $latest == true ? 'products.id' : 'products.price';

            $isDesc = $expensive || $latest;

            //Enabled variant extraction
            if ( $extractVariants === false ) {
                $variantsPrices = DB::table('products')
                                    ->selectRaw(($isDesc ? 'max' : 'min').'('.$agregatedColumn.') as aggregator, product_id')
                                    ->where('product_type', 'variant')
                                    ->groupBy('product_id');

                $query
                    ->leftJoinSub($variantsPrices, 'pricedVariants', function($join){
                        $join->on('products.id', '=', 'pricedVariants.product_id');
                    })
                    ->addSelect(DB::raw('IFNULL(pricedVariants.aggregator, '.$agregatedColumn.') as aggregator'));
            } else {
                $query->addSelect(DB::raw($agregatedColumn.' as aggregator'));
            }

            $query->orderBy('aggregator', $isDesc ? 'DESC' : 'ASC');
        }
    }

    public function scopeWithMinAndMaxVariantPrices($query)
    {
        $query->leftJoin('products as pv', 'pv.product_id', '=', 'products.id');

        $query->addSelect(DB::raw('MIN(pv.price) as min_price, MAX(pv.price) as max_price'));

        $query->groupBy('products.id');
    }
}