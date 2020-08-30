<?php

namespace AdminEshop\Requests;

use Admin\Core\Requests\AdminModelRequest;

class SubmitOrderRequest extends AdminModelRequest
{
    public function only()
    {
        return [
            //Client fields
            'username', 'email', 'phone',
            'street', 'zipcode', 'city', 'country_id',

            //Company fields
            'is_company', 'company_name', 'company_id', 'company_tax_id',

            //Delivery fields
            'delivery_different',
            'delivery_username',
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
        return array_merge(
            config('admineshop.cart.order.validator_rules', []),
            []
        );
    }
}
