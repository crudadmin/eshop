<?php

namespace AdminEshop\Contracts\Cart\Concerns;

use Cart;
use AdminEshop\Contracts\Cart\Identifiers\Identifier;

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

    /**
     * Check
     *
     * @param  Identifier|null  $parentIdentifier
     * @return  bool
     */
    public function hasSameParentIdentifier(Identifier $parentIdentifier = null)
    {
        return $parentIdentifier == $this->getParentIdentifier();
    }
}