<?php

namespace AdminEshop\Contracts;

use Log;
use Cart;
use Mail;
use Admin;
use Store;
use Discounts;
use Exception;
use Localization;
use Gogol\Invoices\Model\Invoice;
use AdminEshop\Contracts\CartItem;
use AdminEshop\Mail\OrderReceived;
use Admin\Core\Contracts\DataStore;
use AdminEshop\Events\OrderCreated;
use AdminEshop\Models\Orders\Order;
use AdminEshop\Models\Orders\OrdersItem;
use AdminEshop\Contracts\Order\HasRequest;
use AdminEshop\Models\Orders\OrdersStatus;
use AdminEshop\Contracts\Order\HasValidation;
use AdminEshop\Contracts\Order\Concerns\HasWeight;
use AdminPayments\Contracts\Concerns\HasProviders;
use AdminEshop\Contracts\Collections\CartCollection;
use AdminEshop\Contracts\Order\Concerns\HasMutators;
use AdminEshop\Contracts\Order\Concerns\HasShipping;
use AdminEshop\Contracts\Order\Concerns\HasOrderProcess;
use AdminEshop\Contracts\Order\Concerns\HasMutatorsForward;

class OrderService
{
    use DataStore,
        HasProviders,
        HasRequest,
        HasValidation,
        HasMutators,
        HasMutatorsForward,
        HasOrderProcess,
        HasShipping,
        HasWeight;

    /**
     * Order row
     *
     * @var  Admin\Eloquent\AdminModel|null
     */
    protected $order;

    /**
     * Cart items which will be filled into order
     *
     * @var  CartCollection
     */
    protected $cartItems;

    /**
     * Should be stock countdown after new order?
     *
     * @var  bool
     */
    protected $stockSync = true;

    /*
     * Returns if invoices support is allowed
     */
    public function hasInvoices()
    {
        return config('admin_eshop.invoices.enabled', false) === true;
    }

    /**
     * Simple order store
     *
     * @return  this
     */
    public function store()
    {
        $this->setOrder(
            clone Admin::getModelByTable('orders')
        );

        //Base Cart items, so only those which are added by user, and not by mutators.
        $cartItems = $this->getCartItems();

        //Build order with all attributes
        $this->buildOrder($cartItems);

        $this->saveOrCreateOrder($cartItems);

        return $this;
    }

    /**
     * Fire order event
     *
     * @return  this
     */
    public function fireCreatedEvent()
    {
        $order = $this->getOrder();

        //Event for added discount code
        event(new OrderCreated($order));

        return $this;
    }

    /**
     * Modify cart items inserted into order
     *
     * @param  CartCollection  $items
     */
    public function setCartItems(CartCollection $items)
    {
        $this->cartItems = $items;

        return $this;
    }

    /**
     * Set cart items from order
     * Todo: (test functionality for 100% worflow)
     *
     * @param  Order  $order
     */
    public function setOrderCartItems(Order $order)
    {
        $orderItems = $order->items->map(function($item){
            return $item->getCartItem();
        });

        $cartItems = (new CartCollection($orderItems))
            ->renderCartItems()
            ->map(function($item){
                // Restore original order item prices
                if ( ($model = $item->getItemModel()) && ($orderItem = $item->getOriginalObject()) instanceof OrdersItem ) {
                    $orderItem->restorePricesIntoItemModel($model);
                }

                return $item;
            });

        $this->setCartItems($cartItems);

        return $this;
    }

    /**
     * This cart items will be inserted into order
     *
     * @return  CartCollection
     */
    public function getCartItems($withMutators = false)
    {
        if ( is_null($this->cartItems) ){
            $items = Cart::all();
        } else {
            $items = $this->cartItems->toCartFormat();
        }

        if ( $withMutators === true ) {
            $items = Cart::addItemsFromMutators($items, true);
        }

        return $items;
    }


    /**
     * Set order
     *
     * @param  AdminModel|null  $order
     */
    public function setOrder($order, $discounts = false, $items = false)
    {
        //We need register order into discounts factory
        //because we want apply discounts on this order
        //in administraiton. Order object must not be available
        //on frontend in discounts!
        if ( Admin::isAdmin() || $discounts == true ) {
            Discounts::setOrder($order);
        }

        $this->order = $order;

        //Set order currency
        Store::setCurrency($order->currency);

        if ( $items === true ){
            $this->setOrderCartItems($order);
        }

        return $this;
    }

    /**
     * Returns order
     *
     * @return  null|Admin\Eloquent\AdminModel
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Scope the whole DataStore cache bucket of this class by the current order.
     * Without this, a bulk admin action processing multiple orders in one request
     * would reuse the first order's cached active mutators (delivery, payment
     * method, ...) for every other order in that same request.
     *
     * @return  string
     */
    protected function getStoreKey()
    {
        $order = $this->getOrder();

        return get_class($this).($order ? '.'.$order->getKey() : '');
    }

    private function saveOrCreateOrder(CartCollection $items)
    {
        $this->getOrder()->save();

        //Add items into order
        $this->addItemsIntoOrder();

        $this->addDiscountsData($items, 'mutateOrderRowAfter');
    }

    /**
     * Add items from cart into order
     *
     * @return this;
     */
    public function addItemsIntoOrder()
    {
        $items = $this->getCartItems(true);

        foreach ($items as $item) {
            $identifier = $item->getIdentifierClass();

            $data = $identifier->onOrderItemCreate($item);

            $this->assignParentCartItem($data, $items, $item);

            foreach ($this->getActiveMutators() as $mutator) {
                //Mutate price by anonymous price mutators
                if ( method_exists($mutator, 'mutateOrderItem') ) {
                    $data = $mutator->mutateOrderItem($item, $data);
                }
            }

            $item->order_item_id = $this->order->items()->create($data);
        }

        //Add all order items into order
        $this->addDiscountableItemsIntoOrder();

        //Coundown stock if is allowed
        if ( $this->stockSync == true && config('admin_eshop.stock.countdown.on_order_create', true) == true ) {
            $this->order->syncStock('-', 'order.new');
        }

        return $this;
    }

    /**
     * Add parent order item id if cart item was assigned to other cart item
     *
     * @param  array  &$data
     * @param  collection  $items
     * @param  CartItem  $item
     */
    private function assignParentCartItem(array &$data, $items, CartItem $cartItem)
    {
        if ( $cartItem->parentIdentifier ){
            //We need find parent owner of givien child cart item
            $parentCartItem = $items->filter(function($parentItem) use ($cartItem) {
                return $parentItem->isParentOwner($cartItem);
            })->first();

            //We need receive order_item_id from parent cart item.
            //Parent cart item must be saved before child cart items.
            //Otherwise error will occur. What is ok, because child cannot be saved before
            //parent item. CartColelction should order and return items in correct order.
            $data['order_item_id'] = $parentCartItem->order_item_id->getKey();
        }

        return $this;
    }

    public function addDiscountableItemsIntoOrder()
    {
        $order = $this->getOrder();

        $newItems = $this->buildDiscountableItems();

        //One extra select is cheaper than deleting and recreating these rows on every
        //recalculation. If the discount items we would create already match what is
        //persisted, we leave the database untouched. This also avoids re-firing their
        //model rules (eg. RebuildOrderOnItemChange), which recalculate the order again.
        $existingItems = $order->items()
                               ->where('identifier', 'discount')
                               ->get(['id', 'name', 'quantity', 'price', 'price_vat']);

        if ( $this->discountItemsAreSame($existingItems, $newItems) ) {
            return $this;
        }

        //Discount items changed - replace them all.
        $order->items()->where('identifier', 'discount')->delete();

        foreach ($newItems as $orderItem) {
            //We are already inside a recalculation here, so we silence the created/updated
            //rules of the new item to prevent RebuildOrderOnItemChange from looping back
            //into calculatePrices.
            $order->items()
                  ->make($orderItem)
                  ->silentRules(['created', 'updated'])
                  ->save();
        }

        return $this;
    }

    /**
     * Build the discount order items which should be present on the order,
     * without touching the database.
     *
     * @return  array
     */
    private function buildDiscountableItems()
    {
        $items = [];

        foreach (Discounts::getDiscounts() as $discount) {
            //TODO: support multiple operators
            foreach ($discount->getAllOperators() as $operatorParam) {
                $operator = $operatorParam['operator'];

                // If value is callable, we need skip this discount. TODO: Fix?
                if ( is_callable($operatorParam['value']) ) {
                    continue;
                }

                $value = Store::calculateFromDefaultCurrency($operatorParam['value']);

                if ( ! $discount->hasSumPriceOperator($operator) ) {
                    continue;
                }

                $orderItem = [
                    'identifier' => 'discount',
                    'discountable' => false,
                    'name' => $discount->getName() ?: _('Zľava'),
                    'quantity' => 1,
                    'price' => $value * ($operator == '-' ? -1 : 1),
                    'vat' => Store::getDefaultVat(),
                    'price_vat' => Store::priceWithVat($value) * ($operator == '-' ? -1 : 1),
                ];

                if ( method_exists($discount, 'createDiscountableItem') ){
                    $orderItem = $discount->createDiscountableItem($orderItem, $operatorParam);
                }

                $items[] = $orderItem;
            }
        }

        return $items;
    }

    /**
     * Compare already persisted discount items with the freshly built ones,
     * so we can skip pointless delete/recreate when nothing changed.
     *
     * @param  \Illuminate\Support\Collection  $existingItems
     * @param  array  $newItems
     * @return  bool
     */
    private function discountItemsAreSame($existingItems, array $newItems)
    {
        if ( count($existingItems) !== count($newItems) ) {
            return false;
        }

        //Compact, order-insensitive signature of the money-relevant fields.
        //Normalize numbers to avoid false mismatches from float noise or negative zero
        //(eg. 0 * -1 = -0.0, which stringifies differently than 0.0).
        $number = function($value) {
            $rounded = round((float) $value, 4);

            return $rounded == 0.0 ? '0' : (string) $rounded;
        };

        $signature = function($name, $quantity, $price, $priceVat) use ($number) {
            return $name.'|'.(int)$quantity.'|'.$number($price).'|'.$number($priceVat);
        };

        $existing = $existingItems
                        ->map(function($item) use ($signature) {
                            return $signature($item->name, $item->quantity, $item->price, $item->price_vat);
                        })
                        ->sort()->values()->all();

        $new = collect($newItems)
                        ->map(function($item) use ($signature) {
                            return $signature($item['name'] ?? '', $item['quantity'] ?? 0, $item['price'] ?? 0, $item['price_vat'] ?? 0);
                        })
                        ->sort()->values()->all();

        return $existing === $new;
    }

    /**
     * Add prices into order
     *
     * @param  array  $row
     * @return  array
     */
    public function addOrderPrices(CartCollection $items)
    {
        $order = $this->getOrder();

        $summary = $items->getSummary(true);

        $order->price = $summary['priceWithoutVat'] ?? 0;
        $order->price_vat = $summary['priceWithVat'] ?? 0;

        return $this;
    }


    /**
     * Add prices into order
     *
     * @param  array  $row
     * @return  array
     */
    public function buildOrder(CartCollection $items)
    {
        $this->addCurrency();
        $this->addLanguage();
        $this->addOrderPrices($items);
        $this->addDiscountsData($items);

        foreach ($this->getActiveMutators() as $mutator) {
            if ( method_exists($mutator, 'mutateOrder') ) {
                $mutator->mutateOrder($this->getOrder(), $mutator->getActiveResponse());
            }
        }

        $this->addClientIntoOrder();
        $this->addDefaultStatus();

        return $this;
    }

    private function addClientIntoOrder()
    {
        if ( Admin::isFrontend() && client() && !$this->getOrder()->client_id ) {
            $this->getOrder()->client_id = client()->getKey();
        }
    }

    private function addDefaultStatus()
    {
        if ( config('admin_eshop.order.status') === false ){
            return;
        }

        $order = $this->getOrder();

        if ( !$order->status_id ) {
            $order->status_id = OrdersStatus::where('default', true)->first()?->getKey();
        }
    }

    private function addCurrency()
    {
        if ( !$this->getOrder()->currency_id ){
            $currency = Store::getCurrency();

            $this->getOrder()->currency_id = $currency?->getKey();
            $this->getOrder()->setRelation('currency', $currency);
        }
    }

    private function addLanguage()
    {
        if ( Admin::isEnabledLocalization() && !$this->getOrder()->language_id ) {
            $this->getOrder()->language_id = Localization::get()->getKey();
        }
    }

    /**
     * Send email to client
     *
     * @return  void
     */
    public function sentClientEmail(Invoice $invoice = null, $silent = true)
    {
        try {
            $order = $this->getOrder();

            $message = $order->getClientEmailMessage();

            $items = $this->getCartItems();

            Mail::to($order->email)->send(
                new OrderReceived($order, $items, $message, $invoice)
            );
        } catch (Exception $error){
            if ( $silent === false ){
                throw $error;
            }

            Log::error($error);

            $order->log()->create([
                'type' => 'error',
                'code' => 'email-client-error'
            ]);

            //Debug
            if ( $this->isDebug() ) {
                throw $error;
            }
        }

        return $this;
    }

    /**
     * Send email into store
     *
     * @return  void
     */
    public function sentStoreEmail()
    {
        $order = $this->getOrder();

        $emails = array_unique(array_filter(array_wrap($order->getStoreEmailReceivers())));
        if ( count($emails) == 0 ){
            return;
        }

        $message = $order->getStoreEmailMessage();

        $items = $this->getCartItems();

        try {
            Mail::to($emails)->send(
                (new OrderReceived($order, $items, $message))->setOwner(true)
            );
        } catch (Exception $error){
            Log::error($error);

            $order->log()->create([
                'type' => 'error',
                'code' => 'email-store-error'
            ]);

            //Debug
            if ( $this->isDebug() ) {
                throw $error;
            }
        }

        return $this;
    }

    /**
     * Add discounts additional fields
     *
     * @param  CartCollection  $items
     * @param  string  $callbackType
     *
     * @return array
     */
    public function addDiscountsData($items, $callbackType = 'mutateOrderRow')
    {
        $data = [];

        foreach (Discounts::getDiscounts() as $discount) {
            if ( method_exists($discount, $callbackType) ) {
                $discount->{$callbackType}($this->getOrder(), $items);
            }

            $data[$discount->getKey()] = $discount->getSerializedResponse();
        }

        //Save all discounts responses
        $this->getOrder()->discount_data = $data;

        return $this;
    }

    /**
     * Return
     *
     * @param
     * @return Response
     */
    public function errorResponse()
    {
        return response()->json([
            'orderErrors' => $this->getErrorMessages(),
            'orderInvalidValidators' => array_map(function($validator){
                return class_basename(get_class($validator));
            }, $this->getInvalidValidators()),
            'cart' => Cart::fullCartResponse(),
        ], 422);
    }

    public function isDebug()
    {
        return app()->environment('local') && env('APP_DEBUG') == true && env('APP_STORE_DEBUG') == true;
    }

    public function getOrderMessage($key)
    {
        return config('admin_eshop.order.codes.'.$key);
    }

    /**
     * Set stock countdown state on new order
     *
     * @param  bool  $state
     */
    public function setStockSync($state)
    {
        $this->stockSync = $state;

        return $this;
    }
}

?>