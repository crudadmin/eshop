<?php

namespace AdminEshop\Controllers\Cart;

use Admin;
use AdminEshop\Contracts\Discounts\DiscountCode;
use AdminEshop\Controllers\Controller;
use Cart;

class CartController extends Controller
{
    /*
     * Verify if row exists in db and return row key
     */
    private function getProductId()
    {
        return Admin::cache('cart.product_id', function(){
            return Admin::getModelByTable('products')
                        ->select(['id'])
                        ->where('id', request('product_id'))
                        ->firstOrFail()
                        ->getKey();
        });
    }

    /*
     * Verify if variant exists in db and returns key
     */
    private function getVariantId()
    {
        if ( ! request('variant_id') ) {
            return;
        }

        return Admin::cache('cart.variant_id', function(){
            return Admin::getModelByTable('products_variants')
                        ->select(['id'])
                        ->where('id', request('variant_id'))
                        ->where('product_id', $this->getProductId())
                        ->firstOrFail()
                        ->getKey();
        });
    }

    public function addItem()
    {
        Cart::addOrUpdate($this->getProductId(), request('quantity'), $this->getVariantId());

        return Cart::response();
    }

    public function updateQuantity()
    {
        Cart::updateQuantity($this->getProductId(), request('quantity'), $this->getVariantId());

        return Cart::response();
    }

    public function removeItem()
    {
        Cart::remove($this->getProductId(), $this->getVariantId());

        return Cart::response();
    }

    public function addDiscountCode()
    {
        $code = request('code');

        validator()->make(request()->all(), ['code' => 'required'])->validate();

        if ( !($code = DiscountCode::getDiscountCode($code)) ) {
            autoAjax()->throwValidation([
                'code' => _('Zadaný kod nie je platný'),
            ]);
        }

        DiscountCode::saveDiscountCode($code->code);

        return Cart::response();
    }

    public function removeDiscountCode()
    {
        DiscountCode::removeDiscountCode();

        return Cart::response();
    }
}
