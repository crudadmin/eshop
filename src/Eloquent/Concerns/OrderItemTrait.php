<?php

namespace AdminEshop\Eloquent\Concerns;

use AdminEshop\Contracts\CartItem;
use AdminEshop\Models\Products\Product;
use AdminEshop\Models\Products\ProductsVariant;
use Store;

trait OrderItemTrait
{
    /*
     * Skip variants non orderable variants
     * Product can also have variants, bud this variants may not be orderable. We want skip this variants.
     *
     * @return Collection
     */
    public function getAvailableVariants()
    {
        return ProductsVariant::select(['id', 'product_id', 'name', 'price', 'vat_id', 'discount_operator', 'discount'])
                ->whereHas('product', function($query){
                    $query->whereIn('product_type', Store::filterConfig('orderableVariants', true));
                })->get()->map(function($item){
                    $item->setVisible(['id', 'product_id', 'name', 'priceWithVat', 'priceWithoutVat', 'vatValue'])
                         ->setAppends(['priceWithVat', 'priceWithoutVat', 'vatValue']);

                    return $item;
                });
    }

    /**
     * Return products with needed attributes
     *
     * @return  Collection
     */
    public function getAvailableProducts()
    {
        return Product::select(['id', 'name', 'price', 'vat_id', 'discount_operator', 'discount'])
                ->get()->map(function($item){
                    $item->setVisible(['id', 'name', 'priceWithVat', 'priceWithoutVat', 'vatValue'])
                         ->setAppends(['priceWithVat', 'priceWithoutVat', 'vatValue']);

                    return $item;
                });
    }

    /**
     * Returns if cart item has manual price
     *
     * @return  bool
     */
    public function getHasManualPriceAttribute()
    {
        return $this->manual_price === true;
    }

    /**
     * Returns cart item
     *
     * @return  AdminEshop\Contracts\CartItem|null
     */
    public function buildCartItem()
    {
        $identifier = $this->getIdentifierClass();

        return new CartItem($identifier, $this->quantity, $this);
    }
}