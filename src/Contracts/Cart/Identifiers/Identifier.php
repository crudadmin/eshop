<?php

namespace AdminEshop\Contracts\Cart\Identifiers;

use AdminEshop\Contracts\CartItem;
use AdminEshop\Contracts\Cart\Identifiers\Concerns\UsesIdentifier;
use AdminEshop\Models\Orders\OrdersItem;
use Store;

class Identifier
{
    /**
     * Here will be binded identifiers data
     * key and values. Which are identifiers of given item.
     *
     * @var  array
     */
    protected $identifiers = [];

    /**
     * Identifiers will be binded in same order as keys in getIdentifyKeys() method
     *
     * @param  array  $args
     */
    public function __construct(...$args)
    {
        if ( count($args) > 0 ) {
            $this->bindInKeysOrder(...$args);
        }
    }

    /**
     * Return identifiers keys and values
     *
     * @return  array
     */
    public function getIdentifiers()
    {
        return $this->identifiers;
    }

    /**
     * Bind values of given keys from identifier configuration
     *
     * @param  array  $args
     * @return  [type]
     */
    public function bindInKeysOrder(...$args)
    {
        $i = 0;
        foreach ($this->getIdentifyKeys() as $key => $options) {
            $this->setIdentifier($key, array_key_exists($i, $args) ? $args[$i] : null);
            $i++;
        }

        return $this;
    }

    /**
     * Keys in array are assigned to eloquents tables
     *
     * @return  array
     */
    public static function getIdentifyKeys()
    {
        return [];
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
     * Modify item on cart items render into website
     *
     * @param  CartItem  $item
     * @return  void
     */
    public function onRender(CartItem $item)
    {

    }

    /**
     * Can discounts be applied on item with this identifier?
     *
     * @return  bool
     */
    public function hasDiscounts()
    {
        return false;
    }

    /**
     * Can be this sum skipped in summary?
     *
     * @return  bool
     */
    public function skipInSummary()
    {
        return false;
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
     * Clone data from identifier to item.
     *
     * @param  object  $item (here may be typed any type of cart item, product, without UsesIdentifier.)
     * @return  this
     */
    public function cloneFromItem(object $item)
    {
        //Build identifier
        foreach ($this->getIdentifyKeys() as $identifierKey => $options) {
            $key = $this->tryOrderItemsColumn($identifierKey, $item);

            $this->setIdentifier($identifierKey, @$item->{$key} ?: null);
        }

        return $this;
    }

    /**
     * Returns if given cart belongs to this identifier
     *
     * @param  UsesIdentifier  $item
     * @return  bool
     */
    public function hasThisItem(UsesIdentifier $item)
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
     * @param  arrat  $item
     * @return string
     */
    public function tryOrderItemsColumn($key, $item = null)
    {
        if ( $item instanceof OrdersItem ) {
            $options = $this->getIdentifyKeys()[$key] ?? [];

            $key = $options['orders_items_column'] ?? $key;
        }

        return $key;
    }

    public function getOrderItemsColumns()
    {
        $columns = [];

        foreach ($this->getIdentifyKeys() as $key => $options) {
            $orderItemKey = $options['orders_items_column'] ?? $key;

            $columns[$orderItemKey] = $this->getIdentifier($key);
        }

        return $columns;
    }

    /**
     * Returns identifier value by identifier name
     *
     * @param  UsesIdentifier  $item
     * @param  string  $key
     * @return  mixed
     */
    public function getIdentifierValue(UsesIdentifier $item, string $key)
    {
        $key = $this->tryOrderItemsColumn($key, $item);

        if ( $value = ($item->{$key} ?? null) ) {
            return $value;
        }
    }

    /**
     * Add joins when fetching row from db...
     *
     * @param  Builder  $query
     * @return  Builder
     */
    public function onFetchItems($query)
    {
        return $query;
    }

    /**
     * Returns identifier hash key
     *
     * @return  string
     */
    public function getIdentifierHash()
    {
        $string = [];

        foreach ($this->identifiers as $key => $value) {
            $string[] = $key.'_'.(is_string($value) || is_numeric($value) ? $value : '-');
        }

        return 'identifier_'.implode(';', $string);
    }

    /**
     * Returns all product name information in array
     *
     * @return  array
     */
    public function getProductNameParts(UsesIdentifier $item) : array
    {
        return [];
    }

    public function onOrderItemCreate(CartItem $item)
    {
        $product = $item->getItemModel();

        $data = [
            'identifier' => $this->getName(),
            'discountable' => $item->hasDiscounts(),
            'quantity' => $item->quantity,
            'default_price' => $product->defaultPriceWithoutVat,
            'price' => $product->priceWithoutVat,
            'vat' => Store::getVatValueById($product->vat_id),
            'price_vat' => $product->priceWithVat,
        ];

        foreach ($this->getIdentifyKeys() as $key => $options) {
            if ( ($options['orders_items_column'] ?? null) === false ){
                continue;
            }

            $orderItemKey = $options['orders_items_column'] ?? $key;

            $identifierValue = $this->getIdentifierValue($item, $key);

            $data[$orderItemKey] = $identifierValue;
        }

        //We can mutate order item from product model
        if ( method_exists($product, 'onOrderItemCreate') ){
            $data = $product->onOrderItemCreate($data, $item);
        }

        return $data;
    }

    /*
     * Returns price of assigned model in CartItem
     */
    public function getPricesArray(UsesIdentifier $item, $discounts = null)
    {
        $array = [];

        //Add all attributes from model which consits of price name in key
        if ( $model = $item->getItemModel() ) {
            foreach ($model->toCartArray($discounts) as $key => $price) {
                //If does not have price in attribute name
                if ( strpos(strtolower($key), 'price') === false ) {
                    continue;
                }

                $array[$key] = $price;
            }
        }

        return $array;
    }

    /**
     * Returns item price
     *
     * @param CartItem  $item
     * @param string    $priceKey
     *
     * @return  decimal
     */
    public function getPrice(CartItem $item, $priceKey = 'priceWithVat')
    {
        if ( $model = $item->getItemModel() ) {
            return $model->getAttribute($priceKey);
        }

        return 0;
    }
}

?>