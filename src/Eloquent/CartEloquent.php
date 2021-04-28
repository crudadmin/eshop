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

    /*
     * Columns required for price calculation
     */
    protected $priceSelect = [
        'price', 'vat_id', 'discount_operator', 'discount',
    ];

    public function getPriceSelectColumns()
    {
        return array_unique($this->fixAmbiguousColumn($this->priceSelect ?: []));
    }

    public function getCartSelectColumns()
    {
        return array_unique($this->fixAmbiguousColumn(array_merge(
            $this->cartSelect ?: [],
            $this->priceSelect ?: [],
        )));
    }

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
        $query->withCartResponse();

        return $query;
    }

    public function addCartSelect(array $columns = [])
    {
        $this->cartSelect = array_merge($this->cartSelect ?: [], $columns);
    }

    public function addPriceSelect(array $columns = [])
    {
        $this->priceSelect = array_merge($this->priceSelect ?: [], $columns);
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
}