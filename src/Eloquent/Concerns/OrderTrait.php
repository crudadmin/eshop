<?php

namespace AdminEshop\Eloquent\Concerns;

use Admin;
use AdminEshop\Contracts\Collections\CartCollection;
use OrderService;
use Ajax;
use Cart;
use Store;

trait OrderTrait
{
    /**
     * Recalculate order price
     *
     * @return  void
     */
    public function calculatePrices()
    {
        $price = 0;
        $priceWithTax = 0;

        $items = (new CartCollection($this->items))
                    ->applyOnOrderCart()
                    ->allowApplyDiscountsInAdmin();

        //Set order into discounts factory
        OrderService::setOrder($this);
        OrderService::rebuildOrder($items);

        $this->save();
    }

    /**
     * Count down products from order in warehouse counts
     *
     * @param  string  $type '-' or '+'
     * @return  void
     */
    public function syncWarehouse($type, $message)
    {
        //Uncount quantity
        foreach ($this->items as $item) {
            //If is product without relationship, just relative item
            if ( !($product = $item->getProduct()) ) {
                continue;
            }

            $product->commitWarehouseChange($type, $item->quantity, $this->getKey(), $message);
        }
    }
}

?>