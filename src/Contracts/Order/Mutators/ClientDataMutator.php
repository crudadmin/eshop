<?php

namespace AdminEshop\Contracts\Order\Mutators;

use AdminEshop\Contracts\Order\Mutators\Mutator;
use AdminEshop\Contracts\Order\Validation\ClientDataValidator;
use AdminEshop\Models\Orders\Order;
use OrderService;
use Cart;

class ClientDataMutator extends Mutator
{
    /**
     * Register validator with this mutators
     *
     * @var  array
     */
    protected $validators = [
        ClientDataValidator::class,
    ];

    /**
     * Session key of stored order client data
     *
     * @var  string
     */
    protected $clientKey = 'clientData';

    /**
     * Returns if mutators is active
     * And sends state to other methods
     *
     * @return  bool
     */
    public function isActive()
    {
        return OrderService::getRequestData() ?: $this->getClientData();
    }

    /**
     * This client data mutator is not available in administration
     * We doesn't want to mutate data in existing order.
     *
     * @return  bool
     */
    public function isActiveInAdmin(Order $order)
    {
        return false;
    }

    /**
     * Add delivery field into order row
     *
     * @param  array  $row
     * @return array
     */
    public function mutateOrder(Order $order, $row)
    {
        $order->fill($row);
    }

    /**
     * Mutation of cart response request
     *
     * @param  $response
     * @return  array
     */
    public function mutateCartResponse($response) : array
    {
        return array_merge($response, [
            'clientData' => $this->getClientData(),
        ]);
    }

    /**
     * Save given client data into session
     *
     * @var  array|null $row
     *
     * @return  void
     */
    public function setClientData($row = null)
    {
        Cart::getDriver()->set($this->clientKey, $row);
    }

    /**
     * Get row data from session
     *
     * @return  this
     */
    public function getClientData()
    {
        return Cart::getDriver()->get($this->clientKey, null);
    }
}

?>