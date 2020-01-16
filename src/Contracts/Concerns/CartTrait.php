<?php

namespace AdminEshop\Contracts\Concerns;

use Admin;
use AdminEshop\Contracts\CartItem;
use AdminEshop\Contracts\Cart\CartItemIdentifier;
use AdminEshop\Contracts\Cart\Identifiers\DefaultIdentifier;
use AdminEshop\Contracts\Collections\CartCollection;
use \Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Discounts;
use Store;

trait CartTrait
{
    /*
     * Which items has been added into cart
     */
    public $addedItems = [];

    /*
     * Which items has been updated in cart
     */
    public $updatedItems = [];

    /*
     * Fetched models from db
     */
    private $fetchedModels = [];

    /*
     * Available cart identifiers
     */
    private $cartIdentifiers = [
        CartItemIdentifier::class,
        DefaultIdentifier::class,
    ];

    /**
     * Returns all booted cart identifiers objects mapped with keys
     *
     * @return  array
     */
    public function getRegistredIdentifiers()
    {
        return $this->cache('cartIdentifiers', function(){
            $identifiers = array_map(function($item){
                return new $item;
            }, $this->cartIdentifiers);

            $identifiersKeys = array_map(function($identifier){
                return $identifier->getName();
            }, $identifiers);

            return array_combine($identifiersKeys, $identifiers);
        });
    }

    /**
     * Return cart item identifier
     *
     * @param  string  $name
     * @return null|AdminEshop\Contracts\Cart\Identifier
     */
    public function getCartItemIdentifier($name)
    {
        $identifiers = $this->getRegistredIdentifiers();

        if ( array_key_exists($name, $identifiers) ) {
            return clone $identifiers[$name];
        }
    }

    /**
     * Items has been added into cart
     *
     * @param  object  $item
     * @return  this
     */
    public function pushToAdded($item)
    {
        $this->addedItems[] = $item;

        return $this;
    }

    /**
     * Items has been updated into cart
     *
     * @param  object  $item
     * @return  this
     */
    public function pushToUpdated($item)
    {
        $this->updatedItems[] = $item;

        return $this;
    }

    /*
     * Fetch items from session
     */
    private function fetchItemsFromSession()
    {
        $items = session($this->key, []);

        if ( ! is_array($items) ) {
            return new CartCollection;
        }

        $items = array_map(function($item){
            $item = (object)$item;

            //If cart identifier is missing
            //This may happend if someone has something id cart, and code will change
            //Or identifier will be renamed
            if (!($identifier = $this->getCartItemIdentifier($item->identifier))) {
                return;
            }

            $identifier->cloneFormItem($item);

            return new CartItem($identifier, @$item->quantity ?: 0);
        }, $items);

        return new CartCollection(array_filter($items));
    }

    /**
     * Check quantity type
     */
    private function checkQuantity($quantity)
    {
        if ( ! is_numeric($quantity) || $quantity < 0 )
            return 1;

        return (int)$quantity;
    }

    /**
     * Fetch products/variants from db
     *
     * @var CartCollection $item
     *
     * @return  this
     */
    public function fetchMissingModels(CartCollection $items)
    {
        if ( $items->count() == 0 )
            return $this;

        $identifiers = $this->getRegistredIdentifiers();

        foreach ($identifiers as $identifier) {
            $fetchConfig = $identifier->getIdentifyKeys();

            foreach ($fetchConfig as $key => $options) {
                //If no identifier items has been found
                if ( ($identifierItems = $items->where('identifier', $identifier->getName()))->count() == 0) {
                    continue;
                }

                $key = $identifier->tryOrderItemsColumn($key, $identifierItems->first());

                $itemsIds = array_filter($identifierItems->pluck([ $key ])->toArray());
                $alreadyFetched = $this->getFetchedModels($options['table'])->pluck(['id'])->toArray();

                $missingIdsToFetch = array_diff($itemsIds, $alreadyFetched);

                //If there are any non-fetched products
                if ( count($missingIdsToFetch) > 0 ) {
                    $model = Admin::getModelByTable($options['table']);

                    //Apply scope on model
                    if ( is_callable($options['scope']) ) {
                        $model = $options['scope']($model);
                    }

                    $fetchedModels = $model->whereIn($model->getModel()->fixAmbiguousColumn('id'), $missingIdsToFetch)->get();

                    $this->addFetchedModels($options['table'], $fetchedModels);
                }
            }
        }

        return $this;
    }

    /**
     * Add cart discounts into model
     *
     * @param  AdminModel  $item
     * @param  array|null  $discounts
     */
    public function addCartDiscountsIntoModel($itemOrItems = null, $discounts = null)
    {
        //Item or items must be present
        if ( ! $itemOrItems ) {
            return $itemOrItems;
        }

        $items = ($itemOrItems instanceof Collection) ? $itemOrItems : collect([ $itemOrItems ]);

        foreach ($items as $row) {
            Discounts::applyDiscountsOnModel(
                $row,
                $discounts,
                function($discount, $item){
                    return $discount->canApplyInCart($item);
                }
            );
        }

        return $itemOrItems;
    }


    /**
     * Check if given key is with tax
     *
     * @param  string  $key
     * @return  bool
     */
    public function isDiscountableTaxSummaryKey($key)
    {
        if ( strpos($key, 'WithTax') !== false )
            return true;

        if ( strpos($key, 'WithoutTax') !== false )
            return false;

        return Discounts::getDiscountableAttributeTaxValue($key);
    }

    /**
     * Get all available cart summary prices
     *
     * @param  Collection  $items
     * @return array
     */
    public function getDefaultSummary($items)
    {
        $sum = [];

        foreach ($items as $cartItem) {
            $array = $cartItem->getItemModel()->toArray();

            foreach ($array as $key => $value) {
                //If does not have price in attribute name
                if ( strpos(strtolower($key), 'price') === false ) {
                    continue;
                }

                if ( !array_key_exists($key, $sum) ) {
                    $sum[$key] = 0;
                }

                $sum[$key] = $cartItem->quantity * $array[$key];
            }
        }

        return $sum;
    }

    /**
     * Add additional prices into order sum, as
     *
     * @param  int/float  $price If is no withTax/withoutTax price atribute
     * @param  bool  $isTax
     */
    public function addAdditionalPaymentsIntoSum($price, bool $isTax)
    {
        $selectedDelivery = $this->getSelectedDelivery();
        $selectedPaymentMethod = $this->getSelectedPaymentMethod();

        //Add delivery
        if ( $selectedDelivery ) {
            $price += $selectedDelivery->{$isTax ? 'priceWithTax' : 'priceWithoutTax'};
        }

        if ( $selectedPaymentMethod ) {
            $price += $selectedPaymentMethod->{$isTax ? 'priceWithTax' : 'priceWithoutTax'};
        }

        return $price;
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
            $discountValue = $isTax === true && $discount->hasSumPriceOperator() ?
                                    Store::priceWithTax($discount->value) : $discount->value;

            //Apply given discount
            $price = operator_modifier($price, $discount->operator, $discountValue);
        }

        return $price;
    }

    /**
     * Get all available cart summary prices with discounts
     *
     * @param  Collection  $items
     * @param  array  $discounts
     * @param  bool  $$fullCartResponse - add payment and delivery prices into sum
     * @return array
     */
    public function getSummary($items = null, $discounts = null, $fullCartResponse = false)
    {
        $items = $items === null ? $this->all() : $items;

        $discounts = $discounts === null ? Discounts::getDiscounts() : $discounts;

        $sum = $this->getDefaultSummary($items);

        foreach ($sum as $key => $value) {
            //Check if we can apply sum modifications into this key
            $isTax = $this->isDiscountableTaxSummaryKey($key);

            //Add statics discount int osummary
            $sum[$key] = $this->addDiscountsIntoFinalSum($sum[$key], $discounts, $isTax);

            //Add delivery, payment method prices etc...
            if ( $fullCartResponse === true && $isTax !== null ) {
                $sum[$key] = $this->addAdditionalPaymentsIntoSum($sum[$key], $isTax);
            }

            //Round numbers, and make sure all numbers are positive
            $sum[$key] = $sum[$key] < 0 ? 0 : $sum[$key];
            $sum[$key] = Store::roundNumber($sum[$key]);
        }

        return $sum;
    }

    /**
     * Returns fetched products
     *
     * @return  Collection
     */
    public function getFetchedModels($table)
    {
        if ( array_key_exists($table, $this->fetchedModels) ){
            return $this->fetchedModels[$table] ?: new EloquentCollection([]);
        }

        return new EloquentCollection([]);
    }

    /**
     * Save fetched models from db
     *
     * @var string  $table
     * @var Collection  $items
     *
     * @return  this
     */
    public function addFetchedModels($table, $items)
    {
        if ( !array_key_exists($table, $this->fetchedModels ?: []) ){
            $this->fetchedModels[$table] = new EloquentCollection([]);
        }

        $this->fetchedModels[$table] = $this->fetchedModels[$table]->merge($items);

        return $this;
    }
}

?>