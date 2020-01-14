<?php

namespace AdminEshop\Contracts\Order;

use Illuminate\Support\Traits\Macroable;

trait HasRequest
{
    use Macroable;

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
    protected $resetIfNotPresent = [
        'delivery_different' => ['delivery_username', 'delivery_phone', 'delivery_street', 'delivery_city', 'delivery_zipcode', 'delivery_city', 'delivery_country_id'],
        'is_company' => ['company_name', 'company_id', 'company_tax_id', 'company_vat_id'],
    ];

    /**
     * Clean order row and made modifications whit order row
     *
     * @param  array  $row
     * @return  this
     */
    public function setRequestData($row)
    {
        $row = $this->cleanNotPresent($row);

        $this->requestData = $row;

        return $this;
    }

    /**
     * Merge reset if not present
     *
     * @param  array  $array
     */
    public function setResetIfNotPresent(array $array)
    {
        $this->resetIfNotPresent = array_merge($this->resetIfNotPresent, $array);
    }

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
     * Clean order row
     *
     * @param  array  $row
     * @return  $row
     */
    public function cleanNotPresent($row)
    {
        foreach ($this->resetIfNotPresent as $isPresentKey => $fieldsToRemove) {
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