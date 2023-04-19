<?php

namespace AdminEshop\Contracts\Delivery\Packeta;

use AdminEshop\Contracts\Delivery\CreatePackageException;
use AdminEshop\Contracts\Delivery\ShipmentException;
use AdminEshop\Contracts\Delivery\ShippingInterface;
use AdminEshop\Contracts\Delivery\ShippingProvider;
use AdminEshop\Contracts\Delivery\ShippingResponse;
use Admin\Helpers\Button;
use Carbon\Carbon;
use Exception;
use SoapClient;
use SoapFault;
use Log;

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

    /**
     * This keys can be passed into request.
     *
     * @var  array
     */
    protected $visibleOptionsKeys = ['API_KEY'];

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
     * Determine whatever shipping has pickup points
     *
     * @return  bool
     */
    public function hasPickupPoints()
    {
        return true;
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
                'addressId' => $this->getPickupPoint()['id'],
                'value' => $order->price_vat,
                'cod' => $this->isCashDelivery() ? $order->price_vat : 0,
                'eshop' => $options['eshop'] ?? env('PACKETA_API_ESHOP'),
                'weight' => $weight,
            ];

            $packet = $gw->createPacket($apiPassword, $data);

            $shippment = new ShippingResponse($packet->id);

            if ( $this->hasLabels() && $label = $this->tryFetchLabel($gw, $packet, $apiPassword) ){
                $shippment->setLabel($label);
            }

            return $shippment;
        }

        catch(SoapFault $error) {
            $message = implode(
                ' - ',
                array_filter([
                    $error->getMessage(),
                    isset($error->detail->PacketAttributesFault->attributes->fault)
                        ? collect(array_wrap($error->detail->PacketAttributesFault->attributes->fault ?: []))->pluck('fault')->join(', ')
                        : []
                ])
            );

            throw new CreatePackageException(
                $message,
                isset($error->detail) && $error->detail
                    ? json_encode($error->detail, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : null
            );
        }
    }

    private function tryFetchLabel($gw, $packet, $apiPassword)
    {
        $label = $this->getOption('label', 'A7 on A4');

        try {
            return $gw->packetLabelPdf($apiPassword, $packet->id, $label, 0);
        } catch (Exception $e){
            Log::error($e);
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
        return $button->title(_('Zadajte váhu balíka'))->type('success')->component('SetDeliveryWeight', [
            'weight' => $this->getPackageWeight()
        ]);
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
            return $button->error(_('Váha musí byť kladne číslo uvedené v kilogramoch.'));
        }

        return [
            'weight' => $weight,
        ];
    }

    /**
     * Returns selected pickup point
     */
    public function getPickupPoint()
    {
        $data = $this->getDeliveryData();

        if ( $data['place'] ?? null ){
            return $data;
        }
    }

    /*
     * Returns selected delivery location point name
     */
    public function getPickupName()
    {
        if ( isset($this->getPickupPoint()['place']) ){
            return $this->getPickupPoint()['place'];
        }
    }

    /*
     * Returns selected pickup point location address
     */
    public function getPickupAddress()
    {
        if ( isset($this->getPickupPoint()['name']) ){
            return $this->getPickupPoint()['name'];
        }
    }
}