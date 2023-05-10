<?php

namespace AdminEshop\Contracts\Delivery;

use AdminEshop\Contracts\Order\OrderConfigProvider;
use AdminEshop\Models\Delivery\Delivery;
use Admin\Helpers\Button;
use Illuminate\Support\Collection;
use OrderService;
use Cart;
use Admin;

class ShippingProvider extends OrderConfigProvider
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

    /*
     * Determine if shipping has labels
     */
    public function hasLabels()
    {
        return config('admineshop.delivery.labels', false);
    }

    public static function export(Collection $orders)
    {
        // return 'string';
    }

    public function getPackageWeight($toUnit = 'kilograms')
    {
        $options = $this->getOptions();

        $order = $this->getOrder();

        //Set default weight
        $defaultWeight = ($options['weight'] ?? null) ?: ($options['default_weight'] ?? null);
        $weight = $defaultWeight;

        //Calculate custom order weight
        if ( $order && $order->exists ){
            //Custom order calc
            if ( method_exists($order, 'getPackageWeight') ) {
                $weight = $order->getPackageWeight($this, $options) ?: $weight;
            }

            $itemsToWeightCalc = $order->items;
        } else {
            $itemsToWeightCalc = Cart::all();
        }

        //Get weight from cart items
        $weight = $this->calculateWeightFromItems($itemsToWeightCalc) ?: $weight;

        //If given weight is rounded to zero, then we need calculate default weight
        return $this->toWeightUnit($weight, $toUnit) ?: $this->toWeightUnit($defaultWeight, $toUnit);
    }

    private function toWeightUnit($weight, $toUnit)
    {
        if ( !is_numeric($weight) ){
            return;
        }

        $inUnit = config('admineshop.product.weight_unit');

        if ( $inUnit == $toUnit ){
            return $weight;
        } else if ( $inUnit == 'grams' && $toUnit == 'kilograms'  ){
            //Round easy weights
            if ( $weight < 100 ){
                $weight = ceil($weight / 100) * 100;
            }

            return round($weight / 1000, 1);
        } else if ( $inUnit == 'kilograms' && $toUnit == 'grams'  ){
            return round($weight * 1000);
        }

        return $weight;
    }

    private function calculateWeightFromItems($items)
    {
        //Calculate weight by cart items
        $cartItemsWithWeight = $items->filter(function($item){
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