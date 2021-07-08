<?php

namespace AdminEshop\Contracts\Cart\Identifiers;

use AdminEshop\Contracts\CartItem;
use AdminEshop\Contracts\Cart\Identifiers\Identifier;
use Discounts;
use Store;

class DefaultIdentifier extends Identifier
{
    /**
     * Keys in array are assigned to eloquents tables
     *
     * @return  array
     */
    public static function getIdentifyKeys()
    {
        return [
            'item_name' => [],
            'item_price' => [],
            'item_vat' => [],
        ];
    }

    /*
     * Retuns name of identifier
     */
    public function getName()
    {
        return 'default';
    }

    /**
     * Get model by given cart type
     * If this method returns false instead of null
     * item without model will be valid and
     * wont be automatically removed from cart.
     *
     * @param  CartItem  $item
     * @return  Admin\Eloquent\AdminModel|CartItem|null
     */
    public function getItemModel($item, $cache)
    {
        $originalObject = $item->getOriginalObject() ?: $item;

        //If item has set original object when if eloquent identifier is missing.
        //Also original object needs to be also with discountable support
        if ( Discounts::hasDiscountableTrait($originalObject) ) {
            return $originalObject;
        }

        return false;
    }

    private function getVatValue(CartItem $item)
    {
        return is_null($item->item_vat) ? Store::getDefaultVat() : $item->item_vat;
    }

    public function onOrderItemCreate(CartItem $item)
    {
        $vat = $this->getVatValue($item);

        $data = [
            'identifier' => $this->getName(),
            'name' => $item->item_name,
            'discountable' => false,
            'quantity' => $item->quantity,
            'default_price' => $item->item_price,
            'price' => $item->item_price,
            'vat' => Store::getVatValueById($vat),
            'price_vat' => Store::priceWithVat($item->item_price, $vat),
        ];

        return $data;
    }
}

?>