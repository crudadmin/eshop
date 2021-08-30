<?php

namespace AdminEshop\Contracts;

use Admin;
use AdminEshop\Contracts\CartItem;
use AdminEshop\Contracts\Collections\CartCollection;
use AdminEshop\Contracts\Order\Concerns\HasMutators;
use AdminEshop\Contracts\Order\Concerns\HasMutatorsForward;
use AdminEshop\Contracts\Order\Concerns\HasOrderProcess;
use AdminEshop\Contracts\Order\Concerns\HasPayments;
use AdminEshop\Contracts\Order\Concerns\HasProviders;
use AdminEshop\Contracts\Order\Concerns\HasShipping;
use AdminEshop\Contracts\Order\HasRequest;
use AdminEshop\Contracts\Order\HasValidation;
use AdminEshop\Events\OrderCreated;
use AdminEshop\Mail\OrderReceived;
use AdminEshop\Models\Orders\Order;
use Admin\Core\Contracts\DataStore;
use Cart;
use Discounts;
use Exception;
use Gogol\Invoices\Model\Invoice;
use Log;
use Mail;
use Store;

class OrderService
{
    use DataStore,
        HasProviders,
        HasRequest,
        HasPayments,
        HasValidation,
        HasMutators,
        HasMutatorsForward,
        HasOrderProcess,
        HasShipping;

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
        return config('admineshop.invoices', false) === true;
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

        //Todo:: check if here shouldn't go items with mutators, maybe price may be wrong without mutators if some are availeble
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
     * This cart items will be inserted into order
     *
     * @return  CartCollection
     */
    public function getCartItems()
    {
        if ( is_null($this->cartItems) ){
            return Cart::all();
        }

        return $this->cartItems->toCartFormat();
    }


    /**
     * Set order
     *
     * @param  AdminModel|null  $order
     */
    public function setOrder($order)
    {
        //We need register order into discounts factory
        //because we want apply discounts on this order
        //in administraiton. Order object must not be available
        //on frontend in discounts!
        if ( Admin::isAdmin() ) {
            Discounts::setOrder($order);
        }

        $this->order = $order;

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
        $items = Cart::addItemsFromMutators($this->getCartItems(), 'addHiddenCartItems');

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
        if ( $this->stockSync == true && config('admineshop.stock.countdown.on_order_create', true) == true ) {
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
        $discounts = Discounts::getDiscounts();

        $order = $this->getOrder();

        foreach ($discounts as $discount) {
            //TODO: support multiple operators
            foreach ($discount->getAllOperators() as $operatorParam) {
                $operator = $operatorParam['operator'];
                $value = $operatorParam['value'];

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

                $order->items()->create($orderItem);
            }
        }

        return $this;
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

        $order->price = $items->count() == 0 ? 0 : ($summary['priceWithoutVat'] ?? 0);
        $order->price_vat = $items->count() == 0 ? 0 : ($summary['priceWithVat'] ?? 0);

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
        $this->addOrderPrices($items);
        $this->addDiscountsData($items);

        foreach ($this->getActiveMutators() as $mutator) {
            if ( method_exists($mutator, 'mutateOrder') ) {
                $mutator->mutateOrder($this->getOrder(), $mutator->getActiveResponse());
            }
        }

        $this->addClientIntoOrder();

        return $this;
    }

    private function addClientIntoOrder()
    {
        if ( Admin::isFrontend() && client() && !$this->getOrder()->client_id ) {
            $this->getOrder()->client_id = client()->getKey();
        }
    }

    /**
     * Generate invoice for order
     *
     * @return  Invoice|null
     */
    public function makeInvoice($type = 'proform')
    {
        if ( ! $this->hasInvoices() ) {
            return;
        }

        //Generate proform
        return $this->getOrder()->makeInvoice($type);
    }

    /**
     * Send email to client
     *
     * @return  void
     */
    public function sentClientEmail(Invoice $invoice = null)
    {
        $order = $this->getOrder();

        $message = $order->getClientEmailMessage();

        $items = $this->getCartItems();

        try {
            Mail::to($order->email)->send(
                new OrderReceived($order, $items, $message, $invoice)
            );
        } catch (Exception $error){
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
        if ( !($email = Store::getSettings()->email) ) {
            return;
        }

        $order = $this->getOrder();

        $message = $order->getStoreEmailMessage();

        $items = $this->getCartItems();

        try {
            Mail::to($email)->send(
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
        return app()->environment('local') && env('APP_DEBUG') == true;
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