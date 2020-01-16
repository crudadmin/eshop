<?php

namespace AdminEshop\Eloquent\Concerns;

use Admin;
use AdminEshop\Contracts\Collections\CartCollection;
use Ajax;
use Cart;
use Discounts;
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

        //Set order into discounts factory
        Discounts::setOrder($this);

        $items = (new CartCollection($this->items))->applyOnOrderCart();

        foreach ($items as $item) {
            $price += $item->priceWithoutTax * $item->quantity;
            $priceWithTax += $item->priceWithTax * $item->quantity;
        }

        $this->price = Store::roundNumber($price + $this->payment_method_price + $this->delivery_price);

        $this->price_tax = Store::roundNumber($priceWithTax + $this->paymentMethodPriceWithTax + $this->deliveryPriceWithTax);

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