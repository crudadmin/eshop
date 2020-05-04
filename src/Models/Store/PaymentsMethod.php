<?php

namespace AdminEshop\Models\Store;

use AdminEshop\Eloquent\Concerns\PriceMutator;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Store;

class PaymentsMethod extends AdminModel
{
    use PriceMutator;

    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2018-01-07 17:48:18';

    /*
     * Template name
     */
    protected $name = 'Platobné metódy';

    protected $group = 'settings.store';

    protected $publishable = false;

    protected $icon = 'fa-money';

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
            'name' => 'name:Názov platby|max:40|required',
            'tax' => 'name:Sadza DPH|belongsTo:taxes,:name (:tax%)|required|defaultByOption:default,1|canAdd',
            'price' => 'name:Základna cena bez DPH|type:decimal|component:PriceField||required',
            'image' => 'name:Ikona dopravy|type:file|image',
            'description' => 'name:Popis platby|type:text',
        ];
    }

    protected $hidden = ['created_at', 'deleted_at', 'updated_at', 'description'];

    protected $settings = [
        'grid.default' => 'medium',
        'title.update' => ':name',
        'columns.id.hidden' => true,
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
            'tax_id' => Store::getTaxes(),
        ];
    }

    public function getThumbnailAttribute()
    {
        return $this->image ? $this->image->resize(null, 180)->url : null;
    }
}