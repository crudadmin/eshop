<?php

namespace AdminEshop\Controllers\Basket;

use \AdminEshop\Controllers\Controller;
use Basket;
use Admin;

class BasketController extends Controller
{
    /*
     * Verify if row exists in db and return row key
     */
    private function getProductId()
    {
        return Admin::cache('basket.product_id', function(){
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

        return Admin::cache('basket.variant_id', function(){
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
        Basket::addOrUpdate($this->getProductId(), request('quantity'), $this->getVariantId());

        return Basket::response();
    }

    public function updateQuantity()
    {
        Basket::updateQuantity($this->getProductId(), request('quantity'), $this->getVariantId());

        return Basket::response();
    }

    public function removeItem()
    {
        Basket::remove($this->getProductId(), $this->getVariantId());

        return Basket::response();
    }

    public function addDiscountCode()
    {
        $code = request('code');

        validator()->make(request()->all(), ['code' => 'required'])->validate();

        if ( !($code = Basket::getDiscountCode($code)) ) {
            autoAjax()->throwValidationError([
                'code' => _('Zadaný kod nie je platný'),
            ]);
        }

        Basket::saveDiscountCode($code->code);

        return Basket::response();
    }
}
