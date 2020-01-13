<?php

namespace AdminEshop\Models\Delivery;

use AdminEshop\Eloquent\Concerns\PriceMutator;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Cart;

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

    protected $group = 'store.settings';

    protected $reversed = true;

    protected $visible = ['id', 'name', 'title', 'description', 'thumbnail', 'priceWithoutTax', 'priceWithTax', 'clientPrice'];

    protected $appends = ['thumbnail', 'priceWithoutTax', 'priceWithTax', 'clientPrice'];

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
            'tax' => 'name:Sazba DPH|belongsTo:taxes,:name (:tax%)|canAdd',
            'price' => 'name:Zakladná cena bez DPH|type:decimal|component:priceField|required',
            'image' => 'name:Ikona dopravy|type:file|image',
            'description' => 'name:Popis dopravy|hidden',

            'Obmedzenia' => Group::tab([
                'payments' => 'name:Platobné metódy|belongsToMany:payments_methods,name|canAdd',
                'countries' => 'name:Krajiny|belongsToMany:countries,name|canAdd',
            ])->icon('fa-gear'),
        ];
    }

    protected $settings = [
        'grid.default' => 'medium',
        'title.insert' => 'Nová doprava',
        'title.update' => ':name',
        'columns.id.hidden' => true,
    ];

    public function getThumbnailAttribute()
    {
        return $this->image ? $this->image->resize(null, 180)->url : null;
    }
}