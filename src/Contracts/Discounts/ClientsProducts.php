<?php

namespace AdminEshop\Contracts\Discounts;

use Admin;
use AdminEshop\Contracts\Collections\CartCollection;
use AdminEshop\Contracts\Discounts\Discount;
use AdminEshop\Contracts\Discounts\Discountable;
use AdminEshop\Models\Orders\Order;
use AdminEshop\Models\Products\Product;
use App\Model\Store\DiscountsLevel;
use Store;

class ClientsProducts extends Discount implements Discountable
{
    /**
     * Discount code can't be applied outside cart
     *
     * @var  bool
     */
    public $canApplyOutsideCart = true;

    /*
     * Discount name
     */
    public function getName()
    {
        return _('Zľava na produkt');
    }

    public function canShowInEmail()
    {
        return false;
    }

    /*
     * Check if is discount active
     */
    public function isActive()
    {
        return count($this->getDiscountedProductsList()) > 0;
    }

    /*
     * Check if is discount active in administration
     */
    public function isActiveInAdmin(Order $order)
    {
        return count($this->getDiscountedProductsList()) > 0;
    }

    /**
     * Boot discount parameters after isActive check
     *
     * @param  mixed  $code
     * @return void
     */
    public function boot($level)
    {
        $this->operator = 'abs';

        $this->value = function($product, $price) use ($level) {
            $discount = null;

            if ( $product instanceof Product ){
                $discount = $this->getDiscountedProductsList()->where('product_id', $product->getKey())->first();
            }
            //TODO: check variant discount
            // elseif ( $product instanceof ProductsVariant ){
            //     $discount = $this->getDiscountedProductsList()->where('variant_id', $product->getKey())->first();
            // }

            //Modify price by discount operator
            if ( $discount ) {
                return operator_modifier($price, $discount->discount_operator, $discount->discount);
            }

            return $price;
        };
    }

    /*
     * Return cached all deliveries
     */
    public function getDiscountedProductsList()
    {
        if ( !($client = $this->getClient()) ){
            return collect();
        }

        return $this->cache('products_discounts.'.$client->getKey(), function() use ($client) {
            return $client->productsDiscounts;
        });
    }
}

?>