<?php

namespace AdminEshop\Contracts\Cart\Identifiers;

use AdminEshop\Contracts\CartItem;
use AdminEshop\Contracts\Cart\Identifiers\Concerns\UsesIdentifier;
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

    /**
     * Return set, or default vat value
     *
     * @param  CartItem  $item
     *
     * @return  integer
     */
    private function getVatValue(CartItem $item)
    {
        return is_null($item->item_vat) ? Store::getDefaultVat() : $item->item_vat;
    }

    /**
     * Create order item by cart item properties
     *
     * @param  CartItem  $item
     * @return  [type]
     */
    public function onOrderItemCreate(CartItem $item)
    {
        $vat = $this->getVatValue($item);

        $data = [
            'identifier' => $this->getName(),
            'name' => $item->item_name,
            'discountable' => false,
            'quantity' => $item->quantity,
            'default_price' => $item->item_price,
            'manual_price' => true,
            'price' => $item->item_price,
            'vat' => Store::getVatValueById($vat),
            'price_vat' => Store::priceWithVat($item->item_price, $vat),
        ];

        return $data;
    }

    /**
     * Returns static prices into cart summary
     *
     * @param  UsesIdentifier  $item
     * @param  Array|null  $discounts
     *
     * @return  array
     */
    public function getPricesArray(UsesIdentifier $item, $discounts = null)
    {
        if ( $item instanceof CartItem ) {
            $vat = $this->getVatValue($item);

            $price = $item->item_price;
            $priceWithVat = Store::priceWithVat($price, $vat);

            return [
                'price' => $price,
                'initialPriceWithVat' => $priceWithVat,
                'initialPriceWithoutVat' => $price,
                'defaultPriceWithVat' => $priceWithVat,
                'defaultPriceWithoutVat' => $price,
                'priceWithVat' => $priceWithVat,
                'priceWithoutVat' => $price,
                'clientPrice' => $price,
            ];
        }

        return parent::getPricesArray($item, $discounts);
    }

    /**
     * Modify item on cart items render into website
     *
     * @param  CartItem  $item
     * @return  void
     */
    public function onRender(CartItem $item)
    {
        $vat = $this->getVatValue($item);

        //Set default vat, if is missing
        $item->item_vat = $vat;
        $item->item_price_vat = Store::priceWithVat($item->item_price, $vat);
    }

    /**
     * Returns product name
     *
     * @param  UsesIdentifier  $item
     *
     * @return  array
     */
    public function getProductNameParts(UsesIdentifier $item) : array
    {
        return array_filter([ $item->item_name ?: $item->name ]);
    }

    /**
     * Returns item price
     *
     * @param CartItem  $item
     * @param string    $priceKey
     *
     * @return  decimal
     */
    public function getPrice(CartItem $item, $priceKey = 'priceWithVat')
    {
        if ( strpos($priceKey, 'withoutVat') !== false ){
            return $this->item_price;
        } else {
            $vat = $this->getVatValue($item);

            return Store::priceWithVat($item->item_price, $vat);
        }
    }
}

?>