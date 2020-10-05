<?php

namespace AdminEshop\Eloquent;

use AdminEshop\Contracts\CartItem;
use AdminEshop\Eloquent\Concerns\CanBeInCart;
use AdminEshop\Eloquent\Concerns\DiscountHelper;
use AdminEshop\Eloquent\Concerns\DiscountSupport;
use AdminEshop\Eloquent\Concerns\HasAttributesSupport;
use AdminEshop\Eloquent\Concerns\HasProductAttributes;
use AdminEshop\Eloquent\Concerns\PriceMutator;
use Admin\Eloquent\AdminModel;
use Cart;

class CartEloquent extends AdminModel implements CanBeInCart, DiscountSupport
{
    use PriceMutator,
        DiscountHelper;

    /**
     * Returns cart identifier classname of actual eloquent
     *
     * @return  string
     */
    public function getModelIdentifier()
    {
        return Cart::getIdentifierByClassName('ProductsIdentifier');
    }

    public function scopeCartSelect($query)
    {
        $query->select($this->fixAmbiguousColumn($this->cartSelect ?: []));

        //Add attributes support into cart
        if (
            config('admineshop.attributes.load_in_cart') === true
            && $query->getModel() instanceof HasAttributesSupport
            && $query->getModel()->hasAttributesEnabled()
        ) {
            $query->with(['attributesItems']);
        }

        return $query;
    }

    public function addCartSelect(array $columns = [])
    {
        $this->cartSelect = array_merge($this->cartSelect ?: [], $columns);
    }

    /**
     * Get initialized model identifier
     *
     * @return  AdminEshop\Contracts\Cart\Identifiers\Identifier
     */
    public function getIdentifier()
    {
        $identifierClass = $this->getModelIdentifier();

        return (new $identifierClass)->bootFromModel($this);
    }

    /**
     * Returns cart item
     *
     * @return  AdminEshop\Contracts\CartItem|null
     */
    public function buildCartItem()
    {
        $identifier = $this->getIdentifier();

        return Cart::getItem($identifier);
    }

    /**
     * We can set cart response... we can append() or make hidden fields in this method here
     *
     * void
     */
    public function setCartResponse()
    {

    }
}