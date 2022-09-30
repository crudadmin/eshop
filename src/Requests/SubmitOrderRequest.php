<?php

namespace AdminEshop\Requests;

use Admin\Core\Requests\AdminModelRequest;

class SubmitOrderRequest extends AdminModelRequest
{
    //Is final order submit
    private $isFinalOrderSubmit = false;

    public function only()
    {
        return [
            //Client fields
            'username', 'firstname', 'lastname', 'email', 'phone',
            'street', 'zipcode', 'city', 'country_id',

            //Company fields
            'is_company', 'company_name', 'company_id', 'company_tax_id', 'company_vat_id',

            //Other fields
            'note', 'register_account',

            //Delivery fields
            'delivery_different',
            'delivery_username', 'delivery_firstname', 'delivery_lastname',
            'delivery_phone',
            'delivery_street',
            'delivery_zipcode', 'delivery_city',
            'delivery_country_id',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = config('admineshop.cart.order.validator_rules', []);

        //We can push additional fields in submit step
        if ( $this->isFinalOrderSubmit === true ){
            $rules = array_merge($rules, config('admineshop.cart.order.validator_rules_submit', []));
        }

        return $rules;
    }

    /**
     * Determine is order is in final submit state
     *
     * @param  bool  $state
     */
    public function setOrderSubmit($state = false)
    {
        $this->isFinalOrderSubmit = $state;

        return $this;
    }
}
