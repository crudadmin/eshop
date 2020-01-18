<?php

namespace AdminEshop\Contracts\Cart\Identifiers\Concerns;

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
        $identifier = Cart::getIdentifierByName($this->identifier);

        $identifier->cloneFormItem($this);

        return $identifier;
    }

    /**
     * Returns array of available prices in cartItem
     *
     * @return  array
     */
    public function getPricesArray()
    {
        $array = [];

        //Add all attributes from model which consits of price name in key
        if ( $model = $this->getItemModel() ) {
            foreach ($model->toCartArray() as $key => $price) {
                //If does not have price in attribute name
                if ( strpos(strtolower($key), 'price') === false ) {
                    continue;
                }

                $array[$key] = $price;
            }
        }

        return $array;
    }
}
