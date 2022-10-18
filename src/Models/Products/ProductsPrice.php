<?php

namespace AdminEshop\Models\Products;

use Admin;
use AdminEshop\Admin\Rules\ProductsPriceLevelsCheck;
use AdminEshop\Eloquent\Concerns\PriceMutator;
use Admin\Eloquent\AdminModel;
use Illuminate\Validation\Rule;
use Store;

class ProductsPrice extends AdminModel
{
    use PriceMutator;

    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2022-10-18 10:00:15';

    /*
     * Template name
     */
    protected $name = 'Cenové hladiny';

    protected $sortable = false;

    protected $icon = 'fa-yen-sign';

    protected $settings = [
        'search.enabled' => false,
        'grid.enabled' => false,
        'grid.default' => 'halt',
        'buttons.create' => 'Nová cena',
        'columns.price_vat.name' => 'Cena s DPH',
    ];

    protected $rules = [
        ProductsPriceLevelsCheck::class,
    ];

    public function active()
    {
        return config('admineshop.prices.price_levels');
    }

    public function belongsToModel()
    {
        return get_class(Admin::getModel('Product'));
    }

    public function fields($row = null)
    {
        return [
            'currency' => [
                'name' => 'Mena',
                'belongsTo' => 'currencies,:name',
                'defaultByOption' => 'default,1',
                Rule::unique('products_prices')->ignore($row?->id)->where('product_id', request('product_id'))->withoutTrashed(),
            ],
            'vat' => [
                'name' => 'Sazba DPH',
                'belongsTo' => 'vats,:name - :vat%',
                'defaultByOption' => 'default,1',
                'canAdd' => true,
                Rule::unique('products_prices')->ignore($row?->id)->where('product_id', request('product_id'))->where('currency_id', request('currency_id'))->withoutTrashed(),
            ],
            'price' => 'name:Cena bez DPH|type:decimal|decimal_length:'.config('admineshop.prices.decimals_places').'|default:0|component:PriceField',
        ];
    }

    public function setAdminRowsAttributes($attributes)
    {
        $attributes['price_vat'] = Store::numberFormat(
            $this->calculateVatPrice($this->price, null)
        );

        return $attributes;
    }
}