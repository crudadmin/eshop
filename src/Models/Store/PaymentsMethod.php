<?php

namespace AdminEshop\Models\Store;

use AdminEshop\Eloquent\Concerns\PriceMutator;
use Admin\Fields\Group;
use Gogol\Invoices\Model\PaymentsMethod as BasePaymentsMethod;
use Store;

class PaymentsMethod extends BasePaymentsMethod
{
    use PriceMutator;

    protected $group = 'store';

    protected $appends = ['thumbnail', 'priceWithoutVat', 'priceWithVat', 'clientPrice'];

    protected $publishable = true;

    public function reserved()
    {
        return array_filter([
            (int)ENV('PAYMENT_WITH_CASH_ON_DELIVERY_ID')
        ]);
    }

    public function mutateFields($fields)
    {
        parent::mutateFields($fields);

        $fields->push([
            'vat' => 'name:Sadza DPH|belongsTo:vats,:name (:vat%)|required|defaultByOption:default,1|canAdd',
            'price' => 'name:Základna cena bez DPH|type:decimal|component:PriceField||required',
            'image' => 'name:Ikona dopravy|type:file|image',
            'description' => 'name:Popis platby|type:text',
        ]);
    }

    protected $hidden = ['created_at', 'deleted_at', 'updated_at'];

    /**
     * We need allow applying discoints in administration for this model all the time
     *
     * @return  bool
     */
    public function canApplyDiscountsInAdmin()
    {
        return true;
    }

    public function options()
    {
        return [
            'vat_id' => Store::getVats(),
        ];
    }

    public function getThumbnailAttribute()
    {
        return $this->image ? $this->image->resize(null, 180)->url : null;
    }

    /*
     * Definy if is cash delivery
     */
    public function isCashDelivery()
    {
        return $this->getKey() == ENV('PAYMENT_WITH_CASH_ON_DELIVERY_ID');
    }

    /**
     * We may filter available payment methods
     *
     * @param  Builder  $query
     */
    public function scopeOnlyAvailable($query)
    {

    }
}