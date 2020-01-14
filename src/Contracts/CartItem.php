<?php

namespace AdminEshop\Contracts;

use Cart;

class CartItem
{
    public $id = null;

    public $variant_id = null;

    public $quantity = 0;

    public $product;

    public $variant;

    /**
     * Create new cart item
     *
     * @param  int  $id
     * @param  int  $quantity
     * @param  int|null  $variantId
     */
    public function __construct(int $id, int $quantity, $variantId = null)
    {
        $this->id = $id;

        $this->quantity = $quantity;

        $this->variant_id = $variantId;
    }

    /**
     * Set product of item
     *
     * @param  AdminModel  $product
     */
    public function setProduct($product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Set variant of item
     *
     * @param  AdminModel  $product
     */
    public function setVariant($variant)
    {
        $this->variant = $variant;

        return $this;
    }

    /**
     * Returns variant or product of item
     *
     * @return  AdminModel
     */
    public function getItemProduct()
    {
        return @$this->variant ?: $this->product;
    }

    /**
     * Add fetched product and variant into cart item
     *
     * @param  array|null  $discounts
     * @return object
     */
    public function fetchItemModels($discounts = null)
    {
        $this->setProduct(Cart::getFetchedProducts()->find($this->id));

        if ( isset($this->variant_id) ) {
            $this->setVariant(Cart::getFetchedVariants()->find($this->variant_id));
        }

        Cart::addCartDiscountsIntoModel($this->getItemProduct(), $discounts);

        return $this;
    }

    /**
     * Returns if product in cart item is on stock
     *
     * @return  bool
     */
    public function hasQuantityOnStock()
    {
        //We can skip checking products which all allowed all the time
        if ( $this->getItemProduct()->canOrderEverytime() ) {
            return true;
        }

        //Check if quantity in cart is lower that quantity on stock
        return $this->quantity <= $this->getItemProduct()->warehouse_quantity;
    }

    /**
     * Add all item additional attributes
     *
     * @param  array|null  $discounts
     * @return  this
     */
    public function render($discounts)
    {
        $this->fetchItemModels($discounts);

        $this->hasQuantityOnStock = $this->hasQuantityOnStock();

        return $this;
    }
}

?>