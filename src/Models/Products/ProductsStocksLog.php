<?php

namespace AdminEshop\Models\Products;

use Admin\Eloquent\AdminModel;

class ProductsStocksLog extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2020-01-17 16:25:15';

    protected $name = 'História skladu';

    protected $sortable = false;

    protected $editable = false;

    protected $publishable = false;

    protected $insertable = false;

    protected $deletable = false;

    protected $group = 'settings.store';

    /*
     * Automatic form and database generation
     * @name - field name
     * @placeholder - field placeholder
     * @type - field type | string/text/editor/select/integer/decimal/file/password/date/datetime/time/checkbox/radio
     * ... other validation methods from laravel
     */
    protected $fields = [
        'product' => 'name:Produkt|belongsTo:products,name',
        'variant' => 'name:Varianta|belongsTo:products_variants,name',
        'sub' => 'name:Úprava skladu|type:integer|required',
        'stock' => 'name:Nová hodnota skladu|type:integer|required',
        'order' => 'name:Objednávka č.|belongsTo:orders,id|invisible',
        'message' => 'name:Zmena|type:select|limit:0',
    ];

    protected $options = [
        'message' => [
            'order.new' => 'Nová objednávka',
            'order.new-backend' => 'Nová objednávka (backend)',
            'order.canceled' => 'Zrušená objednávka',
            'order.deleted' => 'Zmazaná objednávka',
            'item.add' => 'Produkt pridaný do objednávky',
            'item.update' => 'Zmeneny počet ks v objednávke',
            'item.remove' => 'Produkt zmazaný z objednávky',
        ],
    ];

    public function setAdminAttributes($attributes)
    {
        $attributes['created'] = $this->created_at->translatedFormat('d. M Y \o H:i');
        $attributes['sub'] = ($this->sub > 0 ? '+' : '').$this->sub.' ks';
        $attributes['orderId'] = $this->order_id;

        return $attributes;
    }

    protected $settings = [
        'columns.orderId.name' => 'Objednávka č.',
        'columns.orderId.after' => 'stock',
        'columns.created.name' => 'Dátum',
    ];
}