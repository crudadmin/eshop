<?php

namespace AdminEshop\Contracts;

use AdminEshop\Contracts\Cart\Concerns\HasOptionableDiscounts;
use AdminEshop\Contracts\Cart\Concerns\HasParentIdentifier;
use AdminEshop\Contracts\Cart\Concerns\HasPriceSupport;
use AdminEshop\Contracts\Cart\Identifiers\Concerns\IdentifierSupport;
use AdminEshop\Contracts\Cart\Identifiers\Concerns\UsesIdentifier;
use AdminEshop\Contracts\Cart\Identifiers\Identifier;
use AdminEshop\Contracts\Order\Concerns\HasOrderItemNames;
use AdminEshop\Eloquent\Concerns\HasStock;
use Cart;
use Illuminate\Database\Eloquent\Model;

class CartItem implements UsesIdentifier
{
    use IdentifierSupport,
        HasOptionableDiscounts,
        HasOrderItemNames,
        HasParentIdentifier,
        HasPriceSupport;

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
    public $quantity = 1;

    /**
     * Cart data
     *
     * @var  array|null
     */
    public $data;

    /**
     * Identifier
     *
     * @param  Identifier  $identifier
     * @param  int  $quantity
     * @param  mixed  $originalObject
     * @param  bool  $discounts
     */
    public function __construct(Identifier $identifier, $quantity = 1, $originalObject = null, bool $discounts = true)
    {
        $this->identifier = $identifier->getName();

        $this->setQuantity($quantity);

        $this->loadIdentifier($identifier);

        $this->setOriginalObject($originalObject);

        $this->discountableSupport = $discounts;
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
     * Returns quantity
     *
     * @return  int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Get value from cart
     *
     * @param  strin  $key
     * @return  mixed
     */
    public function getValue(string $key)
    {
        if ( property_exists($this, $key) ) {
            return $this->{$key};
        }
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
     *
     * @return  this
     */
    public function render($discounts)
    {
        $identifier = $this->getIdentifierClass();

        $identifierHash = $identifier->getIdentifierHash();

        //Modify cartItem from identifier
        if ( method_exists($identifier, 'onRender') ) {
            $identifier->onRender($this);
        }

        //Bind items models into cart object
        if ( isset($this->itemModels[$identifierHash]) ) {
            foreach ($this->itemModels[$identifierHash] as $key => $model) {
                //We can make hidden fields here, or append additional attributes...
                if ( $model instanceof Model && method_exists($model, 'setCartResponse') ){
                    $model->setCartResponse();
                }

                $this->{$key} = $model;
            }
        }

        return $this;
    }

    /**
     * This data will be stored into session/mysql
     *
     * @return  array
     */
    public function toArray() : array
    {
        $identifier = $this->getIdentifierClass();

        $array = [];

        foreach ($identifier->getIdentifyKeys() as $key => $data) {
            $array[$key] = $identifier->getIdentifier($key);
        }

        $item = [
            'identifier' => $this->identifier,
            'parentIdentifier' => $this->parentIdentifier,
            'quantity' => $this->quantity,
        ];

        //CartItem additional data
        if ( is_null($this->data) === false ){
            $item['data'] = $this->data;
        }

        return array_merge($array, $item);
    }

    /**
     * Set cart item additional data
     *
     * @param  mixed  $data
     * @param  mixed  $persist
     */
    public function setData($data = null, $persist = true)
    {
        $this->data = $data;

        //Save cart items
        if ( $persist === true ){
            Cart::saveItems();
        }

        return $this;
    }

    /**
     * Get CartItem additional data
     *
     * @return  mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Returns identifier hash
     *
     * @return  string
     */
    public function getKey()
    {
        return $this->getIdentifierClass()->getIdentifierHash();
    }
}

?>