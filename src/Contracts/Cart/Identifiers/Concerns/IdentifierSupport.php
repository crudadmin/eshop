<?php

namespace AdminEshop\Contracts\Cart\Identifiers\Concerns;

use AdminEshop\Contracts\Cart\Identifiers\DefaultIdentifier;
use Cart;
use Discounts;

trait IdentifierSupport
{
    /**
     * Here will be saved items models when cart item will be eloquent
     *
     * @var  array
     */
    protected $itemModels = [];

    /**
     * Previous object from which has been created this CartItem
     *
     * @var  mixed
     */
    private $originalObject;

    /*
     * Cached item model for given class instance
     */
    private $classItemModel = [];

    /**
     * Set eloquent of cart item
     *
     * @param  string  $modelKey
     * @param  AdminModel|null  $product
     */
    public function setItemModel($modelKey, $item)
    {
        $this->itemModels[$modelKey] = $item;

        return $this;
    }

    /**
     * Get eloquent of cart item by assigned identifier
     *
     * @var string $type
     *
     * @return  mixed
     */
    public function getItemModel($type = null)
    {
        //We need cache item model of given cart,
        //because we need return cloned instance of given property/eloquent.
        //If returned model would not be cloned, it may rewrite prices in multiple
        //places. Also if product is in cart multiple times, we need manipulate only with instance
        //under this given cart item.
        return $this->cacheItemModel($type, function() use ($type) {
            $identifier = $this->getIdentifierClass();

            //Return given type
            if ( $type ) {
                $model = @$this->itemModels[$type];
            } else {
                //Return default
                $model = $identifier->getItemModel($this, $this->itemModels);
            }

            if ( $model ){
                //We need set actual cart item into model.
                //Because we can not retrieve item from cart in this case.
                //In admin cart is not available, so we need bing cart item into model.
                if ( method_exists($this, 'getCartItem') ){
                    $model->setCartItem($this->getCartItem());
                } else {
                    $model->setCartItem($this);
                }
            }

            return $model;
        });
    }

    /**
     * Return cachable item model of given class instance
     *
     * @param  string|null  $type
     * @param  callable  $callback
     * @return  mixed
     */
    private function cacheItemModel($type = null, callable $callback)
    {
        $type = $type ?: '-';

        if ( array_key_exists($type, $this->classItemModel) ) {
            return $this->classItemModel[$type];
        }

        if ( $data = $callback() ) {
            return $this->classItemModel[$type] = clone $data;
        }
    }

    /**
     * Returns identifier class
     *
     * @return  Identifier
     */
    public function getIdentifierClass()
    {
        $identifier = Cart::getIdentifierByName($this->identifier) ?: new DefaultIdentifier;

        $identifier->cloneFormItem($this);

        return $identifier;
    }

    /**
     * Returns array of available prices in cartItem
     *
     * @var  array $discounts
     *
     * @return  array
     */
    public function getPricesArray($discounts = null)
    {
        $array = [];

        //This prices wont be added into summary
        if ( $this->getIdentifierClass()->skipInSummary() === true ) {
            return [];
        }

        //Add all attributes from model which consits of price name in key
        if ( $model = $this->getItemModel() ) {
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
     * Returns original object
     *
     * @return  mixed
     */
    public function getOriginalObject()
    {
        return $this->originalObject;
    }

    /**
     * Saves original object
     *
     * @param  mixed  $object
     */
    public function setOriginalObject($object)
    {
        $this->originalObject = $object;

        return $this;
    }
}
