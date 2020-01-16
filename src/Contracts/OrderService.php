<?php

namespace AdminEshop\Contracts;

use Admin;
use AdminEshop\Contracts\Collections\CartCollection;
use AdminEshop\Contracts\Order\HasRequest;
use AdminEshop\Contracts\Order\HasSession;
use AdminEshop\Contracts\Order\HasValidation;
use AdminEshop\Contracts\Order\Mutators\DeliveryMutator;
use AdminEshop\Contracts\Order\Mutators\PaymentMethodMutator;
use Admin\Core\Contracts\DataStore;
use Cart;
use Discounts;
use Store;

class OrderService
{
    use DataStore,
        HasRequest,
        HasSession,
        HasValidation;

    /**
     * Order row
     *
     * @var  Admin\Eloquent\AdminModel|null
     */
    protected $order;

    /**
     * Available order mutators
     *
     * @var  array
     */
    protected $mutators = [
        DeliveryMutator::class,
        PaymentMethodMutator::class,
    ];

    /**
     * Store order int osession
     *
     * @return  this
     */
    public function store()
    {
        $order = clone Admin::getModelByTable('orders');

        $this->setOrder($order);

        //Build order from store request and all
        $this->buildOrderFromRequest();

        //Build order with all attributes
        $this->rebuildOrder(Cart::all());

        $order->save();

        return $this;
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
        //in administraiton
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

    /**
     * Returns order row data
     *
     * @return  array
     */
    public function buildOrderFromRequest()
    {
        $row = $this->getRequestData();

        $this->getOrder()->fill($row);

        return $this;
    }

    /**
     * Add items from cart into order
     *
     * @return this;
     */
    public function addItemsIntoOrder()
    {
        $cart = Cart::all();

        foreach ($cart as $item) {
            $product = $item->getItemModel();

            $this->order->items()->create([
                'identifier' => $item->getIdentifierClass()->getName(),
                'product_id' => $item->id,
                'variant_id' => $item->variant_id,
                'quantity' => $item->quantity,
                'default_price' => $product->defaultPriceWithoutTax,
                'price' => $product->priceWithoutTax,
                'tax' => Store::getTaxValueById($product->tax_id),
                'price_tax' => $product->priceWithTax,
            ]);
        }

        $this->order->syncWarehouse('-', 'order.new');

        return $this;
    }

    /**
     * Add additional prices into order sum.
     *
     * @param  int/float  $price
     * @param  bool  $withTax
     */
    public function addAdditionalPaymentsIntoSum($price, bool $withTax)
    {
        foreach ($this->getActiveMutators() as $mutator) {
            if ( method_exists($mutator, 'mutatePrice') ) {
                $price = $mutator->mutatePrice($mutator->getActiveResponse(), $price, $withTax, $this->getOrder());
            }
        }

        return $price;
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

        $order->price = $summary['priceWithoutTax'];
        $order->price_tax = $summary['priceWithTax'];

        return $this;
    }

    /**
     * Add prices into order
     *
     * @param  array  $row
     * @return  array
     */
    public function rebuildOrder(CartCollection $items)
    {
        $order = $this->getOrder();

        $this->addOrderPrices($items);
        $this->addDiscountsData($items);
        $this->fireMutators();

        return $order;
    }

    /**
     * Fire all registered mutators and apply them on order
     *
     * @return  this
     */
    public function fireMutators()
    {
        foreach ($this->getActiveMutators() as $mutator) {
            if ( method_exists($mutator, 'mutateOrder') ) {
                $mutator->mutateOrder($this->getOrder(), $mutator->getActiveResponse());
            }
        }

        return $this;
    }

    /**
     * Returns active mutators for given order
     *
     * @return  array
     */
    public function getActiveMutators()
    {
        $mutators = $this->cache('orderMutators', function(){
            return array_map(function($item){
                return new $item;
            }, $this->mutators);
        });

        return array_filter(array_map(function($mutator){
            if ( Admin::isAdmin() ) {
                $response = $mutator->isActive($this->getOrder());
            } else {
                $response = $mutator->isActive($this->getOrder());
            }

            $mutator->setActiveResponse($response);

            return $mutator;
        }, $mutators));
    }

    /**
     * Add discounts additional fields
     *
     * @param  CartCollection  $items
     * @return array
     */
    public function addDiscountsData($items)
    {
        foreach (Discounts::getDiscounts() as $discount) {
            if ( method_exists($discount, 'mutateOrderRow') ) {
                $discount->mutateOrderRow($this->getOrder(), $items);
            }
        }

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
            'cart' => Cart::fullCartResponse(),
        ], 422);
    }

    /**
     * Register new order mutator
     *
     * @param  string  $namespace
     */
    public function addMutator($namespace)
    {
        $this->mutators[] = $namespace;
    }
}

?>