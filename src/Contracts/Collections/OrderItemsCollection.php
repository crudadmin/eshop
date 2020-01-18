<?php

namespace AdminEshop\Contracts\Collections;

use AdminEshop\Contracts\Collections\CartCollection;
use Discounts;

class OrderItemsCollection extends CartCollection
{
    /**
     * Clone default prices from Item into assigned model in this item
     *
     * @var  string $key
     *
     * @return  this
     */
    public function rewritePricesInModels()
    {
        return $this->map(function($item) {
            //If OrderItem price is typed manually, we also need reset product price of item
            //to this manualy typed price. And turn off discounts on this price.
            //We also need rewrite tax value for calculating prices
            if ( $item->hasManualPrice ) {
                if ( $model = $item->getItemModel() ) {
                    $model->rewriteDefaultPrice($item->price);
                    $model->rewriteTaxValue($item->tax);
                }

                $item->rewriteTaxValue($item->tax);
            }

            //We need rewrite default price of OrdersItem property,
            //because if price of related product in OrdersItem would change,
            //this price may modify whole OrdersItem price with this new product price.
            //So we need remember old price of product from the time of order creation.
            else {
                if (
                    $model = $item->getItemModel()
                    && !is_null($price = $item->default_price)
                ) {
                    $item->getItemModel()->rewriteDefaultPrice($price);
                }

                //If price is dynamic, we need allow discounts on this item
                $this->allowItemDiscountsInAdmin($item);
            }

            return $item;
        });
    }

    /**
     * Set to each model that discounts can be applied also in administration
     *
     * @return  CartCollection
     */
    private function allowItemDiscountsInAdmin($item)
    {
        //We need apply discounts only on discountable items
        if ( Discounts::hasDiscountableTrait($item) ) {
            $item->setApplyDiscountsInAdmin(true);
        }

        //We also want apply cart item discounts on modelItems
        if ( Discounts::hasDiscountableTrait($model = $item->getItemModel()) ) {
            $model->setApplyDiscountsInAdmin(true);
        }
    }
}
