<?php

namespace AdminEshop\Models\Delivery;

use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;

class DeliveriesLocation extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2020-04-21 18:47:13';

    /*
     * Template name
     */
    protected $name = 'Predajne';

    protected $icon = 'fa-store';

    protected $reversed = true;

    protected $belongsToModel = Delivery::class;

    protected $withoutParent = true;

    protected $hidden = ['created_at', 'updated_at', 'published_at', 'deleted_at', '_order'];

    public function active() {
        return config('admineshop.delivery.multiple_locations') === true;
    }

    /*
     * Automatic form and database generator by fields list
     * :name - field name
     * :type - field type (string/text/editor/select/integer/decimal/file/password/date/datetime/time/checkbox/radio)
     * ... other validation methods from laravel
     */
    public function fields()
    {
        return [
            'name' => 'name:Názov pobočky|required|max:90',
            'address' => 'name:Adresa pobočky|title:Slúži pre lepšiu identifikáciu miesta pre zákaznika',
            'identifier' => 'name:ID Pobočky|index|invisible',
            'data' => 'name:Data Pobočky|type:json|invisible',
        ];
    }

    protected $settings = [
        'grid.enabled' => false,
        'grid.default' => 'full',
    ];
}