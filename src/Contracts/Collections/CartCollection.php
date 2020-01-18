<?php

namespace AdminEshop\Contracts\Collections;

use AdminEshop\Contracts\CartItem;
use Admin\Eloquent\AdminModel;
use Cart;
use Discounts;
use Illuminate\Support\Collection;
use OrderService;
use Store;

class CartCollection extends Collection
{
    /**
     * Convert cart items into cart format
     * This function is suitable only for carItem
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
            //We would try apply discounts on cart item also.
            //If item would have discountable trait, cart discounts will be applied
            Cart::addCartDiscountsIntoModel($item, $discounts);

            //We also want apply discounts on item eloquent
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
            if ( $item instanceof CartItem ) {
                return (clone $item)->render($discounts);
            }

            return $item;
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
                    if ( ! ($identifierValue = $identifier->getIdentifierValue($item, $key)) ) {
                        continue;
                    }

                    $relation = $fetchedModels->find($identifierValue);

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

    /**
     * Get all available cart summary prices
     *
     * @return array
     */
    public function getDefaultSummary()
    {
        $sum = [];

        foreach ($this as $item) {
            $array = $item->getPricesArray();

            foreach ($array as $key => $value) {
                if ( !array_key_exists($key, $sum) ) {
                    $sum[$key] = 0;
                }

                $sum[$key] += ($item->quantity * $array[$key]);
            }
        }

        return $sum;
    }

    /**
     * Check if given key is with tax
     *
     * @param  string  $key
     * @return  bool
     */
    private function isDiscountableTaxSummaryKey($key)
    {
        if ( strpos($key, 'WithTax') !== false )
            return true;

        if ( strpos($key, 'WithoutTax') !== false )
            return false;

        return Discounts::getDiscountableAttributeTaxValue($key);
    }

    /**
     * Get all available cart summary prices with discounts
     *
     * @param  Collection  $items
     * @param  bool  $fullCartResponse - add payment and delivery prices into sum
     * @param  array|null  $discounts
     * @return array
     */
    public function getSummary($fullCartResponse = false, $discounts = null)
    {
        //Set discounts if are missing
        //Sometimes we may want discounts without specific discount...
        if ( $discounts === null ) {
            $discounts = Discounts::getDiscounts();
        }

        $sum = $this->getDefaultSummary();

        foreach ($sum as $key => $value) {
            //Check if we can apply sum modifications into this key
            $isTax = $this->isDiscountableTaxSummaryKey($key);

            //Add statics discount int osummary
            $sum[$key] = $this->addDiscountsIntoFinalSum($sum[$key], $discounts, $isTax);

            //Add delivery, payment method prices etc...
            if ( $fullCartResponse === true && $isTax !== null ) {
                $sum[$key] = OrderService::addAdditionalPaymentsIntoSum($sum[$key], $isTax);
            }

            //Round numbers, and make sure all numbers are positive
            $sum[$key] = $sum[$key] < 0 ? 0 : $sum[$key];
            $sum[$key] = Store::roundNumber($sum[$key]);
        }

        return $sum;
    }

    /**
     * Apply given discounts on whole sum
     *
     * @param  int/float  $price
     * @param  array  $discounts
     * @param  bool/null  $isTax
     *
     * @return int/float
     */
    public function addDiscountsIntoFinalSum($price, $discounts, $isTax = null)
    {
        foreach ($discounts as $discount) {
            //If this discount is not applied on whole cart,
            //Or is not discountableTax attribute
            if ( $discount->applyOnWholeCart() !== true || $isTax === null ) {
                continue;
            }

            //If is tax attribute, and discount value is with + or - operator
            //Then we need to apply tax to this discount
            $discountValue = $isTax === true && $discount->hasSumPriceOperator()
                                ? Store::priceWithTax($discount->value)
                                : $discount->value;

            //Apply given discount
            $price = operator_modifier($price, $discount->operator, $discountValue);
        }

        return $price;
    }
}
