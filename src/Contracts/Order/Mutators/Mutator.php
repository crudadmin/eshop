<?php

namespace AdminEshop\Contracts\Order\Mutators;

use AdminEshop\Contracts\CartItem;
use AdminEshop\Contracts\Cart\Concerns\ActiveInterface;
use AdminEshop\Contracts\Cart\Concerns\ActiveResponse;
use AdminEshop\Contracts\Cart\Concerns\DriverSupport;
use AdminEshop\Contracts\Collections\CartCollection;
use AdminEshop\Contracts\Order\Concerns\HasMutatorsForward;
use AdminEshop\Models\Orders\Order;
use Admin\Core\Contracts\DataStore;
use OrderService;

class Mutator implements ActiveInterface
{
    use DataStore,
        DriverSupport,
        ActiveResponse,
        HasMutatorsForward;

    /**
     * Register order validator with this mutators
     *
     * @var  array
     */
    protected $validators = [];

    /**
     * Get cart items
     *
     * @var  array
     */
    protected $cartItems = [];

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
     * Mutate OrderItem before create
     */
    public function mutateOrderItem(CartItem $item, array $data) : array
    {
        return $data;
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

    public function setCartItems(CartCollection $items)
    {
        $this->cartItems = $items;

        return $this;
    }

    public function getCartItems()
    {
        return $this->cartItems;
    }

    public function bootMutator()
    {
        $cartItems = OrderService::getCartItems();

        $this->setCartItems($cartItems);

        return $this;
    }
}

?>