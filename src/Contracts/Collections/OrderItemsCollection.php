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
            //We also need rewrite tax value for calculating prices for given product
            if ( $item->hasManualPrice ) {
                if ( $model = $item->getItemModel() ) {
                    $model->rewriteDefaultPrice($item->price);
                    $model->rewriteTaxValue($item->tax);
                }
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

            //We also need to set tax for cart item.
            //Because prices calculation will be applied also for this items.
            $item->rewriteTaxValue($item->tax);

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

    /**
     * Push into item original object.
     * In this case item itself.
     */
    public function addOriginalObjects()
    {
        return $this->map(function($item){
            if ( ! $item->getOriginalObject() ) {
                $item->setOriginalObject($item);
            }

            return $item;
        });
    }
}
