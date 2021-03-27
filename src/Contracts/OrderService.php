<?php

namespace AdminEshop\Contracts;

use Admin;
use AdminEshop\Contracts\Collections\CartCollection;
use AdminEshop\Contracts\Order\Concerns\HasMutators;
use AdminEshop\Contracts\Order\Concerns\HasOrderProcess;
use AdminEshop\Contracts\Order\Concerns\HasPayments;
use AdminEshop\Contracts\Order\Concerns\HasProviders;
use AdminEshop\Contracts\Order\Concerns\HasShipping;
use AdminEshop\Contracts\Order\HasRequest;
use AdminEshop\Contracts\Order\HasValidation;
use AdminEshop\Contracts\Order\Mutators\ClientDataMutator;
use AdminEshop\Mail\OrderReceived;
use AdminEshop\Models\Orders\Order;
use Admin\Core\Contracts\DataStore;
use Cart;
use Discounts;
use Gogol\Invoices\Model\Invoice;
use Mail;
use Store;
use Exception;
use Log;

class OrderService
{
    use DataStore,
        HasProviders,
        HasRequest,
        HasPayments,
        HasValidation,
        HasMutators,
        HasOrderProcess,
        HasShipping;

    /**
     * Order row
     *
     * @var  Admin\Eloquent\AdminModel|null
     */
    protected $order;

    /*
     * Returns if invoices support is allowed
     */
    public function hasInvoices()
    {
        return config('admineshop.invoices', false) === true;
    }

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
        $items = Cart::allWithMutators();

        foreach ($items as $item) {
            $product = $item->getItemModel();

            $this->order->items()->create([
                'identifier' => $item->getIdentifierClass()->getName(),
                'discountable' => $item->hasDiscounts(),
                'product_id' => $item->id,
                'variant_id' => $item->variant_id,
                'quantity' => $item->quantity,
                'default_price' => $product->defaultPriceWithoutVat,
                'price' => $product->priceWithoutVat,
                'vat' => Store::getVatValueById($product->vat_id),
                'price_vat' => $product->priceWithVat,
            ]);
        }

        //Add all order items into order
        $this->addDiscountableItemsIntoOrder();

        $this->order->syncStock('-', 'order.new');

        return $this;
    }

    public function addDiscountableItemsIntoOrder()
    {
        $discounts = Discounts::getDiscounts();

        $order = $this->getOrder();

        foreach ($discounts as $discount) {
            if ( ! $discount->hasSumPriceOperator() ) {
                continue;
            }

            $order->items()->create([
                'identifier' => 'discount',
                'discountable' => false,
                'name' => $discount->getName() ?: _('Zľava'),
                'quantity' => 1,
                'price' => $discount->value * ($discount->operator == '-' ? -1 : 1),
                'vat' => Store::getDefaultVat(),
                'price_vat' => Store::priceWithVat($discount->value) * ($discount->operator == '-' ? -1 : 1),
            ]);
        }
    }

    /**
     * Add additional prices into order sum.
     *
     * @param  int/float  $price
     * @param  bool  $withVat
     */
    public function addAdditionalPaymentsIntoSum($price, bool $withVat)
    {
        foreach ($this->getActiveMutators() as $mutator) {
            //Mutate price by anonymous price mutators
            if ( method_exists($mutator, 'mutatePrice') ) {
                $price = $mutator->mutatePrice(
                    $mutator->getActiveResponse(),
                    $price,
                    $withVat,
                    $this->getOrder() ?: new Order
                );
            }

            //Add price from additional cart items from mutators which will be inserted into order
            if ( method_exists($mutator, 'addCartItems') ) {
                $addItems = $mutator->addCartItems($mutator->getActiveResponse())
                                    ->toCartFormat();

                $addItemsSummary = $addItems->getSummary();

                $price += (@$addItemsSummary[$withVat ? 'priceWithVat' : 'priceWithoutVat'] ?: 0);
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

        $order->price = $items->count() == 0 ? 0 : $summary['priceWithoutVat'];
        $order->price_vat = $items->count() == 0 ? 0 : $summary['priceWithVat'];

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

        $message = sprintf(_('Vaša objednávka č. %s zo dňa %s bola úspešne prijatá.'), $order->number, $order->created_at->format('d.m.Y'));

        try {
            Mail::to($order->email)->send(
                new OrderReceived($order, $message, $invoice)
            );
        } catch (Exception $error){
            Log::error($error);

            $order->log()->create([
                'type' => 'error',
                'code' => 'email-client-error'
            ]);
        }
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

            try {
                Mail::to($email)->send(
                    (new OrderReceived($order, $message))->setOwner(true)
                );
            } catch (Exception $error){
                Log::error($error);

                $order->log()->create([
                    'type' => 'error',
                    'code' => 'email-store-error'
                ]);
            }
        }
    }

    /**
     * Add discounts additional fields
     *
     * @param  CartCollection  $items
     * @return array
     */
    public function addDiscountsData($items)
    {
        $data = [];

        foreach (Discounts::getDiscounts() as $discount) {
            if ( method_exists($discount, 'mutateOrderRow') ) {
                $discount->mutateOrderRow($this->getOrder(), $items);
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
            'cart' => Cart::fullCartResponse(),
        ], 422);
    }
}

?>