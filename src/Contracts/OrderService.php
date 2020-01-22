<?php

namespace AdminEshop\Contracts;

use Admin;
use AdminEshop\Contracts\Collections\CartCollection;
use AdminEshop\Contracts\Order\HasRequest;
use AdminEshop\Contracts\Order\HasValidation;
use AdminEshop\Contracts\Order\Mutators\ClientDataMutator;
use AdminEshop\Contracts\Order\Mutators\DeliveryMutator;
use AdminEshop\Contracts\Order\Mutators\PaymentMethodMutator;
use AdminEshop\Mail\OrderReceived;
use AdminEshop\Models\Orders\Order;
use Admin\Core\Contracts\DataStore;
use Cart;
use Discounts;
use Store;
use Mail;

class OrderService
{
    use DataStore,
        HasRequest,
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
        ClientDataMutator::class,
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

        //Build order with all attributes
        $this->rebuildOrder(Cart::all());

        $order->save();

        return $this;
    }

    /**
     * Store row into session
     *
     * @return  this
     */
    public function storeIntoSession()
    {
        $requestData = $this->getRequestData();

        (new ClientDataMutator)->setClientData($requestData);

        return $this;
    }

    /**
     * Get row data from session
     *
     * @return  this
     */
    public function getFromSession()
    {
        return (new ClientDataMutator)->getClientData();
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
                $price = $mutator->mutatePrice($mutator->getActiveResponse(), $price, $withTax, $this->getOrder() ?: new Order);
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

        $order->price = $items->count() == 0 ? 0 : $summary['priceWithoutTax'];
        $order->price_tax = $items->count() == 0 ? 0 : $summary['priceWithTax'];

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
        $this->addClientIntoOrder();

        return $order;
    }

    private function addClientIntoOrder()
    {
        if ( client() ) {
            $this->getOrder()->client_id = client()->getKey();
        }
    }

    /**
     * Send email to client
     *
     * @return  void
     */
    public function sentClientEmail()
    {
        $order = $this->getOrder();

        $message = sprintf(_('Vaša objednávka č. %s zo dňa %s bola úspešne prijatá.'), $order->number, $order->created_at->format('d.m.Y'));

        Mail::to($order->email)->send(new OrderReceived($order, $message));
    }

    /**
     * Send email into store
     *
     * @return  void
     */
    public function sentStoreEmail()
    {
        if ( $email = Store::getSettings()->email ) {
            $order = $this->getOrder();

            $message = sprintf(_('Gratulujeme! Obržali ste objednávku č. %s.'), $order->number);

            Mail::to($email)->send(new OrderReceived($order, $message));
        }
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
     * Returns all available order mutators
     *
     * @return  array
     */
    public function getMutators()
    {
        return $this->cache('orderMutators', function(){
            return array_map(function($item){
                return new $item;
            }, $this->mutators);
        });
    }

    /**
     * Returns active mutators for given order
     *
     * @return  array
     */
    public function getActiveMutators()
    {
        return array_filter(array_map(function($mutator){
            if ( Admin::isAdmin() ) {
                $response = $mutator->isActiveInAdmin($this->getOrder());
            } else {
                $response = $mutator->isActive($this->getOrder());
            }

            //Apply all discounts on given reponse if is correct type
            //Sometimes mutator may return Discountable admin model.
            //So we need apply discounts to this model
            Cart::addCartDiscountsIntoModel($response);

            //If no response has been given, skip this mutator
            if ( ! $response ) {
                return;
            }

            $mutator->setActiveResponse($response);

            return $mutator;
        }, $this->getMutators()));
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