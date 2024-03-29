<?php

namespace AdminEshop\Models\Store;

use AdminEshop\Eloquent\Concerns\HasPriceLevels;
use AdminEshop\Eloquent\Concerns\PriceMutator;
use Admin\Fields\Group;
use Gogol\Invoices\Model\PaymentsMethod as BasePaymentsMethod;
use PaymentService;
use Store;

class PaymentsMethod extends BasePaymentsMethod
{
    use PriceMutator,
        HasPriceLevels;

    protected $group = 'store';

    protected $appends = ['thumbnail', 'priceWithoutVat', 'priceWithVat', 'clientPrice'];

    protected $publishable = true;

    public function active()
    {
        return config('admineshop.payment_methods.enabled', true);
    }

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
            'price' => 'name:Základna cena bez DPH|type:decimal|component:PriceField|required',
            'image' => 'name:Ikona platby|type:file|image',
            'description' => 'name:Popis platby|type:text',
        ]);

        $restrictionFields = [
            'is_cod' => 'name:Platba dobierkou v dopravnej službe|column_name:Platba dobierkou|type:checkbox|default:0|title:Pri zvolení tejto platobnej metódy v spojení s dopravnou službou, ktorá podporuje dobierku, zaznačíme úhradu zasielky dobierkou.|hidden',
        ];

        //Add payments rules
        if ( config('admineshop.payment_methods.price_limit') == true ) {
            $restrictionFields['price_limit'] = 'name:Limit ceny objednávky pre platobnú metódu|type:decimal|title:S DPH - Po presiahnutí ceny objednávky bude platobná metóda odobraná z objednávkoveho košíku|hidden';
        }

        $fields->push(
            Group::tab($restrictionFields)->name('Obmedzenia')->icon('fa-gear')->id('restrictions')
        );
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
        return $this->is_cod === true || $this->getKey() == ENV('PAYMENT_CASH_ON_DELIVERY_ID');
    }

    /**
     * We may filter available payment methods
     *
     * @param  Builder  $query
     */
    public function scopeOnlyAvailable($query)
    {

    }

    public function scopeWithCartResponse($query)
    {
        $query->select(['payments_methods.*']);

        $query->withPriceLevelsColumns();
    }

    public function getPaymentMethodProvider()
    {
        return PaymentService::getPaymentProvider($this->getKey());
    }

    public function getPaymentProviderAttribute()
    {
        if ( $this->exists && $provider = $this->getPaymentMethodProvider() ) {
            return $provider->toArray();
        }
    }

    public function setCartResponse()
    {
        return $this->append('paymentProvider')
                    ->makeHidden(['created_at', 'published_at', 'deleted_at', 'updated_at'])
                    ->makeVisible('paymentProvider');
    }
}