<?php

namespace AdminEshop\Contracts\Collections;

use AdminEshop\Contracts\CartItem;
use AdminEshop\Models\Orders\Order;
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
     * @param  array|bool|null  $discounts (if is bool, discounts will be disabled)
     * @param  callable|null  $onItemRejected
     *
     * @return  CartCollection
     */
    public function toCartFormat($discounts = null, $onItemRejected = null)
    {
        return $this
                    ->applyOnOrderCart($discounts)
                    ->rejectWithMissingProduct($onItemRejected)
                    ->rejectWithMissingParents($onItemRejected)
                    ->renderCartItems($discounts)
                    ->sortByParentItemsOrder();
    }

    /**
     * Convert given items into cart format with order items
     *
     * @param  array|bool|null  $discounts
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
     * @param  array|bool|null  $discounts
     *
     * @return  CartCollection
     */
    public function applyCartDiscounts($discounts = null)
    {
        if ( $discounts === false ){
            return $this;
        }

        return $this->map(function($item) use ($discounts) {
            //If cart item does not have enabled discounts
            if ( $item->hasDiscounts() == false ){
                return $item;
            }

            //We would try apply discounts on cart item also.
            //If item would have discountable trait, cart discounts will be applied
            Cart::addCartDiscountsIntoModel($item, $discounts);

            //We also want apply discounts on item eloquent.
            //Also on on cached eloquent. Because this will be in cart summary in frontend.
            Cart::addCartDiscountsIntoModel($item->getItemModel(), $discounts);
            Cart::addCartDiscountsIntoModel($item->getOriginalitemModel(), $discounts);

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
                    if ( !isset($options['table']) ){
                        continue;
                    }

                    $fetchedModels = Cart::getFetchedModels($options);

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
            $itemModel = $item->getItemModel();

            //If product or variant is missing from cart item, remove this cart item
            if ( ! $itemModel && $itemModel !== false ) {
                //If has callback on remove item
                if ( is_callable($rejectionCallback) ) {
                    $rejectionCallback($item);
                }

                return true;
            }
        })->values();
    }

    /**
     * Reject assignes items when parent is not in cart anymore
     *
     * @param  null|callable  $rejectionCallback
     * @return  CartCollection
     */
    public function rejectWithMissingParents($rejectionCallback = null)
    {
        return $this->reject(function($item) use ($rejectionCallback) {
            //If product or variant is missing from cart item, remove this cart item
            if ( $item->parentIdentifier && !$item->getParentCartItem() ) {
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
     * @var  array $discounts
     *
     * @return array
     */
    public function getDefaultSummary($discounts)
    {
        //If we need turn off rounding in summary. We can switch this feature
        if ( Store::hasSummaryRounding() === false ) {
            Store::setRounding(false);
        }

        $sum = [];

        foreach ($this as $item) {
            $array = $item->getPricesArray($discounts);

            foreach ($array as $key => $value) {
                if ( !array_key_exists($key, $sum) ) {
                    $sum[$key] = 0;
                }

                $sum[$key] += ($item->quantity * $array[$key]);
            }
        }

        Store::setRounding(true);

        return $sum;
    }

    /**
     * Check if given key is with vat
     *
     * @param  string  $key
     * @return  bool
     */
    private function isDiscountableVatSummaryKey($key)
    {
        if ( strpos($key, 'WithVat') !== false )
            return true;

        if ( strpos($key, 'WithoutVat') !== false )
            return false;

        return Discounts::getDiscountableAttributeVatValue($key);
    }

    /**
     * Get all available cart summary prices with discounts
     *
     * @param  bool         $fullCartResponse - add payment and delivery prices into sum
     * @param  array|null   $discounts
     * @param  boolean      $withFixedDiscounts
     * @return array
     */
    public function getSummary($fullCartResponse = false, $discounts = null, $withFixedDiscounts = true)
    {
        //Set discounts if are missing
        //Sometimes we may want discounts without specific discount...
        $discounts = $discounts === null ? Discounts::getDiscounts() : $discounts;

        $sum = $this->getDefaultSummary($discounts);

        foreach ($sum as $key => $value) {
            //Check if we can apply sum modifications into this key
            $isVat = $this->isDiscountableVatSummaryKey($key);

            //Add statics discount into summary
            if ( $withFixedDiscounts == true ) {
                $sum[$key] = $this->addFixedDiscountsIntoFinalSum($sum[$key], $discounts, $isVat);
            }

            //Add delivery, payment method prices etc...
            if ( $fullCartResponse === true && $isVat !== null ) {
                $sum[$key] = $this->addAdditionalPaymentsIntoSum($sum[$key], $isVat);
            }

            //Round numbers, and make sure all numbers are positive
            $sum[$key] = $sum[$key] < 0 ? 0 : $sum[$key];
            $sum[$key] = Store::roundNumber($sum[$key]);
        }

        return $sum;
    }

    /**
     * Add additional prices into order sum.
     *
     * @param  int/float  $price
     * @param  bool  $withVat
     */
    public function addAdditionalPaymentsIntoSum($price, bool $withVat)
    {
        $order = OrderService::getOrder() ?: new Order;

        foreach (OrderService::getActiveMutators() as $mutator) {
            //Mutate price by anonymous price mutators
            if ( method_exists($mutator, 'mutatePrice') ) {
                $price = $mutator->mutatePrice(
                    $mutator->getActiveResponse(),
                    $price,
                    $withVat,
                    $order
                );
            }

            //Add price from additional cart items from mutators which will be inserted into order
            foreach (['addCartItems', 'addHiddenCartItems'] as $method) {
                if ( method_exists($mutator, $method) ) {
                    $addItems = $mutator->{$method}($mutator->getActiveResponse())
                                        ->toCartFormat();

                    $summaryFromAddedItems = $addItems->getSummary(false, null, false);

                    $price += (@$summaryFromAddedItems[$withVat ? 'priceWithVat' : 'priceWithoutVat'] ?: 0);
                }
            }
        }

        return $price;
    }

    /**
     * Apply given discounts on whole sum
     *
     * @param  int/float  $price
     * @param  array  $discounts
     * @param  bool/null  $isVat
     *
     * @return int/float
     */
    public function addFixedDiscountsIntoFinalSum($price, $discounts, $isVat = null)
    {
        foreach ($discounts as $discount) {
            //If is not discountableVat attribute
            if ( $isVat === null ) {
                continue;
            }

            foreach ($discount->getAllOperators() as $operatorProp) {
                //Skip non-appliable discounts
                if ( ($operatorProp['applyOnWholeCart'] ?? false) === false ){
                    continue;
                }

                $operator = $operatorProp['operator'];
                $value = $operatorProp['value'];

                //If is vat attribute, and discount value is with + or - operator
                //Then we need to apply vat to this discount
                $discountValue = $isVat === true && $discount->hasSumPriceOperator($operator)
                                    ? Store::priceWithVat($value)
                                    : $value;

                //Apply given discount
                $price = operator_modifier($price, $operator, $discountValue);
            }
        }

        return $price;
    }

    public function getAppliedItemsDiscounts()
    {
        return $this->map(function($item){
            return [
                'id' => $item->getKey(),
                'class' => get_class($item),
                'discounts' => $item->appliedDiscounts,
            ];
        });
    }

    public function sortByParentItemsOrder()
    {
        $indexes = [];
        $parentAddons = [];

        $i = -1;
        return $this->sortBy(function($cartItem) use(&$i, &$parentAddons) {
            $i++;

            //Set order only for items without parent
            if ( $cartItem->parentIdentifier ){
                //Get parent cart item order
                $parentOrder = $this->search(function($parentItem) use ($cartItem) {
                    return $parentItem->isParentOwner($cartItem);
                });

                if ( !array_key_exists($parentOrder, $parentAddons) ){
                    $parentAddons[$parentOrder] = 1;
                }

                $order = $parentOrder.'-'.$parentAddons[$parentOrder];

                $parentAddons[$parentOrder]++;

                return $order;
            } else {
                return $i.'-0';
            }
        })->values();
    }
}
