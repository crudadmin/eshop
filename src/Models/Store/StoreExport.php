<?php

namespace AdminEshop\Models\Store;

use AdminEshop\Admin\Rules\OnExportCreated;
use AdminEshop\Models\Orders\Order;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Carbon\Carbon;
use OrderService;

class StoreExport extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2018-01-03 17:42:24';

    /*
     * Template name
     */
    protected $name = 'Exporty';

    /*
     * Template title
     * Default ''
     */
    protected $title = '';

    protected $group = 'store';

    protected $publishable = false;
    protected $sortable = false;
    protected $editable = false;

    protected $rules = [
        OnExportCreated::class,
    ];

    public function active()
    {
        return count($this->getExportTypes()) > 0;
    }

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
            'type' => 'name:Typ reportu|type:select|option:name|required',
            Group::inline([
                'date_from' => 'name:Od dňa|type:datetime|default:'.Carbon::now()->format('d.m.Y 00:00').'|required',
                'date_to' => 'name:Do dňa|type:datetime|default:'.Carbon::now()->format('d.m.Y 23:59').'|required',
            ]),
            'file' => 'name:Export|type:file|removeFromForm',
        ];
    }

    public function options()
    {
        return [
            'type' => $this->getExportTypes(),
        ];
    }

    public function getExportTypes()
    {
        $types = config('admineshop.exports');

        //Add buttons from shipping providers for exports
        foreach (OrderService::getShippingProviders() as $provider) {
            if ( $provider->isExportable() ) {
                $types[$provider->getKey()] = [
                    'name' => $provider->getName(),
                    'class' => $provider,
                ];
            }
        }

        return $types;
    }
}