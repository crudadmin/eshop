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
        $items = Basket::add($this->getProductId(), request('quantity'), $this->getVariantId());

        return $items->all();
    }

    public function updateQuantity()
    {
        $items = Basket::updateQuantity($this->getProductId(), request('quantity'), $this->getVariantId());

        return $items->all();
    }

    public function removeItem()
    {
        $items = Basket::remove($this->getProductId(), $this->getVariantId());

        return $items->all();
    }
}
