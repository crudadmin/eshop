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
     */
    public function getItemModel()
    {
        $identifier = $this->getIdentifierClass();

        return $identifier->getItemModel($this, $this->itemModels);
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
