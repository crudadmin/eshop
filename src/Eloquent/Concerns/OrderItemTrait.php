<?php

namespace AdminEshop\Eloquent\Concerns;

use AdminEshop\Contracts\CartItem;
use AdminEshop\Models\Products\Product;
use AdminEshop\Models\Products\ProductsVariant;
use Store;
use Admin;

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
        $variantsModel = Admin::getModel('ProductsVariant');

        $products = $variantsModel->select([
            'id', 'product_id', 'name', 'price', 'vat_id', 'discount_operator', 'discount'
        ])->whereHas('product', function($query){
            $query->whereIn('product_type', Store::filterConfig('orderableVariants', true));
        })->when($variantsModel->hasAttributesEnabled(), function($query){
            $query->with(['attributesItems']);
        })->get();

        return $products->map(function($item) use ($variantsModel) {
            //Extend name with attributes
            if ( $variantsModel->hasAttributesEnabled() ) {
                $item->name .= $item->attributesText ? ' - '.$item->attributesText : '';
            }

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
        $productModel = Admin::getModel('Product');

        $products = $productModel->select([
            'id', 'name', 'price', 'vat_id', 'discount_operator', 'discount'
        ])->when($productModel->hasAttributesEnabled(), function($query){
            $query->with(['attributesItems']);
        })->get();

        return $products->map(function($item) use ($productModel) {
            //Extend name with attributes
            if ( $productModel->hasAttributesEnabled() ) {
                $item->name .= $item->attributesText ? ' - '.$item->attributesText : '';
            }

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