<?php

namespace AdminEshop\Eloquent\Concerns;

use AdminEshop\Events\StockChanged;
use AdminEshop\Models\Products\Product;
use AdminEshop\Models\Products\ProductsStocksLog;
use AdminEshop\Models\Products\ProductsVariant;
use Store;

trait HasStock
{
    /**
     * Avaiable stock attributes
     *
     * @var  array
     */
    protected $stockAttributes = [
        'stockText', 'hasStock', 'stockNumber',
    ];

    /**
     * Get all stock attributes
     *
     * @return  array
     */
    public function getStockAttributes()
    {
        return $this->stockAttributes;
    }

    /**
     * Add stock attribute
     *
     * @param  string|array  $attribute
     */
    public function addStockAttribute($attribute)
    {
        $this->stockAttributes = array_merge($this->stockAttributes, array_wrap($attribute));
    }

    /*
     * Get product stock type or value from global settings
     */
    public function getStockTypeAttribute($value)
    {
        //Overide default value by global settings
        if ( $value === 'default' ) {
            return Store::getSettings()->stock_type;
        }

        return $value;
    }

    /*
     * Get product value or value from global settings
     */
    public function getStockSoldAttribute($value)
    {
        //Overide default value by global settings
        if ( ! $value ) {
            return Store::getSettings()->stock_sold;
        }

        return $value;
    }

    public function getHasStockAttribute()
    {
        return $this->stock_quantity > 0;
    }

    public function getIsAvailableAttribute()
    {
        return $this->stock_quantity > 0 || $this->canOrderEverytime();
    }

    /*
     * Check if order can be ordered with zero quantity on stock
     */
    public function canOrderEverytime()
    {
        return $this->stock_type == 'everytime';
    }

    /*
     * Check if product can be ordered
     */
    public function getCanOrderAttribute()
    {
        if ( $this->isAvailable )
            return true;

        return false;
    }

    /**
     * Returns stock text value
     *
     * @return  string
     */
    public function getStockTextAttribute()
    {
        if ( $this->hasStock ) {
            if ( $this->canOrderEverytime() == false && config('admineshop.stock.status_with_quantity') === true ) {
                return sprintf(_('Skladom %sks'), $this->stockNumber);
            }

            return _('Skladom');
        }

        //If is custom message
        if ( $this->canOrderEverytime() && $this->stock_sold ) {
            return $this->stock_sold;
        }

        return _('Nie je skladom');
    }

    public function getStockNumberAttribute()
    {
        $stockText = $this->stock_quantity;

        $roundings = config('admineshop.stock.rounding', []);

        arsort($roundings);

        $roundings = array_values($roundings);

        $prevStock = null;
        foreach ($roundings as $onStock) {
            if ( $this->stock_quantity > $onStock ){
                //If is more than sentences limiter
                if ( ! $prevStock ){
                    return config('admineshop.stock.rounding_more_than_char', '>').$roundings[0];
                }

                return config('admineshop.stock.rounding_less_than_char', '<').$prevStock;
            }

            $prevStock = $onStock;
        }

        //If is less then lowest limit
        if ( count($roundings) > 0 ){
            return config('admineshop.stock.rounding_less_than_char', '<').$roundings[count($roundings) - 1];
        }

        return $stockText;
    }

    public function scopeOnStock($query)
    {
        /*
         * Limit stocks on variants
         */
        if ( $this instanceof ProductsVariant ) {
            $query->where('products.stock_type', '!=', 'hide')
                  ->orWhere('products_variants.stock_quantity', '>', 0);
        }

        /*
         * Limit stocks on product
         */
        else if ( $this instanceof Product ) {
            $query
                ->where('products.stock_type', '!=', 'hide')

                ->orWhere(function($query){
                    $query
                        ->whereHas('variants', function($query){
                            $query->where('products_variants.stock_quantity', '>', 0);
                        })

                        ->orWhere(function($query){
                            $query
                                ->doesntHave('variants')
                                ->where('products.stock_quantity', '>', 0);
                        });
                });
        }
    }

    /**
     * Commit product stock change
     *
     * @param  string  $type "+" or "-"
     * @param  int  $sub
     * @param  int  $orderId
     * @param  string|null  $message
     * @return void
     */
    public function commitStockChange($type, int $sub, $orderId, $message = null)
    {
        //Set sub on product type
        $sub = ($type == '-' ? $sub * -1 : $sub);

        $updatedStock = $this->stock_quantity + $sub;

        $this->stock_quantity = $updatedStock < 0 ? 0 : $updatedStock;
        $this->save();

        $stockLog = ProductsStocksLog::create([
            'order_id' => $orderId,
            'product_id' => $this instanceof Product ? $this->getKey() : $this->product_id,
            'variant_id' => $this instanceof ProductsVariant ? $this->getKey() : null,
            'sub' => $sub,
            'stock' => $this->stock_quantity,
            'message' => $message,
        ]);

        //Event for added discount code
        event(new StockChanged($this, $stockLog));
    }
}