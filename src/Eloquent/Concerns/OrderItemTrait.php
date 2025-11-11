<?php

namespace AdminEshop\Eloquent\Concerns;

use Admin;
use Store;
use Localization;
use AdminEshop\Contracts\CartItem;
use Illuminate\Support\Facades\DB;
use AdminEshop\Models\Products\Product;

trait OrderItemTrait
{
    /**
     * Return products with needed attributes
     *
     * @return  Collection
     */
    public function scopeProductIdOption($query)
    {
        $query
            ->addSelect(DB::raw('
                products.product_type,
                products.id, products.name, products.price,
                products.vat_id, products.discount_operator, products.discount,
                parentProduct.name as parent_product_name
            '))
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
            ->with([
                'attributesItems' => function($query){
                    $query->withTextAttributes();
                }
            ]);
    }

    public function setProductIdOption($option)
    {
        $option = $option
            ->setVisible(['id', 'name', 'priceWithVat', 'priceWithoutVat', 'vatValue', 'product_type'])
            ->setAppends([
                'priceWithVat',
                'priceWithoutVat',
                'vatValue'
            ]);


        if ( config('admin_eshop.attributes.attributesVariants', false) == true ) {
            $attributesText = $option->attributesVariantsText;
        } else if ( config('admin_eshop.attributes.attributesText', false) == true ) {
            $attributesText = $option->attributesText;
        } else {
            $attributesText = null;
        }

        $name = ($option->getValue('name') ?: $option->getValue('parent_product_name')) ?: '';
        $name .= $attributesText ? ' - '.$attributesText : '';

        return [
            'name' => $name,
        ] + $option->toArray();
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