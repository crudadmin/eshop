<?php

namespace AdminEshop\Eloquent\Concerns;

use AdminEshop\Contracts\CartItem;
use AdminEshop\Models\Products\Product;
use Localization;
use Store;
use Admin;

trait OrderItemTrait
{
    protected $attributesLimitLoad = 3000;

    /**
     * Return products with needed attributes
     *
     * @return  Collection
     */
    public function getAvailableProducts()
    {
        $productModel = Admin::getModel('Product');

        $productsQuery = $productModel->selectRaw('
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
            });

        $products = $productsQuery
            ->leftJoin('products as parentProduct', function($join){
                $join->on('parentProduct.id', '=', 'products.product_id');
            })

            //CHECK attributesLimitLoad attribute, attributes may not be available after
            //certain limit of products
            ->when($productsQuery->count() < $this->attributesLimitLoad, function($query){
                $query->with([
                    'attributesItems' => function($query){
                        $query->withTextAttributes();
                    }
                ]);
           })
            ->get();

        return $products->map(function($product) use ($productModel) {
            if ( config('admineshop.attributes.attributesVariants', false) == true ) {
                $attributesText = $product->attributesVariantsText;
            } else if ( config('admineshop.attributes.attributesText', false) == true ) {
                $attributesText = $product->attributesText;
            } else {
                $attributesText = null;
            }

            $name = ($product->getValue('name') ?: $product->getValue('parent_product_name')) ?: '';
            $name .= $attributesText ? ' - '.$attributesText : '';
            if ( $product->hasFieldParam('name', 'locale') ) {
                $name = [Localization::getLocale() => $name];
            }

            $product->setAttribute('name', $name);

            $product
                    ->setVisible(['id', 'name', 'priceWithVat', 'priceWithoutVat', 'vatValue', 'product_type'])
                    ->setAppends([
                        'priceWithVat',
                        'priceWithoutVat',
                        'vatValue'
                    ]);

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