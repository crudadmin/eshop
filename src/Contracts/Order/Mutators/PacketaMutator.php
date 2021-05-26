<?php

namespace AdminEshop\Contracts\Order\Mutators;

use Admin;
use AdminEshop\Contracts\Delivery\Packeta\PacketaShipping;
use AdminEshop\Contracts\Order\Mutators\Mutator;
use AdminEshop\Contracts\Order\Validation\PacketaValidator;
use AdminEshop\Models\Orders\Order;
use Cart;
use OrderService;
use Store;

class PacketaMutator extends Mutator
{
    /**
     * Register validator with this mutators
     *
     * @var  array
     */
    protected $validators = [
        PacketaValidator::class,
    ];

    /*
     * driver key for delivery
     */
    const PACKETA_POINT_KEY = 'packeta';

    /**
     * Returns if mutators is active
     * And sends state to other methods
     *
     * @return  bool
     */
    public function isActive()
    {
        if ( config('admineshop.delivery.packeta') == false ) {
            return false;
        }

        //If is choosen packeta shipping and
        if ( $this->isPacketaShipping() && $point = $this->getSelectedPoint() ) {
            return [
                'packeta_point' => $point,
            ];
        }
    }

    /**
     * Check if selected delivery is packeta type
     *
     * @return  bool
     */
    public function isPacketaShipping()
    {
        if ( !($delivery = $this->getDeliveryMutator()->getSelectedDelivery()) ) {
            return false;
        }

        if ( !($shippingProvider = OrderService::getShippingProvider($delivery->getKey())) ){
            return false;
        }

        return $shippingProvider instanceof PacketaShipping;
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
        $order->fill([
            'packeta_point' => array_intersect_key($activeResponse['packeta_point'], array_flip(['id', 'url', 'name', 'place'])),
        ]);
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
            'packetaPoint' => $this->getSelectedPoint(),
        ]);
    }

    /*
     * Get delivery from driver
     */
    public function getSelectedPoint()
    {
        return $this->getDriver()->get(self::PACKETA_POINT_KEY);
    }

    /**
     * Save delivery into driver
     *
     * @param  int|null  $id
     * @param  bool  $persist
     * @return  this
     */
    public function setSelectedPoint($data = null, $persist = true)
    {
        $this->getDriver()->set(self::PACKETA_POINT_KEY, $data, $persist);

        return $this;
    }
}

?>