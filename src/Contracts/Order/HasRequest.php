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
    protected $defaultresetIfNotPresent = [
        'delivery_different' => ['delivery_username', 'delivery_phone', 'delivery_street', 'delivery_city', 'delivery_zipcode', 'delivery_city', 'delivery_country_id'],
        'is_company' => ['company_name', 'company_id', 'company_tax_id', 'company_vat_id'],
    ];

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
        $row = $this->cleanNotPresent($row, $submitOrder);

        $this->requestData = $row;

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
        $this->defaultresetIfNotPresent = $array;
    }

    /**
     * Get reset if not present mutators list
     *
     * @return  array
     */
    public function getDefaultResetIfNotPresent()
    {
        return $this->defaultresetIfNotPresent;
    }

    /**
     * Clean order row
     *
     * @param  array  $row
     * @return  $row
     */
    public function cleanNotPresent($row, $submitOrder = false)
    {
        $resetFields = config('admineshop.cart.order.'.($submitOrder ? 'fields_reset_submit' : 'fields_reset_process'));
        $resetFields = is_array($resetFields) ? $resetFields : $this->getDefaultResetIfNotPresent();

        foreach ($resetFields as $isPresentKey => $fieldsToRemove) {
            if ( !array_key_exists($isPresentKey, $row) || !in_array($row[$isPresentKey], [1, 'on']) ) {
                foreach ($fieldsToRemove as $key) {
                    $row[$key] = null;
                }
            }
        }

        return $row;
    }
}
?>