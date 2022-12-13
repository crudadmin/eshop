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
    const CLIENT_KEY = 'clientData';

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
     * Mutate base cart response
     *
     * @param  array  $response
     * @return  array
     */
    public function mutateCartResponse($response) : array
    {
        if ( config('admineshop.client.in_cart_response') == true && client() ){
            $response['client'] = client()->setClientResponse();
        }

        return $response;
    }

    /**
     * Mutation of cart response request
     *
     * @param  $response
     * @return  array
     */
    public function mutateFullCartResponse($response) : array
    {
        return array_merge($response, [
            'clientData' => $this->getClientData(),
        ]);
    }

    /**
     * Save given client data into session
     *
     * @var  array|null $row
     * @var  bool $persist
     *
     * @return  void
     */
    public function setClientData($row = null, $persist = true)
    {
        $this->getDriver()->set(self::CLIENT_KEY, $row, $persist);
    }

    /**
     * Get row data from session
     *
     * @return  this
     */
    public function getClientData($key = null, $default = null)
    {
        $data = $this->getDriver()->get(self::CLIENT_KEY, null);

        if ( is_null($key) === false ){
            $value = $data[$key] ?? null;

            if ( is_null($value) ){
                return $default;
            }

            return $value;
        }

        return $data;
    }
}

?>