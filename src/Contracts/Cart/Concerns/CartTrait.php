<?php

namespace AdminEshop\Contracts\Cart\Concerns;

use Admin;
use AdminEshop\Contracts\CartItem;
use AdminEshop\Contracts\Cart\Identifiers\DefaultIdentifier;
use AdminEshop\Contracts\Cart\Identifiers\DiscountIdentifier;
use AdminEshop\Contracts\Cart\Identifiers\ProductsIdentifier;
use AdminEshop\Contracts\Collections\CartCollection;
use AdminEshop\Events\CartUpdated;
use Admin\Eloquent\AdminModel;
use Discounts;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Store;
use \Illuminate\Database\Eloquent\Collection as EloquentCollection;

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
        ProductsIdentifier::class,
        DefaultIdentifier::class,
        DiscountIdentifier::class,
    ];

    /**
     * Returns all booted cart identifiers objects mapped with keys
     *
     * @return  array
     */
    public function getRegistredIdentifiers()
    {
        //First we need register identifier from config, because we may want rewrite original identifiers
        $cartIdentifiers = array_merge(
            $this->cartIdentifiers,
            config('admineshop.cart.identifiers', [])
        );

        return $this->cache('cartIdentifiers', function() use ($cartIdentifiers) {
            $identifiers = array_map(function($item){
                return new $item;
            }, $cartIdentifiers);

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
    public function getIdentifierByName($name)
    {
        $identifiers = $this->getRegistredIdentifiers();

        if ( array_key_exists($name, $identifiers) ) {
            return clone $identifiers[$name];
        }
    }

    /**
     * Return cart item identifier
     *
     * @param  string  $name
     * @return null|AdminEshop\Contracts\Cart\Identifier
     */
    public function getIdentifierByClassName($name)
    {
        $identifiers = $this->getRegistredIdentifiers();

        $name = mb_strtolower($name);

        foreach ($identifiers as $identifier) {
            $classname = get_class($identifier);

            if ( mb_strtolower(class_basename($classname)) == $name ){
                return $classname;
            }
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
    public function fetchItemsFromDriver() : CartCollection
    {
        $items = $this->getDriver()->get('items');

        if ( ! is_array($items) ) {
            return new CartCollection;
        }

        $items = array_map(function($item){
            $item = (object)$item;

            //If cart identifier is missing
            //This may happend if someone has something id cart, and code will change
            //Or identifier will be renamed
            if (!($identifier = $this->getIdentifierByName($item->identifier))) {
                return;
            }

            $identifier->cloneFromItem($item);

            $cartItem = new CartItem($identifier, @$item->quantity ?: 0);

            //Set data of item
            if ( isset($item->data) ){
                $cartItem->setData($item->data, false);
            }

            //If parent cartitem identifier has not been found, we need skip this item from cart
            if ($this->assignParentCartItem($item, $cartItem) === false){
                return;
            }

            return $cartItem;
        }, $items);

        return new CartCollection(array_filter($items));
    }

    /**
     * Assign parent identifier into existing cart item
     *
     * @param  object  $item
     * @param  CartItem  $cartItem
     * @return  CartItem
     */
    private function assignParentCartItem($item, CartItem $cartItem)
    {
        if ( !($item->parentIdentifier ?? null) ){
            return;
        }

        //We need skip this item, because parent identifier is missing
        if (!($parentIdentifier = $this->bootCartItemParentIdentifier($item->parentIdentifier))) {
            return false;
        }

        $cartItem->setParentIdentifier($parentIdentifier);
    }

    /**
     * Boot parent identifier from given array
     *
     * @param  array|null  $parentIdentifierArray
     *
     * @return  Identifier
     */
    public function bootCartItemParentIdentifier(array $parentIdentifierArray = null)
    {
        //If identifier is missing
        if (
            !is_array($parentIdentifierArray)
            || !($parentIdentifier = $this->getIdentifierByName($parentIdentifierArray['identifier']))
        ) {
            return;
        }

        return $parentIdentifier->cloneFromItem(
            (object)$parentIdentifierArray['data']
        );
    }

    /**
     * Check quantity type
     */
    public function checkQuantity($quantity)
    {
        if ( ! is_numeric($quantity) || $quantity < 0 ) {
            return 1;
        }

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

                    $query = $model->whereIn($model->getModel()->fixAmbiguousColumn('id'), $missingIdsToFetch);

                    $fetchedModels = $identifier->onFetchItems($query)->get();

                    $this->addFetchedModels($options['table'], $fetchedModels);
                }
            }
        }

        return $this;
    }

    /**
     * Add cart discounts into model.
     * Some discounts are not allowed outside cart.
     * But if we want allow items to be allowed in cart,
     * we can use this class.
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
            //We need apply discounts only on discountable classes. So we want skip non discountable classes
            if ( ! Discounts::hasDiscountableTrait($row) ) {
                continue;
            }

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

    /**
     * Save items from cart into session
     *
     * @return void
     */
    public function saveItems()
    {
        $items = $this->items->toArray();

        $arrayItems = [];

        foreach ($items as $key => $item) {
            $arrayItems[] = $item->toArray();
        }

        $this->getDriver()->set('items', $arrayItems);

        event(new CartUpdated($this->items));
    }

    public function getBoughtWithProducts()
    {
        $identifiersKeys = $this->items->map(function($item){
            $identifier = $item->getIdentifierClass();

            if ( !($identifier instanceof ProductsIdentifier) ){
                return;
            }

            return $identifier->getOrderItemsColumns();
        })->filter()->values();

        $orderIdsWithThisProducts = DB::table('orders_items')
            ->where(function($query) use ($identifiersKeys) {
                foreach ($identifiersKeys as $key => $keys) {
                    $query->{$key == 0 ? 'where' : 'orWhere'}($keys);
                }
            })
            ->select('order_id')
            ->take(300)
            ->get()
            ->pluck('order_id');

        return Admin::getModel('Product')
                //Except items in cart
                ->whereNotIn('id', $identifiersKeys->pluck('product_id'))
                //Only products which has been ordered in given orders list
                ->whereHas('ordersItem', function($query) use ($orderIdsWithThisProducts) {
                    $query->whereIn('order_id', $orderIdsWithThisProducts);
                });
    }
}

?>