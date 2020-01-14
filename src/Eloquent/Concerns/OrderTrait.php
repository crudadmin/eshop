<?php

namespace AdminEshop\Eloquent\Concerns;

use Admin;
use Ajax;
use Store;

trait OrderTrait
{
    /*
     * Recalculate order price
     */
    public function calculatePrices()
    {
        $price = 0;
        $priceWithTax = 0;

        foreach ($this->items as $item) {
            $price += $item->price * $item->quantity;
            $priceWithTax += $item->price_tax * $item->quantity;
        }

        $this->price = Store::roundNumber($price + $this->payment_method_price + $this->delivery_price);

        $this->price_tax = Store::roundNumber($priceWithTax + $this->paymentMethodPriceWithTax + $this->deliveryPriceWithTax);

        $this->save();
    }

    /*
     * Count down products from order in warehouse counts
     */
    public function countProductsFromWarehouse($add = false)
    {
        //Check product quantity
        if ( $add == false ) {
            foreach ($this->items as $item) {
                //If is product without relationship, just relative item
                if (!($product = $item->getProduct())) {
                    continue;
                }

                if ( Admin::isAdmin() && $product->warehouse_quantity - $item->quantity < 0 ) {
                    Ajax::warning('Produkt <strong>'.$product->name.'</strong> ma nedostačujúce množstvo ('.$product->warehouse_quantity.') pre odčítanie ('.$item->quantity.') produktov.');
                }
            }
        }

        //Uncount quantity
        foreach ($this->items as $item) {
            //If is product without relationship, just relative item
            if ( !($product = $item->getProduct()) ) {
                continue;
            }

            $product->warehouse_quantity -= ($item->quantity * ($add == true ? -1 : 1));
            $product->save();
        }
    }
}

?>