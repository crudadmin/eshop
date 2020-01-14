<?php

namespace AdminEshop\Eloquent\Concerns;

use AdminEshop\Models\Products\Product;
use AdminEshop\Models\Products\ProductsVariant;

trait HasWarehouse
{
    public function getHasStockAttribute()
    {
        return $this->warehouse_quantity > 0;
    }

    public function getIsAvailableAttribute()
    {
        return $this->warehouse_quantity > 0 || $this->canOrderEverytime();
    }

    /*
     * Check if order can be ordered with zero quantity on warehouse
     */
    public function canOrderEverytime()
    {
        return $this->warehouse_type == 'everytime';
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

    public function getStockTextAttribute()
    {
        if ( $this->hasStock ) {
            if ( $this->canOrderEverytime() == false ) {
                $stockText = $this->warehouse_quantity;

                foreach ([100, 50, 20, 10] as $onStock) {
                    if ( $this->warehouse_quantity > $onStock ){
                        return sprintf(_('Skladom >%sks'), $stockText);
                    }
                }

                return sprintf(_('Skladom %sks'), $stockText);
            }

            return _('Skladom');
        }

        if ( $this->canOrderEverytime() && $this->warehouse_sold ) {
            return $this->warehouse_sold;
        }

        return _('Nie je skladom');
    }

    public function scopeOnStock($query)
    {
        /*
         * Limit stocks on variants
         */
        if ( $this instanceof ProductsVariant ) {
            $query->where('products.warehouse_type', '!=', 'hide')
                  ->orWhere('products_variants.warehouse_quantity', '>', 0);
        }

        /*
         * Limit stocks on product
         */
        else if ( $this instanceof Product ) {
            $query
                ->where('products.warehouse_type', '!=', 'hide')

                ->orWhere(function($query){
                    $query
                        ->whereHas('variants', function($query){
                            $query->where('products_variants.warehouse_quantity', '>', 0);
                        })

                        ->orWhere(function($query){
                            $query
                                ->doesntHave('variants')
                                ->where('products.warehouse_quantity', '>', 0);
                        });
                });
        }
    }
}