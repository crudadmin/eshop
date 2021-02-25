<?php

namespace AdminEshop\Contracts\Order\Mutators;

use AdminEshop\Contracts\Cart\Concerns\ActiveInterface;
use AdminEshop\Contracts\Cart\Concerns\ActiveResponse;
use AdminEshop\Contracts\Cart\Concerns\DriverSupport;
use AdminEshop\Models\Orders\Order;
use Admin\Core\Contracts\DataStore;

class Mutator implements ActiveInterface
{
    use DataStore,
        DriverSupport,
        ActiveResponse;

    /**
     * Register order validator with this mutators
     *
     * @var  array
     */
    protected $validators = [];

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
     * Mutators responses are not saved and supported in order yet
     * This method is only to the future
     *
     * @return  bool
     */
    public function isCachableResponse()
    {
        return false;
    }

    /**
     * Add delivery field into order row
     *
     * @param  array  $row
     *
     * @return void
     */
    public function mutateOrder(Order $order, $activeResponse)
    {

    }

    /**
     * Mutate sum price of order/cart
     *
     * @param  AdminEshop\Models\Delivery\Delivery|null  $delivery
     * @param  float  $price
     * @param  bool  $withVat
     *
     * @return  void
     */
    public function mutatePrice($activeResponse, $price, bool $withVat, Order $order)
    {
        return $price;
    }

    /**
     * Mutation of cart response request
     *
     * @param  $response
     *
     * @return  array
     */
    public function mutateCartResponse($response) : array
    {
        return $response;
    }

    /**
     * Mutation of full cart response request
     *
     * @param  $response
     *
     * @return  array
     */
    public function mutateFullCartResponse($response) : array
    {
        return $response;
    }

    /**
     * Returns mutation validators
     *
     * @return  array
     */
    public function getValidators()
    {
        return $this->validators;
    }
}

?>