<?php

namespace AdminEshop\Models\Orders;

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
            'default' => 'name:Predvolený stav|type:checkbox|default:0|title:Po vytvorení budú objednávky v tomto stave',
            'key' => 'name:Pôvodný kľúč|inaccessible',
        ];
    }

    public function onTableCreate($table, $schema)
    {
        $hasSortable = $this->isSortable();

        $storeId = Store::first()->getKey();

        $i = -1;

        $languages = [
            ['store_id' => $storeId, 'name' => 'Pripravuje sa', 'default' => true, 'key' => 'new,waiting'] + ($hasSortable ? ['_order' => $i++] : []),
            ['store_id' => $storeId, 'name' => 'Zabalená', 'default' => false, 'key' => 'shipped'] + ($hasSortable ? ['_order' => $i++] : []),
            ['store_id' => $storeId, 'name' => 'Odoslaná', 'default' => false, 'key' => 'ok'] + ($hasSortable ? ['_order' => $i++] : []),
            ['store_id' => $storeId, 'name' => 'Zrušená', 'default' => false, 'key' => 'canceled'] + ($hasSortable ? ['_order' => $i++] : []),
        ];

        $this->insert($languages);
    }
}