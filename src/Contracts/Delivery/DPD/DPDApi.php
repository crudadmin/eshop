<?php

namespace AdminEshop\Contracts\Delivery\DPD;

use AdminEshop\Contracts\Delivery\ShipmentException;
use AdminEshop\Models\Orders\Order;
use Carbon\Carbon;

class DPDApi
{
    /** @var string */
    protected $clientKey;

    /** @var string */
    protected $email;

    /** @var string */
    protected $password;

    /** @var array */
    protected $options = [];

    /** @var array */
    protected $errors = [];

    /**
     * Api constructor.
     *
     * @param string $clientKey
     * @param string $email
     * @param string $password
     * @param array  $options
     */
    public function __construct()
    {
        $this->clientKey = env('SHIPPMENT_DPD_CLIENT_KEY');
        $this->email = env('SHIPPMENT_DPD_EMAIL');
        $this->password = env('SHIPPMENT_DPD_PASSWORD');
        $this->options = [
            'testMode' => env('SHIPPMENT_DPD_DEBUG', true) ? true : false,
            'timeout' => 10
        ];
    }

    public function getPackageEndpoint()
    {
        if ($this->options['testMode']) {
            return 'https://capi.dpdportal.sk/apix/shipment/json';
        }

        return 'https://api.dpdportal.sk/shipment/json';
    }

    public function getPickupEndpoint()
    {
        return 'https://api.dpdportal.sk/parcelshop/json';
    }

    /**
     * Send cURL request.
     *
     * @param string $endpoint
     * @param string $method
     * @param array  $data
     *
     * @return array
     */
    public function sendRequest(string $endpoint, string $method, array $data): array
    {
        $data['DPDSecurity'] = [
            'SecurityToken' => [
                'ClientKey' => $this->clientKey,
                'Email' => $this->email,
                'Password' => $this->password
            ]
        ];

        $postData = [
            'id' => 1,
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $data
        ];

        $ch = \curl_init($endpoint);
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($ch, CURLOPT_POST, true);
        \curl_setopt($ch, CURLOPT_POSTFIELDS, \json_encode($postData));
        \curl_setopt($ch, CURLOPT_TIMEOUT, $this->options['timeout']);

        $response = \curl_exec($ch);

        if (\curl_errno($ch)) {
            throw new ShipmentException('Request failed: ' . \curl_error($ch));
        }

        \curl_close($ch);
        $response = \json_decode($response, true);

        return $response;
    }
}