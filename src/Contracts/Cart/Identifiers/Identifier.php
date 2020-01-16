<?php

namespace AdminEshop\Contracts\Cart\Identifiers;

use AdminEshop\Contracts\CartItem;
use AdminEshop\Contracts\Cart\Identifiers\HasIdentifier;
use AdminEshop\Models\Orders\OrdersItem;

class Identifier implements HasIdentifier
{
    /**
     * Keys in array are assigned to eloquents tables
     *
     * @return  array
     */
    public static function getIdentifyKeys()
    {
        return [];
    }

    /**
     * Here will be binded identifiers
     *
     * @var  array
     */
    protected $identifiers = [];

    /**
     * Identifiers will be binded in same order as keys in getIdentifyKeys() method
     *
     * @param  [type]  $args
     */
    public function __construct(...$args)
    {
        $i = 0;
        foreach ($this->getIdentifyKeys() as $key => $options) {
            $this->setIdentifier($key, array_key_exists($i, $args) ? $args[$i] : null);
            $i++;
        }
    }

    /*
     * Retuns name of identifier
     */
    public function getName()
    {
        return class_basename(get_class($this));
    }

    /**
     * Get model by given cart type
     * If this method returns false instead of null
     * item without model will be valid and
     * wont be automatically removed from cart.
     *
     * @param  CartItem  $item
     * @return  Admin\Eloquent\AdminModel|CartItem|null
     */
    public function getItemModel($item, $cache)
    {
        // if ( $item->getValue('xyz_relation_id') ) {
        //     return $item->xyz_relation;
        // }
    }

    /**
     * Modify cart item on render
     *
     * @param  CartItem  $item
     * @return  [type]
     */
    public function onRender(CartItem $item)
    {

    }

    /**
     * Return identifier value
     *
     * @param  string  $key
     * @return  mixed
     */
    public function getIdentifier(string $key)
    {
        if ( array_key_exists($key, $this->identifiers) ) {
            return $this->identifiers[$key];
        }
    }

    /**
     * Clone data from identifier to item.
     *
     * @param  object  $item (here may be typed any type of cart item, product, without HasIdentifier.)
     * @return  this
     */
    public function cloneFormItem(object $item)
    {
        //Build identifier
        foreach ($this->getIdentifyKeys() as $key => $options) {
            $key = $this->tryOrderItemsColumn($key, $item);

            $this->setIdentifier($key, @$item->{$key} ?: null);
        }

        return $this;
    }

    /**
     * Set identifier value
     *
     * @param  string  $key
     * @param  mixed  $value
     */
    public function setIdentifier(string $key, $value)
    {
        $this->identifiers[$key] = $value;

        return $this;
    }

    /**
     * Returns if given cart belongs to this identifier
     *
     * @param  AdminEshop\Contracts\CartItem  $item
     * @return  bool
     */
    public function isThisCartItem(HasIdentifier $item)
    {
        foreach ($this->getIdentifyKeys() as $key => $options) {
            $identifierValue = $this->getIdentifier($key);

            if ( $identifierValue && $identifierValue != $this->getIdentifierValue($item, $key) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Try if given key has order_items_column
     *
     * @param  string  $key
     * @param  object  $item
     * @return string
     */
    public function tryOrderItemsColumn($key, object $item = null)
    {
        if ( $item instanceof OrdersItem ) {
            $options = @$this->getIdentifyKeys()[$key] ?: [];

            $key = @$options['orders_items_column'] ?: $key;
        }

        return $key;
    }

    /**
     * Returns identifier value by identifier name
     *
     * @param  HasIdentifier  $item
     * @param  string  $key
     * @return  mixed
     */
    public function getIdentifierValue(HasIdentifier $item, string $key)
    {
        $key = $this->tryOrderItemsColumn($key, $item);

        if ( property_exists($item, $key) || @$item->{$key} ) {
            return $item->{$key};
        }
    }
}

?>