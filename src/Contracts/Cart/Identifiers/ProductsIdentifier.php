<?php

namespace AdminEshop\Contracts\Cart\Identifiers;

use AdminEshop\Contracts\CartItem;
use AdminEshop\Contracts\Cart\Identifiers\Identifier;

class ProductsIdentifier extends Identifier
{
    /*
     * Retuns name of identifier
     */
    public function getName()
    {
        return 'products';
    }

    /**
     * Keys in array are assigned to eloquents tables
     *
     * @return  array
     */
    public static function getIdentifyKeys()
    {
        return [
            'id' => [
                'table' => 'products',
                'modelKey' => 'product',
                'scope' => function($query){
                    return $query->cartSelect();
                },
                'orders_items_column' => 'product_id',
            ],
            'variant_id' => [
                'table' => 'products_variants',
                'modelKey' => 'variant',
                'scope' => function($query){
                    return $query->cartSelect()->with(['attributesItems']);
                },
            ],
        ];
    }

    /**
     * Get model by given cart type
     * If this method returns false instead of null
     * item without model will be valid and
     * wont be automatically removed from cart.
     *
     * @param  CartItem  $item
     * @return  Admin\Eloquent\AdminModel|null
     */
    public function getItemModel($item, $cache)
    {
        if ( $item->variant_id ) {
            return @$cache['variant'];
        }

        if ( $item->id ) {
            return @$cache['product'];
        }
    }

    /**
     * Returns if product in cart item is on stock
     *
     * @return  bool
     */
    public function hasQuantityOnStock($item)
    {
        //If item product is not present
        if ( ! ($model = $item->getItemModel()) ) {
            return true;
        }

        //We can skip checking products which all allowed all the time
        if ( $model->canOrderEverytime() ) {
            return true;
        }

        //Check if quantity in cart is lower that quantity on stock
        return $item->quantity <= $model->warehouse_quantity;
    }

    /**
     * Modify item on render
     *
     * @param  CartItem  $item
     * @return  [type]
     */
    public function onRender(CartItem $item)
    {
        $item->hasQuantityOnStock = $this->hasQuantityOnStock($item);
    }
}

?>