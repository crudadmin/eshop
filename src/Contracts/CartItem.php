<?php

namespace AdminEshop\Contracts;

use AdminEshop\Contracts\Cart\Identifier;
use AdminEshop\Contracts\Concerns\HasIdentifierSupport;
use AdminEshop\Eloquent\Concerns\HasWarehouse;
use Cart;

class CartItem
{
    use HasIdentifierSupport;

    /**
     * Cart item identififer
     *
     * @var  string
     */
    public $identifier;

    /**
     * Quantity of item
     *
     * @var  int
     */
    public $quantity = 0;

    /**
     * Identifier
     *
     * @param  Identifier  $identifier
     * @param  int  $quantity
     */
    public function __construct(Identifier $identifier, $quantity = 0)
    {
        $this->identifier = $identifier->getName();

        $this->setQuantity($quantity);

        $this->loadIdentifier($identifier);
    }

    /**
     * Set quantity of cart item
     *
     * @param  int  $quantity
     */
    public function setQuantity(int $quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get value from cart
     *
     * @param  strin  $key
     * @return  mixed
     */
    public function getValue(string $key)
    {
        if ( property_exists($this, $key) )
            return $this->{$key};
    }

    /**
     * Set cart identifiers
     *
     * @param  string  $identifier
     *
     * @return  this
     */
    private function loadIdentifier(Identifier $identifier)
    {
        foreach ($identifier->getIdentifyKeys() as $key => $config) {
            $this->$key = $identifier->getIdentifier($key);
        }

        return $this;
    }

    /**
     * Add all item additional attributes
     *
     * @param  array|null  $discounts
     * @return  this
     */
    public function render($discounts)
    {
        $identifier = $this->getIdentifierClass();

        if ( method_exists($identifier, 'onRender') ) {
            $identifier->onRender($this);
        }

        //Bind items models into cart object
        foreach ($this->itemModels as $key => $model) {
            $this->{$key} = $model;
        }

        return $this;
    }
}

?>