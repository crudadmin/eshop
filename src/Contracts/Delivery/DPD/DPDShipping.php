<?php

namespace AdminEshop\Contracts\Delivery\DPD;

use AdminEshop\Admin\Buttons\DPDExportButton;
use AdminEshop\Contracts\Delivery\CreatePackageException;
use AdminEshop\Contracts\Delivery\ShipmentException;
use AdminEshop\Contracts\Delivery\ShippingInterface;
use AdminEshop\Contracts\Delivery\ShippingProvider;
use AdminEshop\Contracts\Delivery\ShippingResponse;
use Carbon\Carbon;
use Facades\AdminEshop\Contracts\Delivery\DPD\DPDApi;
use Illuminate\Support\Collection;

/**
 * Class Api.
 */
class DPDShipping extends ShippingProvider implements ShippingInterface
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
        return _('DPD Doprava');
    }

    /*
     * Check if provider is enabled
     */
    public function isActive()
    {
        return env('SHIPPMENT_DPD_ENABLED') === true;
    }

    /*
     * Has shipping export?
     */
    public function isExportable()
    {
        return true;
    }

    /*
     * Returns export data
     */
    public static function export(Collection $orders)
    {
        return [
            'data' => view('admineshop::xml.dpd_export', compact('orders'))->render(),
            'extension' => 'xml',
        ];
    }

    /**
     * Admin order buttons
     *
     * @return  array
     */
    public function buttons()
    {
        return [
            DPDExportButton::class,
        ];
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

    public function createPackage() : ShippingResponse
    {
        $order = $this->getOrder();

        $params = [
            'shipment' => [
                'reference' => $order->number,
                'delisId' => env('SHIPPMENT_DPD_DELIS_ID'),
                'note' => $order->note,
                'product' => $this->getProductCode(),
                'pickup' => $this->getPickup(),
                'addressSender' => [
                    'id' => env('SHIPPMENT_DPD_ADDRESS_ID'),
                ],
                'addressRecipient' => $this->getAddressRecipient(),

                'parcels' => [
                    'parcel' => $this->getParcels() ?: [],
                ],
                'services' => $this->getServices(),
            ]
        ];

        if ( static::$requestBuilder ) {
            $params = (static::$requestBuilder)($order, $params);
        }

        $response = DPDApi::setOptions([
            'timeout' => $this->getRequestTimeout()
        ])->sendRequest(
            DPDApi::getPackageEndpoint(),
            'create',
            $params
        );

        return $this->returnShippingResponse($response);
    }

    private function getAddressRecipient()
    {
        $order = $this->getOrder();

        return [
            'type' => $order->delivery_location ? 'psd' : ($order->is_company ? 'b2b' : 'b2c'),
            'name' => $order->is_company ? $order->company_name : ($order->delivery_username ?: $order->username),
            'street' => $order->delivery_street ?: $order->street,
            'zip' => $order->delivery_zipcode ?: $order->zipcode,
            'country' => $order->delivery_country ? $order->delivery_country->iso3166 : ($order->country ? $order->country->iso3166 : null),
            'city' => $order->delivery_city ?: $order->city,
            'phone' => $order->delivery_phone ?: $order->phone,
            'email' => $order->email,
            'ico' => $order->company_id,
            'vatId' => $order->company_tax_id,
            'vatId2' => $order->company_vat_id,
        ];
    }

    private function getServices()
    {
        $services = [];

        $order = $this->getOrder();

        //Parcel shop delivery
        if ( $order->delivery_location ) {
            $services['parcelShopDelivery'] = [
                'parcelShopId' => (int)$order->delivery_location->identifier,
            ];
        }

        //Add payment in delivery (cast or card)
        if ( $this->isCashDelivery() ) {
            $services['cod'] = [
                'amount' => $order->price_vat,
                'currency' => 'EUR',
                'bankAccount' => [
                    'id' => env('SHIPPMENT_DPD_BANK_ACCOUNT_ID'),
                ],
                'variableSymbol' => $order->number,
                'paymentMethod' => 1,
            ];
        }

        return $services;
    }

    private function getProductCode()
    {
        $type = $this->getOption('type');

        $types = [
            'classic' => 1,
            'guarantee' => 2,
            'dpd10' => 3,
            'dpd12' => 4,
            'home' => 9,
            'parcelshop' => 17,
            'city' => 10,
        ];

        if ( array_key_exists($type, $types) ){
            return $types[$type];
        }

        throw new ShipmentException('Nebol zvoleny žiaden kód produktu.');
    }

    private function getPickup()
    {
        if ( static::$pickupBuilder ) {
            return (static::$pickupBuilder)($this->getOrder());
        }

        return [
            'date' => Carbon::now()->addWeekday(1)->format('Ymd'),
            'timeWindow' => [
                'beginning' => '1000',
                'end' => '1500',
            ],
        ];
    }

    private function getParcels()
    {
        if ( static::$parcelBuilder ) {
            return (static::$parcelBuilder)($this->getOrder(), $this);
        }

        return [
            'reference1' => $this->getOrder()->number,
            'weight' => $this->getPackageWeight(),
            'height' => 30,
            'width' => 30,
            'depth' => 40
        ];
    }

    private function returnShippingResponse($response)
    {
        //Returns success response
        if (
            isset($response['result']['result'][0])
            && $response['result']['result'][0]['success']
            && $package = $response['result']['result'][0]
        ){
            $message = is_array($package['messages']) ? implode(' ', $package['messages']) : null;

            if ( in_array($package['ackCode'] ?? null, ['success', 'successWithWarning']) === false ) {
                throw new CreatePackageException($package['ackCode'].' - '.$message);
            }

            return new ShippingResponse(
                $package['mpsid'],
                $message,
                $package
            );
        }

        $message = null;

        if ( isset($response['result']['result'][0]['success']) && !$response['result']['result'][0]['success'] ) {
            $message = \implode(' ', \array_column($response['result']['result'][0]['messages'], 'value'));
        } else if ( isset($response['error']['message']) ) {
            $message = $response['error']['message'];
        }

        throw new CreatePackageException($message, $response);
    }

    public function importLocations()
    {
        $response = DPDApi::sendRequest(
            DPDApi::getPickupEndpoint(),
            'getAll',
            []
        );

        if ( !isset($response['result']['parcelshops']['parcelshop']) ){
            return [];
        }

        $parcelshops = $response['result']['parcelshops']['parcelshop'];

        $items = [];

        foreach ($parcelshops as $data) {
            $address = [
                $data['street'].' '.@$data['houseno'],
                $data['zip'],
                $data['city'],
                is_array($data['country']) ? $data['country']['value'] : null
            ];

            $address = array_filter($address);
            $address = implode(', ', $address);

            $items[] = [
                'name' => $data['name'],
                'identifier' => $data['id'],
                'address' => $address,
                'data' => $data,
            ];
        }

        return $items;
    }

    public function getTrackingUrl($trackingNumber)
    {
        return 'https://tracking.dpd.de/status/sk_SK/parcel/'.$trackingNumber;
    }
}