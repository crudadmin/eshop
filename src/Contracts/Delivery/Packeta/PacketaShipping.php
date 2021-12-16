<?php

namespace AdminEshop\Contracts\Delivery\Packeta;

use AdminEshop\Contracts\Delivery\CreatePackageException;
use AdminEshop\Contracts\Delivery\ShipmentException;
use AdminEshop\Contracts\Delivery\ShippingInterface;
use AdminEshop\Contracts\Delivery\ShippingProvider;
use AdminEshop\Contracts\Delivery\ShippingResponse;
use Admin\Helpers\Button;
use Carbon\Carbon;
use SoapClient;
use SoapFault;

/**
 * Class Api.
 */
class PacketaShipping extends ShippingProvider implements ShippingInterface
{
    /**
     * Set pickup builder
     *
     * @var  callable
     */
    private static $pickupBuilder;

    /**
     * Set parcel builder
     *
     * @var  callable
     */
    private static $parcelBuilder;

    /**
     * Set request builder
     *
     * @var  callable
     */
    private static $requestBuilder;

    /*
     * Shipping name
     */
    public function getName()
    {
        return _('Zasielkovňa');
    }

    /*
     * Check if provider is enabled
     */
    public function isActive()
    {
        return env('SHIPPMENT_PACKETA_ENABLED') === true;
    }

    /**
     * You can modify parcels throught callback passed in this method
     *
     * @param  callable  $callback
     */
    public static function setParcelBuilder(callable $callback)
    {
        static::$parcelBuilder = $callback;
    }

    /**
     * You can modify pickup throught callback passed in this method
     *
     * @param  callable  $callback
     */
    public static function setPickupBuilder(callable $callback)
    {
        static::$pickupBuilder = $callback;
    }

    /**
     * You can modify request throught callback passed in this method
     *
     * @param  callable  $callback
     */
    public static function setRequestBuilder(callable $callback)
    {
        static::$requestBuilder = $callback;
    }

    public function getTrackingUrl($trackingNumber)
    {
        return 'https://tracking.packeta.com/sk/?id='.$trackingNumber;
    }

    public function createPackage() : ShippingResponse
    {
        $order = $this->getOrder();

        $options = $this->getOptions();

        $weight = $this->getPackageWeight();

        //Weight is required for packeta from 1.9.2021
        if ( is_null($weight) ){
            throw new ShipmentException('Nebola zadaná hmotnosť balíka.');
        }

        $gw = new SoapClient($options['API_HOST'].'/api/soap.wsdl');
        $apiPassword = $options['API_PASSWORD'];

        try {
            $data = [
                'number' => $order->number,
                'name' => $order->firstname,
                'surname' => $order->lastname,
                'email' => $order->email,
                'phone' => $order->delivery_phone ?: $order->phone,
                'addressId' => $order->packeta_point['id'],
                'value' => $order->price_vat,
                'cod' => $this->isCashDelivery() ? $order->price_vat : 0,
                'eshop' => $options['eshop'] ?? env('PACKETA_API_ESHOP'),
                'weight' => $weight,
            ];

            $packet = $gw->createPacket($apiPassword, $data);

            return new ShippingResponse($packet->id);
        }

        catch(SoapFault $error) {
            $message = implode(' - ', array_filter([$error->getMessage(), collect($error?->detail?->PacketAttributesFault?->attributes?->fault ?: [])->pluck('fault')->join(', ')]));

            throw new CreatePackageException($message, json_encode($error->detail ?? '{}', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
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
        return $button->title('Zadajte váhu balíka')->type('success')->component('SetPacketaWeight.vue');
    }

    /**
     * Pass and mutate shipping options from pressed button question component
     *
     * @return  []
     */
    public function getButtonOptions(Button $button)
    {
        $weight = (float)str_replace(',', '.', request('weight'));

        if ( $weight <= 0 ){
            return $button->error('Váha musí byť kladne číslo uvedené v kologramoch.');
        }

        return [
            'weight' => $weight,
        ];
    }
}