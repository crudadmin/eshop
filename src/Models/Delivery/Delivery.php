<?php

namespace AdminEshop\Models\Delivery;

use AdminEshop\Eloquent\Concerns\DiscountHelper;
use AdminEshop\Eloquent\Concerns\DiscountSupport;
use AdminEshop\Eloquent\Concerns\PriceMutator;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
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

    protected $group = 'settings.store';

    protected $reversed = true;

    protected $hidden = ['created_at', 'published_at', 'deleted_at', 'updated_at'];

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
            'name' => 'name:Názov dopravy|placeholder:Zadejte názov dopravy|required|max:90',
            'vat' => 'name:Sadza DPH|belongsTo:vats,:name (:vat%)|required|defaultByOption:default,1|canAdd',
            'price' => 'name:Základná cena bez DPH|type:decimal|component:priceField|required',
            'image' => 'name:Ikona dopravy|type:file|image',
            'description' => 'name:Popis dopravy|hidden',
        ];
    }

    public function mutateFields($fields)
    {
        $restrictionFields = [];

        //Add multiple locations model
        if ( config('admineshop.delivery.multiple_locations') == true ) {
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

        if ( count($restrictionFields) > 0 ) {
            $fields->push(
                Group::tab($restrictionFields)->name('Obmedzenia')->icon('fa-gear')->id('restrictions')
            );
        }
    }

    protected $settings = [
        'grid.default' => 'medium',
        'title.insert' => 'Nová doprava',
        'title.update' => ':name',
        'columns.id.hidden' => true,
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
}