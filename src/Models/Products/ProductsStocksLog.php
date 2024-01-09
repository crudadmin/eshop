<?php

namespace AdminEshop\Models\Products;

use AdminEshop\Admin\Buttons\RevertStock;
use AdminEshop\Eloquent\Concerns\OrderItemTrait;
use Admin\Eloquent\AdminModel;
use Illuminate\Support\Facades\DB;

class ProductsStocksLog extends AdminModel
{
    use OrderItemTrait;

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

    protected $group = 'store';

    protected $buttons = [
        RevertStock::class,
    ];

    /*
     * Automatic form and database generation
     * @name - field name
     * @placeholder - field placeholder
     * @type - field type | string/text/editor/select/integer/decimal/file/password/date/datetime/time/checkbox/radio
     * ... other validation methods from laravel
     */
    protected $fields = [
        'product' => 'name:Produkt|belongsTo:products,name|limit:40',
        'sub' => 'name:Úprava skladu|type:integer|required',
        'stock' => 'name:Nová hodnota skladu|type:integer|required',
        'order' => 'name:Objednávka č.|belongsTo:orders,number|invisible',
        'log' => 'name:Stav č.|belongsTo:products_stocks_logs,id|invisible',
        'reverted' => 'name:Vrátene stavu|type:checkbox|default:0|hidden',
        'message' => 'name:Zmena|type:select|limit:0',
    ];

    public function options()
    {
        return [
            'message' => [
                'revert' => _('Vrátenie stavu'),
                'order.new' => _('Nová objednávka'),
                'order.paid' => _('Objednávka zaplatená'),
                'order.new-backend' => _('Nová objednávka (backend)'),
                'order.canceled' => _('Zrušená objednávka'),
                'order.deleted' => _('Zmazaná objednávka'),
                'item.add' => _('Produkt pridaný do objednávky'),
                'item.update' => _('Zmenený počet ks v objednávke'),
                'item.changed.new' => _('Tento produkt nahradil iný tovar v objednávke'),
                'item.changed.old' => _('Tento produkt v objednávke bol nahradený iným tovarom'),
                'item.remove' => _('Produkt zmazaný z objednávky'),
            ],
            'product_id' => $this->getAvailableProducts(),
            'order_id' => [],
        ];
    }

    public function scopeAdminRows($query)
    {
        $query
            ->selectRaw('products_stocks_logs.*, orders.number as order_number')
            ->leftJoin('orders', function($join){
                $join->on('orders.id', '=', 'products_stocks_logs.order_id');
            });
    }

    public function setAdminRowsAttributes($attributes)
    {
        $attributes['created'] = $this->created_at->translatedFormat('d. M Y \o H:i');
        $attributes['sub'] = ($this->sub > 0 ? '+' : '').$this->sub.' ks';
        $attributes['orderId'] = $this->order_number;

        if ( $this->log_id ){
            $attributes['message'] = sprintf(_('Vrátenie stavu č. %s'), $this->log_id);
        }

        return $attributes;
    }

    protected $settings = [
        'columns.orderId.name' => 'Objednávka č.',
        'columns.orderId.after' => 'stock',
        'columns.created.name' => 'Dátum',
    ];
}