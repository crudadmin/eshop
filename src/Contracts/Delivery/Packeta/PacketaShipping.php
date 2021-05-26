<?php

namespace AdminEshop\Contracts\Delivery\Packeta;

use AdminEshop\Contracts\Delivery\CreatePackageException;
use AdminEshop\Contracts\Delivery\ShipmentException;
use AdminEshop\Contracts\Delivery\ShippingInterface;
use AdminEshop\Contracts\Delivery\ShippingProvider;
use AdminEshop\Contracts\Delivery\ShippingResponse;
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
                'eshop' => env('APP_NAME')
            ];

            $packet = $gw->createPacket($apiPassword, $data);

            return new ShippingResponse(true, $packet->id);
        }

        catch(SoapFault $error) {
            throw new CreatePackageException($error->getMessage());
        }
    }
}