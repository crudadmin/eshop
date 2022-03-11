<?php

namespace AdminEshop\Models\Orders;

use AdminEshop\Admin\Buttons\SendTestingOrderStatus;
use AdminEshop\Admin\Rules\SetDefaultOrderStatus;
use AdminEshop\Models\Store\Store;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;

class OrdersStatus extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2018-01-07 03:49:15';

    /*
     * Template name
     */
    protected $name = 'Stavy objednávok';

    /*
     * Template title
     * Default ''
     */
    protected $title = '';

    protected $icon = 'fa-question-circle';

    /*
     * Model Parent
     * Eg. Articles::class,
     */
    protected $belongsToModel = Store::class;

    protected $publishable = false;

    protected $reversed = true;

    protected $settings = [
        'title.create' => 'Nový stav',
    ];

    protected $rules = [
        SetDefaultOrderStatus::class,
    ];

    protected $buttons = [
        SendTestingOrderStatus::class,
    ];

    /*
     * Automatic form and database generation
     * @name - field name
     * @placeholder - field placeholder
     * @type - field type | string/text/editor/select/integer/decimal/file/password/date/datetime/time/checkbox
     * ... other validation methods from laravel
     */
    public function fields()
    {
        return [
            'name' => 'name:Názov|required',
            'color' => 'name:Farba objednávky|type:color',
            Group::inline([
                'default' => 'name:Predvolený stav|type:checkbox|default:0|title:Po vytvorení budú objednávky v tomto stave',
                'return_stock' => 'name:Vrátiť tovat na sklad|type:checkbox|default:0|title:Pri zvolení tohto stavu bude tovar z objednávky vráteny späť na sklad|removeFromFormIf:default,1'
            ]),
            'key' => 'name:Pôvodný kľúč|inaccessible',
            'Emailové notifikácie' => Group::tab([
                Group::inline([
                    'email_send' => 'name:Odoslať email pri zmene stavu|column_name:Email|type:checkbox|default:0',
                    'email_delivery' => 'name:Do zmeny stavu zahrnúť informácie o doprave|type:checkbox|default:0|removeFromFormIf:email_send,0',
                ])->add('removeFromFormIf:default,1'),
                'email_content' => 'name:Obsah emailu|type:editor|sub_component:ShowOrderStatusVariables',
            ])->icon('fa fa-envelope')->add('hidden')->id('notification')
        ];
    }

    public function onTableCreate($table, $schema)
    {
        $hasSortable = $this->isSortable();

        $storeId = Store::first()->getKey();

        $i = 1;

        $languages = [
            [
                'store_id' => $storeId,
                'name' => 'Pripravuje sa',
                'default' => true,
                'key' => 'new,waiting',
                'email_delivery' => 0,
                'email_content' => _('Vaša objednávka č. {number} zo dňa {date} bola úspešne prijatá.'),
                'return_stock' => false,
                'color' => '#ffb900'
            ] + ($hasSortable ? ['_order' => $i++] : []),
            [
                'store_id' => $storeId,
                'name' => 'Zabalená',
                'default' => false,
                'key' => 'shipped',
                'email_delivery' => 0,
                'return_stock' => false,
                'color' => null]
                 + ($hasSortable ? ['_order' => $i++] : []),
            [
                'store_id' => $storeId,
                'name' => 'Odoslaná',
                'default' => false,
                'key' => 'ok',
                'email_delivery' => 0,
                'return_stock' => false,
                'color' => '#2ecc71'
            ] + ($hasSortable ? ['_order' => $i++] : []),
            [
                'store_id' => $storeId,
                'name' => 'Zrušená',
                'default' => false,
                'key' => 'canceled',
                'email_delivery' => 0,
                'return_stock' => true,
                'color' => null
            ] + ($hasSortable ? ['_order' => $i++] : []),
        ];

        $this->insert($languages);
    }

    public function parseOrderText($key, $order)
    {
        $text = $this->{$key} ?? '';

        foreach ($order->append(['firstname', 'lastname'])->toArray() as $key => $value) {
            if ( is_string($value) || is_numeric($value) ) {
                $text = str_replace('{'.$key.'}', e($value), $text);
            }
        }

        $text = str_replace('{number}', $order->number, $text);
        $text = str_replace('{date}', $order->created_at->format('d.m.Y'), $text);
        $text = str_replace('{datetime}', $order->created_at->format('d.m.Y H:i'), $text);

        return $text;
    }
}