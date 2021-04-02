<?php

namespace AdminEshop\Contracts\Cart\Identifiers\Concerns;

use AdminEshop\Contracts\Cart\Identifiers\DefaultIdentifier;
use AdminEshop\Contracts\Collections\CartCollection;
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
    private $classItemModelCloned = [];

    /**
     * Set eloquent of cart item
     *
     * @param  string  $modelKey
     * @param  AdminModel|null  $product
     */
    public function setItemModel($modelKey, $item)
    {
        //We need use identifier hashed, because if OrderItem changes product_id or variant_id
        //or any other instance of this type... So then we need recache itemModel,
        //we cannot return old variant or old product.
        $hash = $this->getIdentifierClass()->getIdentifierHash();

        //If identifier hash does not exists in model instance
        if ( array_key_exists($hash, $this->itemModels) === false ){
            $this->itemModels[$hash] = [];
        }

        $this->itemModels[$hash][$modelKey] = $item;

        return $this;
    }

    /**
     * Get eloquent of cart item by assigned identifier
     *
     * @param string $type
     * @param bool $cloned
     *
     * @return  mixed
     */
    public function getItemModel($type = null, $cloned = true)
    {
        $identifier = $this->getIdentifierClass();

        $identifierHash = $identifier->getIdentifierHash();

        $callback = function() use ($type, $identifier, $identifierHash) {
            //Item models has not been mounted yet
            if ( !array_key_exists($identifierHash, $this->itemModels) ) {
                $this->fetchSingleItemModel();
            }

            //Return mounted given model type
            if ( $type ) {
                $model = @$this->itemModels[$identifierHash][$type];
            }

            //Return default by identifier type
            else {
                //Return default
                $model = $identifier->getItemModel($this, $this->itemModels[$identifierHash] ?? null);
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
        };

        //We need cache item model of given cart,
        //because we need return cloned instance of given property/eloquent.
        //If returned model would not be cloned, it may rewrite prices in multiple
        //order item eloquent places. Also if product is in cart multiple times, we need manipulate only with instance
        //under this given cart item.
        return $this->cacheItemModel($identifierHash, $callback, $type, $cloned);
    }

    /**
     * Return non cloned item model
     *
     * @param  string|null  $type
     *
     * @return  AdminModel|null
     */
    public function getOriginalitemModel($type = null)
    {
        return $this->getItemModel($type, false);
    }

    public function fetchSingleItemModel()
    {
        //Set then itemModels has been set already
        $this->itemModels = [];

        //Bind fetched models
        (new CartCollection([
            $this
        ]))->fetchModels();
    }

    /**
     * Return cachable item model of given class instance
     *
     * @param  string  $identifierHash
     * @param  callable  $callback
     * @param  string|null  $type
     * @param  bool  $cloned
     * @return  mixed
     */
    private function cacheItemModel(string $identifierHash, callable $callback, $type = null, bool $cloned = true)
    {
        $type = $identifierHash.'_'.($type ?: '-');

        $cacheKey = $cloned == true ? 'classItemModelCloned' : 'classItemModel';

        if ( array_key_exists($type, $this->classItemModel) ) {
            return $this->{$cacheKey}[$type];
        }

        if ( $data = $callback() ) {
            $this->classItemModel[$type] = $data;
            $this->classItemModelCloned[$type] = clone $data;

            return $this->{$cacheKey}[$type];
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
