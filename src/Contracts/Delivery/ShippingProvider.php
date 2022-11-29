<?php

namespace AdminEshop\Contracts\Delivery;

use AdminEshop\Contracts\Order\OrderProvider;
use AdminEshop\Models\Delivery\Delivery;
use Admin\Helpers\Button;
use Illuminate\Support\Collection;
use Admin;

class ShippingProvider extends OrderProvider
{


    /**
     * Admin order buttons
     *
     * @return  array
     */
    public function buttons()
    {
        return [];
    }

    public function getKey()
    {
        return class_basename($this);
    }

    /*
     * Has shipping export?
     */
    public function isExportable()
    {
        return false;
    }

    public static function export(Collection $orders)
    {
        // return 'string';
    }

    public function getPackageWeight()
    {
        $options = $this->getOptions();

        //Calculate custom order weight
        if ( ($order = $this->getOrder()) && method_exists($order, 'getPackageWeight') ){
            $weight = $order->getPackageWeight($this, $options);

            if ( is_null($weight) === false ) {
                return $weight;
            };
        }

        return ($options['weight'] ?? $options['default_weight'] ?? null);
    }

    public function isCashDelivery()
    {
        $order = $this->getOrder();

        if ( $order->paymentMethod && $order->paymentMethod->isCashDelivery() === true ){
            return true;
        }

        return false;
    }

    /**
     * On shipping send button question action
     *
     * @param  Button  $button
     *
     * @return  Button
     */
    public function buttonQuestion(Button $button)
    {
       return $button->title('Prajete si pokračovať?')
                     ->warning('Balík bude automatický odoslaný do dopravnej služby');
    }

    /**
     * Pass and mutate shipping options from pressed button question component
     *
     * @return  []
     */
    public function getButtonOptions(Button $button)
    {
        return [];
    }

    public function getRequestTimeout()
    {
        //We can use higher timeout in admin or console.
        if ( Admin::isAdmin() || app()->runningInconsole() ){
            return 45;
        }

        $options = $this->getOptions();

        return $options['timeout'] ?? 2;
    }

    /*
     * Returns selected pickup point location name
     */
    public function getPickupName()
    {

    }

    /*
     * Returns selected pickup point location address
     */
    public function getPickupAddress()
    {

    }
}