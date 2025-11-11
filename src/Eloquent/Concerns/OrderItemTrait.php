<?php

namespace AdminEshop\Eloquent\Concerns;

use Discounts;
use Store;
use AdminEshop\Contracts\CartItem;
use Illuminate\Support\Facades\DB;

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
            ->when(Store::hasAttributes(), function($query){
                $query->with([
                    'attributesItems' => function($query){
                        $query->withTextAttributes();
                    }
                ]);
            });
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

    public function restorePricesIntoItemModel($model = null)
    {
        $model = $model ?: $this->getItemModel();

        //If OrderItem price is typed manually, we also need reset product price of item
        //to manualy typed price. And turn off discounts for this price.
        //We also need rewrite vat value for calculating prices for given product
        if ( $this->hasManualPrice ) {
            if ( $model ) {
                $model->rewriteDefaultPrice($this->price);
                $model->rewriteVatValue($this->vat);
            }
        }

        //We need to remember default price of OrdersItem property,
        //because when price of product may change, order may be modified what will be wrong.
        //We also need check, if given identifier has discounts support. Because
        //this support may change, if identifier will be missing.
        else {
            if ( $model && !is_null($price = $this->default_price) ) {
                $model->rewriteDefaultPrice($price);
            }

            //If price is dynamic, we need allow discounts on this item
            if ( $this->getIdentifierClass()->hasDiscounts() ) {
                $this->allowItemDiscountsInAdmin();
            }
        }

        //We also need to set vat for cart item.
        //Because prices calculation will be applied also for this items.
        $this->rewriteVatValue($this->vat);
    }

    /**
     * Set to each model that discounts can be applied also in administration
     *
     * @return  CartCollection
     */
    private function allowItemDiscountsInAdmin()
    {
        //We need apply discounts only on discountable items
        if ( Discounts::hasDiscountableTrait($this) ) {
            $this->setApplyDiscountsInAdmin(true);
        }

        //We also want apply cart item discounts on modelItems
        if ( Discounts::hasDiscountableTrait($model = $this->getItemModel()) ) {
            $model->setApplyDiscountsInAdmin(true);
        }
    }
}