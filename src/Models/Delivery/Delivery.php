<?php

namespace AdminEshop\Models\Delivery;

use AdminEshop\Contracts\Discounts\FreeDeliveryFromPrice;
use AdminEshop\Eloquent\Concerns\DiscountHelper;
use AdminEshop\Eloquent\Concerns\DiscountSupport;
use AdminEshop\Eloquent\Concerns\PriceMutator;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use OrderService;
use Discounts;
use Store;

class Delivery extends AdminModel implements DiscountSupport
{
    use PriceMutator,
        DiscountHelper;

    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2018-01-07 17:48:16';

    /*
     * Template name
     */
    protected $name = 'Doprava';

    /*
     * Template title
     * Default ''
     */
    protected $title = '';

    protected $group = 'store';

    protected $reversed = true;

    protected $appends = ['thumbnail', 'priceWithoutVat', 'priceWithVat', 'clientPrice'];

    /*
     * Automatic form and database generation
     * @name - field name
     * @placeholder - field placeholder
     * @type - field type | string/text/editor/select/integer/decimal/file/password/date/datetime/time/checkbox/radio
     * ... other validation methods from laravel
     */
    public function fields()
    {
        return [
            'name' => 'name:Názov dopravy|placeholder:Zadejte názov dopravy|required|limit:40|max:90',
            'vat' => 'name:Sadza DPH|belongsTo:vats,:name (:vat%)|required|defaultByOption:default,1|canAdd',
            'price' => 'name:Základná cena bez DPH|type:decimal|component:priceField|required',
            'image' => 'name:Ikona dopravy|type:file|image',
            'Informácie k doprave' => Group::tab([
                'description' => 'name:Popis dopravy|hidden',
                'description_email' => 'name:Popis k doprave v potvdzovaciom emaily objednávky|hidden|type:editor',
                'description_email_status' => 'name:Popis pri zmene stavu objednávky|hidden|type:editor',
            ])->icon('fa fa-info'),
        ];
    }

    public function mutateFields($fields)
    {
        $this->addRestrictionTab($fields);
        $this->addDiscountsTab($fields);
    }

    private function addRestrictionTab($fields)
    {
        $restrictionFields = [];

        //Add multiple locations model
        if ( config('admineshop.delivery.multiple_locations.enabled') == true ) {
            $restrictionFields['multiple_locations'] = 'name:Viacero doručovacích adries/predajní|type:checkbox|default:0';
        }

        //Add payments rules
        if ( config('admineshop.delivery.payments') == true ) {
            $restrictionFields['payments'] = 'name:Dostupné platobné metódy|belongsToMany:payments_methods,name|title:Pri žiadnej vybranej platia všetký|canAdd';
        }

        //Add payments rules
        if ( config('admineshop.delivery.countries') == true ) {
            $restrictionFields['countries'] = 'name:Dostupné krajiny|belongsToMany:countries,name|title:Pri žiadnej vybranej platia všetký|canAdd';
        }

        //Add payments rules
        if ( config('admineshop.delivery.price_limit') == true ) {
            $restrictionFields['price_limit'] = 'name:Limit ceny objednávky pre dopravu|type:decimal|title:S DPH - Po presiahnutí ceny objednávky bude doprava odobraná z objednávkoveho košíku|hidden';
        }

        //Add payments rules
        if ( config('admineshop.heureka.enabled') ) {
            $restrictionFields['heureka_id'] = 'name:Heureka ID dopravy|title:Ak identifikátor bude vyplnený, zašle sa doprava do heureka exportu. - https://sluzby.heureka.sk/napoveda/xml-feed/#DELIVERY';
        }

        if ( count($restrictionFields) > 0 ) {
            $fields->push(
                Group::tab($restrictionFields)->name('Obmedzenia')->icon('fa-gear')->id('restrictions')
            );
        }
    }

    private function addDiscountsTab($fields)
    {
        $discountFields = [];

        //Add multiple locations model
        if ( Discounts::isRegistredDiscount(FreeDeliveryFromPrice::class) ) {
            $discountFields['free_from'] = 'name:Zdarma od (€)|title:Platí od sumy s DPH|type:decimal';
        }

        if ( count($discountFields) > 0 ) {
            $fields->push(
                Group::tab($discountFields)->name('Zľavy dopravy')->icon('fa-percentage')->add('hidden')
            );
        }
    }

    protected $settings = [
        'grid.default' => 'medium',
        'title.insert' => 'Nová doprava',
        'title.update' => ':name',
    ];

    protected $layouts = [
        'form-top' => 'DeliveryGroups',
    ];

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

    public function getCountriesIdsAttribute()
    {
        return $this->countries->pluck('id');
    }

    public function getDescriptionEmailAttribute($string)
    {
        $string = trim($string);
        $string = str_replace("\n", '</br>', $string);
        $string = preg_replace("/<\/br><\/br>/", '</br>', $string);

        return $string;
    }

    /**
     * We may filter available deliveries
     *
     * @param  Builder  $query
     */
    public function scopeOnlyAvailable($query)
    {

    }

    /**
     * We need build cart item for discounts
     * but delivery is not assigned to cartItem, so we does not need
     * response in this method
     */
    public function buildCartItem()
    {

    }

    public function getShippingProviderAttribute()
    {
        if ( $this->exists && $provider = OrderService::getShippingProvider($this->getKey()) ) {
            return [
                'name' => class_basename(get_class($provider)),
                'options' => $provider->getOptions(),
            ];
        }
    }

    public function scopeWithCartResponse($query)
    {
        $with = [];

        //Autoload default delivery locations
        if (
            config('admineshop.delivery.multiple_locations.enabled') == true
            && config('admineshop.delivery.multiple_locations.autoload', false) == true
            && config('admineshop.delivery.multiple_locations.table') == 'deliveries_locations'
        ) {
            $with[] = 'locations:id,delivery_id,name';
        }

        if ( config('admineshop.delivery.countries') == true ) {
            $with[] = 'countries';
        }

        if ( config('admineshop.delivery.payments') == true ) {
            $with[] = 'payments';
        }

        $query->with($with);
    }

    public function setCartResponse()
    {
        return $this->append('shippingProvider')
                    ->makeHidden(['created_at', 'published_at', 'deleted_at', 'updated_at'])
                    ->makeVisible('shippingProvider');
    }

    /*
     * We can switch available locations for each delivery if neccessary
     */
    public function getDeliveryLocations()
    {
        return $this->locations();
    }
}