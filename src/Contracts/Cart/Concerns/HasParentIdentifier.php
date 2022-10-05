<?php

namespace AdminEshop\Contracts\Cart\Concerns;

use AdminEshop\Contracts\CartItem;
use AdminEshop\Contracts\Cart\Identifiers\Identifier;
use Cart;

trait HasParentIdentifier
{
    /**
     * Apply discounts on given cart item
     *
     * @var  bool
     */
    public $parentIdentifier;


    /**
     * Assign this cart item into another cart item
     *
     * @param  Identifier  $parentItem
     *
     * @return  this
     */
    public function setParentIdentifier(Identifier $parentIdentifier)
    {
        $this->parentIdentifier = [
            'identifier' => $parentIdentifier->getName(),
            'data' => $parentIdentifier->getIdentifiers(),
        ];

        return $this;
    }

    /**
     * Has cart item enabled discounts?
     *
     * @return Identifier|null
     */
    public function getParentIdentifier()
    {
        return Cart::bootCartItemParentIdentifier(
            $this->parentIdentifier
        );
    }

    public function getParentCartItem()
    {
        if ( $parentIdentifier = $this->getParentIdentifier() ) {
            return Cart::getItem($parentIdentifier);
        }
    }

    /**
     * Check if given identifier is same as setted in parent identifier property
     *
     * @param  Identifier|null  $parentIdentifier
     * @return  bool
     */
    public function hasSameParentIdentifier(Identifier $parentIdentifier = null)
    {
        return $parentIdentifier == $this->getParentIdentifier();
    }

    /**
     * Check if actual cart item is parent to other child cart item
     *
     * @param  CartItem  $childCartItem
     * @return  bool
     */
    public function isParentOwner(CartItem $childCartItem)
    {
        return !$this->parentIdentifier && $childCartItem->hasSameParentIdentifier($this->getIdentifierClass());
    }

    /**
     * Determine if item is child of existing cart item
     *
     * @return  bool
     */
    public function isChildItem()
    {
        return $this->parentIdentifier ? true : false;
    }
}