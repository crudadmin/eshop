<?php

namespace AdminEshop\Contracts\Collections;

use Illuminate\Support\Collection;
use Admin\Eloquent\AdminModel;
use OrderService;
use Discounts;
use Store;
use Cart;

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
     * Set to each model that discounts can be applied also in administration
     *
     * @return  CartCollection
     */
    public function allowApplyDiscountsInAdmin()
    {
        return $this->map(function($item){
            if ( $model = $item->getItemModel() ) {
                $item->getItemModel()->setApplyDiscountsInAdmin(true);
            }

            return $item;
        });
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

    /**
     * Get all available cart summary prices
     *
     * @return array
     */
    public function getDefaultSummary()
    {
        $sum = [];

        foreach ($this as $item) {
            $model = $item->getItemModel();
            $array = $model->toCartArray();

            foreach ($array as $key => $value) {
                //If does not have price in attribute name
                if ( strpos(strtolower($key), 'price') === false ) {
                    continue;
                }

                if ( !array_key_exists($key, $sum) ) {
                    $sum[$key] = 0;
                }

                $sum[$key] = $item->quantity * $array[$key];
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
     * @param  array  $discounts
     * @param  bool  $$fullCartResponse - add payment and delivery prices into sum
     * @return array
     */
    public function getSummary($fullCartResponse = false)
    {
        $discounts = Discounts::getDiscounts();

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
