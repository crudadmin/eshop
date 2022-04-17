<?php

namespace AdminEshop\Contracts\Order;

trait HasRequest
{
    /**
     * Request data of order
     *
     * @var  array
     */
    protected $requestData = [];

    /**
     * If keys of this fields will be missing in request
     * Values from key array will be set to null
     *
     * @var  array
     */
    protected $defaultResetIfNotPresent = [];

    /**
     * Returns request data
     *
     * @param  array  $row
     * @return  this
     */
    public function getRequestData()
    {
        return $this->requestData;
    }

    /**
     * Clean order row and made modifications whit order row
     *
     * @param  array  $row
     * @param  bool  $submitOrder
     * @param  bool  $persist
     *
     * @return  this
     */
    public function setRequestData($row, $submitOrder = false, $persist = false)
    {
        $this->requestData = $this->prepareRequestData($row, $submitOrder, true);

        $this->getClientDataMutator()->setClientData($row, $persist);

        return $this;
    }

    /**
     * Merge reset if not present
     *
     * @param  array  $array
     */
    public function setDefaultResetIfNotPresent(array $array)
    {
        $this->defaultResetIfNotPresent = $array;
    }

    /**
     * Get reset if not present mutators list
     *
     * @return  array
     */
    public function getDefaultResetIfNotPresent()
    {
        return array_merge(
            $this->getCompanyResetIfNotPresent(),
            $this->getDeliveryAddressResetIfNotPresent(),
            $this->defaultResetIfNotPresent,
        );
    }

    /**
     * Prepare request data
     *
     * @param  array  $row
     * @param  boolean  $submitOrder
     * @param  boolean  $cleanedData
     *
     * @return  array
     */
    public function prepareRequestData($row, $submitOrder, $cleanedData = false)
    {
        $row = $this->cleanNotPresent($row, $submitOrder, $cleanedData);

        return $row;
    }

    /**
     * Clean order row
     *
     * @param  array  $row
     * @return  $row
     */
    private function cleanNotPresent(&$row, $submitOrder = false, $cleanedData = false)
    {
        $resetFields = config('admineshop.cart.order.'.($submitOrder ? 'fields_reset_submit' : 'fields_reset_process'));
        $resetFields = is_array($resetFields) ? $resetFields : $this->getDefaultResetIfNotPresent();

        foreach ($resetFields as $presenceKeyName => $data) {
            $fieldsToRemove = isset($data['fields']) ? $data['fields'] : $data;
            $prefix = isset($data['rewriteSameFieldsWithoutPrefix']) ? $data['rewriteSameFieldsWithoutPrefix'] : null;
            $isActivated = array_key_exists($presenceKeyName, $row) && in_array($row[$presenceKeyName], [1, 'on']);

            if ( $isActivated === false ) {
                foreach ($fieldsToRemove as $key) {
                    //If prefix is available, we can rewrite this fields
                    if ( $prefix && isset($row[$key]) ) {
                        $unprefixedKey = str_replace($prefix, '', $key);

                        $row[$unprefixedKey] = $row[$key];

                        //On order submit, we can reset this temporary fields and keep only final fields
                        if ( $submitOrder === true && $cleanedData == true ){
                            $row[$key] = null;
                        }
                    }

                    //Reset original field key
                    else {
                        //We need reset original field key
                        $row[$key] = null;
                    }
                }
            }
        }

        return $row;
    }

    protected function getPreparedOrderRequest($submitOrder = false, $fetchStoredClientData = false)
    {
        $request = request();

        //Fetch data from session and put them into request
        if ( $fetchStoredClientData ){
            $clientData = $this->getClientDataMutator()->getClientData() ?: [];

            $request->merge($request->all() + $clientData);
        }

        //Replace data by properties
        $request = $request->replace(
            $this->prepareRequestData($request->all(), $submitOrder)
        );

        return $request;
    }

    public function isDeliveryAddressPrimary()
    {
        return config('admineshop.cart.order.delivery_address_primary', false);
    }

    private function getCompanyResetIfNotPresent()
    {
        return [
            'is_company' => [
                'company_name', 'company_id', 'company_tax_id', 'company_vat_id'
            ]
        ];
    }

    private function getDeliveryAddressResetIfNotPresent()
    {
        $fields = array_merge(
            ['delivery_username', 'delivery_firstname', 'delivery_lastname', 'delivery_phone', 'delivery_street', 'delivery_city', 'delivery_zipcode', 'delivery_city', 'delivery_country_id'],
            config('admineshop.cart.order.additional_delivery_fields', [])
        );

        return [
            'delivery_different' => [
                'rewriteSameFieldsWithoutPrefix' => $this->isDeliveryAddressPrimary() ? 'delivery_' : null,
                'fields' => $fields,
            ],
        ];
    }
}
?>