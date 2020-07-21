<?php

namespace AdminEshop\Models\Delivery;

use AdminEshop\Eloquent\Concerns\PriceMutator;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Store;

class Delivery extends AdminModel
{
    use PriceMutator;

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

    protected $visible = ['id', 'name', 'title', 'description', 'thumbnail', 'priceWithoutVat', 'priceWithVat', 'clientPrice', 'locations'];

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

            'Obmedzenia' => Group::tab([
                'payments' => 'name:Dostupné platobné metódy|belongsToMany:payments_methods,name|title:Pri žiadnej vybranej platia všetký|canAdd',
                'multiple_locations' => 'name:Viacej predajni|type:checkbox|default:0',
            ])->icon('fa-gear'),
        ];
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

    /**
     * We may filter available deliveries
     *
     * @param  Builder  $query
     */
    public function scopeOnlyAvailable($query)
    {

    }
}