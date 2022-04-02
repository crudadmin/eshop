<?php

namespace AdminEshop\Eloquent\Concerns;

use AdminEshop\Contracts\CartItem;
use AdminEshop\Models\Products\Product;
use Store;
use Admin;

trait OrderItemTrait
{
    /**
     * Return products with needed attributes
     *
     * @return  Collection
     */
    public function getAvailableProducts()
    {
        $productModel = Admin::getModel('Product');

        $products = $productModel->selectRaw('
                products.product_type,
                products.id, products.name, products.price,
                products.vat_id, products.discount_operator, products.discount,
                parentProduct.name as parent_product_name
            ')
            ->where(function($query){
                $query->where(function($query){
                    $query->variantProducts();
                })->orWhere(function($query){
                    $query->nonVariantProducts();
                });
            })
            ->leftJoin('products as parentProduct', function($join){
                $join->on('parentProduct.id', '=', 'products.product_id');
            })
            ->with(['attributesItems' => function($query){
                $query->withTextAttributes();
            }])
            ->get();

        return $products->map(function($product) use ($productModel) {
            $product->name = $product->name ?: $product->parent_product_name;

            if ( config('admineshop.attributes.attributesVariants', false) == true ) {
                $attributesText = $product->attributesVariantsText;
            } else if ( config('admineshop.attributes.attributesText', false) == true ) {
                $attributesText = $product->attributesText;
            } else {
                $attributesText = null;
            }

            //Extend name with attributes
            $product->name .= $attributesText ? ' - '.$attributesText : '';

            $product
                    ->setVisible(['id', 'name', 'priceWithVat', 'priceWithoutVat', 'vatValue', 'product_type'])
                    ->setAppends(['priceWithVat', 'priceWithoutVat', 'vatValue']);

            return $product;
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