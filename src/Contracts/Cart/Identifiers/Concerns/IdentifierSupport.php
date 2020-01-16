<?php

namespace AdminEshop\Contracts\Cart\Identifiers\Concerns;

use Cart;

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
        $identifier = Cart::getCartItemIdentifier($this->identifier);

        $identifier->cloneFormItem($this);

        return $identifier;
    }
}
