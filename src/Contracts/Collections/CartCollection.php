<?php

namespace AdminEshop\Contracts\Collections;

use Admin\Eloquent\AdminModel;
use Cart;
use Illuminate\Support\Collection;

class CartCollection extends Collection
{
    /**
     * Convert cart items into cart format
     *
     * @param  array  $discounts
     * @param  callable|null  $onItemRejected
     *
     * @return  CartCollection
     */
    public function toCartFormat($discounts = null, $onItemRejected = null)
    {
        return $this->applyOnOrderCart($discounts)
                    ->renderCartItems($discounts)
                    ->rejectWithMissingProduct($onItemRejected);
    }

    /**
     * Convert given items into cart format with order items
     *
     * @param  array|null  $discounts
     *
     * @return  CartCollection
     */
    public function applyOnOrderCart($discounts = null)
    {
        return $this->fetchModels()
                    ->applyCartDiscounts($discounts);
    }

    /**
     * Convert given items into cart format with order items
     *
     * @param  array  $discounts
     *
     * @return  CartCollection
     */
    public function applyCartDiscounts($discounts = null)
    {
        return $this->map(function($item) use ($discounts) {
            if ( $item instanceof AdminModel ) {
                Cart::addCartDiscountsIntoModel($item, $discounts);
            }

            Cart::addCartDiscountsIntoModel($item->getItemModel(), $discounts);

            return $item;
        });
    }

    /**
     * Render all cart items
     *
     * @param  array  $discounts
     * @return  CartCollection
     */
    public function renderCartItems($discounts = null)
    {
        return $this->map(function($item) use ($discounts) {
            return (clone $item)->render($discounts);
        });
    }

    /**
     * Add fetched product and variant into cart item
     *
     * @return object
     */
    public function fetchModels()
    {
        Cart::fetchMissingModels($this);

        $identifiers = Cart::getRegistredIdentifiers();

        return $this->map(function($item) use ($identifiers) {
            foreach ($identifiers as $identifier) {
                foreach ($identifier->getIdentifyKeys() as $key => $options) {
                    $fetchedModels = Cart::getFetchedModels($options['table']);

                    //If identifier is not present
                    if ( ! $identifier->getIdentifierValue($item, $key) ) {
                        continue;
                    }

                    $relation = $fetchedModels->find($identifier->getIdentifierValue($item, $key));

                    $item->setItemModel($options['modelKey'], $relation);
                }
            }

            return $item;
        });
    }

    /**
     * Reject items without product from cart collection
     *
     * @param  null|callable  $rejectionCallback
     * @return  CartCollection
     */
    public function rejectWithMissingProduct($rejectionCallback = null)
    {
        return $this->reject(function($item) use ($rejectionCallback) {
            //If product or variant is missing from cart item, remove this cart item
            if ( ! $item->getItemModel() && $item->getItemModel() !== false ) {
                //If has callback on remove item
                if ( is_callable($rejectionCallback) ) {
                    $rejectionCallback($item);
                }

                return true;
            }
        })->values();
    }
}
