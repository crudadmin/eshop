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
        })->when($variantsModel->hasAttributesEnabled() && config('admineshop.attributes.attributesText', false), function($query){
            $query->with(['attributesItems']);
        })->get();

        return $products->map(function($item) use ($variantsModel) {
            //Extend name with attributes
            if ( $variantsModel->hasAttributesEnabled() && config('admineshop.attributes.attributesText', false) ) {
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
                $query
                    ->with('attribute')
                    ->whereHas('attribute', function($query){
                        if ( config('admineshop.attributes.attributesText', false) ) {
                            $query->orWhere('product_info', 1);
                        }

                        if ( config('admineshop.attributes.attributesVariants', false) ) {
                            $query->orWhere('variants', 1);
                        }
                    });
            }])
            ->get();

        return $products->map(function($product) use ($productModel) {
            $product->name = $product->name ?: $product->parent_product_name;

            if ( config('admineshop.attributes.attributesVariants', false) == true ) {
                $attributesText = $product->attributesVariantsText;
            } else if ( config('admineshop.attributes.attributesText', false) == true ) {
                $attributesText = $product->attributesText;
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