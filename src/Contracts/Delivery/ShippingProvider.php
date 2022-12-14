<?php

namespace AdminEshop\Contracts\Delivery;

use AdminEshop\Contracts\Order\OrderProvider;
use AdminEshop\Models\Delivery\Delivery;
use Admin\Helpers\Button;
use Illuminate\Support\Collection;
use OrderService;
use Cart;
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

    /**
     * Determine whatever shipping has pickup points
     *
     * @return  bool
     */
    public function hasPickupPoints()
    {
        return false;
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

        $order = $this->getOrder();

        //Calculate custom order weight
        if ( $order ){
            //Custom order calc
            if ( method_exists($order, 'getPackageWeight') ) {
                $weight = $order->getPackageWeight($this, $options);

                if ( is_null($weight) === false ) {
                    return $weight;
                };
            }

            $itemsToWeightCalc = $order->items;
        } else {
            $itemsToWeightCalc = Cart::all();
        }

        $cartWeight = $this->calculateWeightFromItems($itemsToWeightCalc);
        if ( is_null($cartWeight) == false ){
            return $cartWeight;
        }

        //Default weight
        return ($options['weight'] ?? $options['default_weight'] ?? null);
    }

    private function calculateWeightFromItems($items)
    {
        //Calculate weight by cart items
        $cartItemsWithWeight = Cart::all()->filter(function($item){
            return is_null($item->getItemModel()?->weight) == false;
        });
        if ( $cartItemsWithWeight->count() ){
            $calculatedWeight = $cartItemsWithWeight->map(function($item){
                return ($item->getItemModel()?->weight ?: 0) * $item->quantity;
            })->sum();

            return $calculatedWeight;
        }
    }

    public function isCashDelivery()
    {
        $order = $this->getOrder();

        if ( $order->paymentMethod && $order->paymentMethod->isCashDelivery() === true ){
            return true;
        }

        return false;
    }

    public function getDeliveryData()
    {
        //Get data from order
        if ( $order = $this->getOrder() ){
            return $order->delivery_data[$this->identifier] ?? null;
        }

        //Get data from cart
        else if ( $this->identifier ){
            return OrderService::getDeliveryMutator()->getDeliveryData($this->identifier);
        }
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
       return $button->title(_('Prajete si pokračovať?'))
                     ->warning(_('Balík bude automatický odoslaný do dopravnej služby'));
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

    /**
     * Modify custom shipping price
     *
     * @return  float
     */
    public function getShippingPrice()
    {

    }

    /**
     * Returns selected pickup point
     */
    public function getPickupPoint()
    {

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

    public function toArray()
    {
        $array = parent::toArray();

        $pickupName = $this->getPickupName();
        $pickupAddress = $this->getPickupAddress();
        if ( $this->hasPickupPoints() && ($pickupName || $pickupAddress) ){
            $array['point'] = [
                'name' => $pickupName,
                'address' => $pickupAddress,
            ];
        }

        return $array;
    }

    public function getExportData($row)
    {
        $orders = Admin::getModel('Order')
                        ->whereDate('created_at', '>=', $row->date_from)
                        ->whereDate('created_at', '<=', $row->date_to)
                        ->with([
                            'payment_method' => function($query){
                                $query->withTrashed();
                            },
                            'delivery' => function($query){
                                $query->withTrashed();
                            }
                        ])
                        ->get();

        return $this->export($orders);
    }
}