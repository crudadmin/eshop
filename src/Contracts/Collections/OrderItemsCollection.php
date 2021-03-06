<?php

namespace AdminEshop\Contracts\Collections;

use AdminEshop\Contracts\Collections\CartCollection;
use Discounts;

class OrderItemsCollection extends CartCollection
{
    /**
     * Allow discounts by discount checkbox from orders_items table
     *
     * @return  this
     */
    public function setDiscountable()
    {
        return $this->map(function($item) {
            $item->setDiscounts($item->discountable);

            return $item;
        });
    }

    /**
     * Clone default prices from Item into assigned model in this item
     * And allow discounts on given models
     *
     * @var  string $key
     *
     * @return  this
     */
    public function rewritePricesInModels()
    {
        return $this->map(function($item) {
            //If OrderItem price is typed manually, we also need reset product price of item
            //to manualy typed price. And turn off discounts for this price.
            //We also need rewrite vat value for calculating prices for given product
            if ( $item->hasManualPrice ) {
                if ( $model = $item->getItemModel() ) {
                    $model->rewriteDefaultPrice($item->price);
                    $model->rewriteVatValue($item->vat);
                }
            }

            //We need to remember default price of OrdersItem property,
            //because when price of product will be changed, order may be modifier
            //by new price. This is wrong.
            //We also need check, if given identifier has discounts support. Because
            //this support may change, if identifier will be missing.
            else if ( $item->getIdentifierClass()->hasDiscounts() ) {
                if (
                    ($model = $item->getItemModel())
                    && !is_null($price = $item->default_price)
                ) {
                    $model->rewriteDefaultPrice($price);
                }

                //If price is dynamic, we need allow discounts on this item
                $this->allowItemDiscountsInAdmin($item);
            }

            //We also need to set vat for cart item.
            //Because prices calculation will be applied also for this items.
            $item->rewriteVatValue($item->vat);

            return $item;
        });
    }

    /**
     * Set to each model that discounts can be applied also in administration
     *
     * @return  CartCollection
     */
    public function allowItemDiscountsInAdmin($item)
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
