<?php

namespace AdminEshop\Contracts\Order\Mutators;

use AdminEshop\Models\Orders\Order;

class Mutator
{
    /**
     * Response from isActive/isActiveInAdmin methods
     *
     * @var  mixed
     */
    protected $activeResponse;

    /**
     * Returns if mutators is active
     * And sends state to other methods
     *
     * @return  bool
     */
    public function isActive()
    {
        return false;
    }

    /**
     * Returns if mutators is active in administration
     * And sends state to other methods
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
    public function mutateOrder(Order $order, $activeResponse)
    {

    }

    /**
     * Mutate sum price of order/cart
     *
     * @param  AdminEshop\Models\Delivery\Delivery|null  $delivery
     * @param  float  $price
     * @param  bool  $withTax
     * @return  void
     */
    public function mutatePrice($activeResponse, $price, bool $withTax)
    {
        return $price;
    }

    /**
     * Set activeResponse
     *
     * @var mixed $response
     *
     * @return  bool
     */
    public function setActiveResponse($response)
    {
        $this->activeResponse = $response;
    }

    /**
     * Returns active response
     *
     * @return  mixed
     */
    public function getActiveResponse()
    {
        return $this->activeResponse;
    }
}

?>