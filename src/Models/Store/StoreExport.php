<?php

namespace AdminEshop\Models\Store;

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
            'type' => 'name:Typ reportu|type:select|required',
            Group::inline([
                'date_from' => 'name:Od dňa|type:datetime|default:'.Carbon::now()->format('d.m.Y 00:00').'|required',
                'date_to' => 'name:Do dňa|type:datetime|default:'.Carbon::now()->format('d.m.Y 23:59').'|required',
            ]),
            'file' => 'name:Export|type:file|removeFromForm',
        ];
    }

    public function onCreate($row)
    {
        $orders = Order::whereDate('created_at', '>=', $row->date_from)
                        ->whereDate('created_at', '<=', $row->date_to)
                        ->with([
                            'payment_method' => function($query){
                                $query->withTrashed();
                            },
                            'delivery' => function($query){
                                $query->withTrashed();
                            }
                        ])
                        ->get();

        foreach (OrderService::getShippingProviders() as $provider) {
            if ( $provider->getKey() == $row->type ){
                $path = OrderService::makeShippingExport(get_class($provider), $orders);

                $row->file = basename($path);
                break;
            }
        }

        $row->save();
    }

    public function options()
    {
        return [
            'type' => $this->getExportTypes(),
        ];
    }

    private function getExportTypes()
    {
        $types = [];

        //Add buttons from shipping providers for exports
        foreach (OrderService::getShippingProviders() as $provider) {
            if ( $provider->isExportable() ) {
                $types[$provider->getKey()] = $provider->getName();
            }
        }

        return $types;
    }
}