<?php

namespace AdminEshop\Contracts\Collections;

use AdminEshop\Contracts\Collections\CartCollection;

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
            $item->restorePricesIntoItemModel();

            return $item;
        });
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
