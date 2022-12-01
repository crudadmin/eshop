<?php

namespace AdminEshop\Eloquent\Concerns;

use AdminEshop\Events\StockChanged;
use AdminEshop\Models\Products\Product;
use AdminEshop\Models\Products\ProductsStocksLog;
use AdminEshop\Models\Store\CartStockBlock;
use Store;
use Cart;

trait HasStock
{
    /**
     * Avaiable stock attributes
     *
     * @var  array
     */
    protected $stockAttributes = [
        'stockText', 'hasStock', 'stockNumber', 'canOrderEverytime'
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
     * Is product on stock
     */
    public function getHasStockAttribute()
    {
          return $this->stock_quantity > 0;
    }

    /*
     * Check if order can be ordered with zero quantity on stock
     */
    public function getCanOrderEverytimeAttribute()
    {
        return $this->stock_type == 'everytime' ;
    }

    /**
     * Returns text if product is on stock
     *
     * @return  string
     */
    public function getOnStockTextAttribute()
    {
        if ( $this->canOrderEverytime == false && config('admineshop.stock.status_with_quantity') === true ) {
            return sprintf(_('Skladom %sks'), $this->stockNumber);
        }

        return _('Skladom');
    }

    /**
     * Returns text if product is not on stock
     *
     * @return  string
     */
    public function getOffStockTextAttribute()
    {
        //If is custom message for sold product which are able to order anytime.
        if ( $this->canOrderEverytime && $soldText = $this->soldStockToSale ) {
            return $soldText;
        }

        return _('Nie je skladom');
    }

    /*
     * Get product value or value from global settings of sold product which is able to order
     */
    public function getSoldStockToSaleAttribute($value)
    {
        $stockSoldText = $this->stock_sold;

        //Overide default value by global settings
        if ( ! $value ) {
            $settings = Store::getSettings();

            if ( $settings->stock_type == 'everytime' ) {
                return Store::getSettings()->stock_sold;
            }
        }

        return $stockSoldText;
    }

    /**
     * Returns stock text value
     *
     * @return  string
     */
    public function getStockTextAttribute()
    {
        if ( $this->hasStock ) {
            return $this->onStockText;
        }

        return $this->offStockText;
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
         * TODO: check rule
         */
        // if ( $this instanceof ProductsVariant ) {
        //     $query->where('products.stock_type', '!=', 'hide')
        //           ->orWhere('products.stock_quantity', '>', 0);
        // }

        /*
         * Limit stocks on product
         */
        if ( $this instanceof Product ) {
            $query
                ->where('products.stock_type', '!=', 'hide')

                ->orWhere(function($query){
                    $query
                        ->whereHas('variants', function($query){
                            $query->where('products.stock_quantity', '>', 0);
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
    public function commitStockChange($type, int $sub, $orderId, $message = null, $logId = null)
    {
        //Set sub on product type
        $sub = ($type == '-' ? $sub * -1 : $sub);

        $uncastedStockQuantity = $this->attributes['stock_quantity'] ?? 0;

        //Stock can be onli positive
        $newStockQuantity = max(0, $uncastedStockQuantity + $sub);

        $this->stock_quantity = $newStockQuantity;
        $this->save();

        $stockLog = ProductsStocksLog::create([
            'order_id' => $orderId,
            //TODO: check correct variant ID is pushed here
            'product_id' => $this instanceof Product ? $this->getKey() : $this->product_id,
            'sub' => $sub,
            'stock' => $newStockQuantity,
            'message' => $message,
            'log_id' => $logId,
        ]);

        //Event for added discount code
        event(new StockChanged($this, $stockLog));
    }

    public function blockedItems()
    {
        $blockedMinutage = config('admineshop.stock.temporary_block_time', 0);

        return $this->hasMany(CartStockBlock::class, $this instanceof ProductsVariant ? 'variant_id' : 'product_id')
                    ->where(function($query){
                        foreach (Cart::getStockBlockIdentifier() as $key => $value) {
                            $query->where($key, '!=', $value);
                        }
                    })
                    ->where('blocked_at', '>=', Carbon::now()->addMinutes(-$blockedMinutage));
    }

    public function scopeWithBlockedStock($query)
    {
        if ( Cart::isStockBlockEnabled() === false ){
            return;
        }

        $query->withSum('blockedItems', 'quantity');
    }

    public function getStockQuantityAttribute($value)
    {
        if ( Cart::isStockBlockEnabled() == false ){
            return $value;
        }

        $blockedStock = (int)($this->getAttribute('blocked_items_sum_quantity') ?: 0);

        return max(0, $value - $blockedStock);
    }
}