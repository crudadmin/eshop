<?php

namespace AdminEshop\Models\Products;

use Admin;
use AdminEshop\Models\Products\Product;
use AdminEshop\Notifications\ProductAvailableNotification;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Illuminate\Notifications\Notifiable;

class ProductsNotification extends AdminModel
{
    use Notifiable;

    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2021-08-09 18:55:23';

    /*
     * Template name
     */
    protected $name = 'Notifikácie pri naskladnení';

    protected $publishable = false;

    protected $sortable = false;

    protected $belongsToModel = [
        Product::class,
    ];

    protected $active = false;

    /*
     * Automatic form and database generator by fields list
     * :name - field name
     * :type - field type (string/text/editor/select/integer/decimal/file/password/date/datetime/time/checkbox/radio)
     * ... other validation methods from laravel
     */
    public function fields()
    {
        return [
            'email' => 'name:E-mail|required|email|max:90',
            'notified' => 'name:Upozornený emailom|type:checkbox|default:0',
            'notified_error' => 'name:Chyba pri upozornení|type:checkbox|default:0|inaccessible',
        ];
    }

    public function getNotifierValidator()
    {
        return $this->validator()->only([
            'product_id' => 'required_without:variant_id|exists:products,id',
            'variant_id' => 'required_without:product_id|exists:products,id',
            'email',
        ]);
    }

    public function sendNotification()
    {
        $this->notify(new ProductAvailableNotification($this));
    }

    public function getProductUrl()
    {
        if ( $this->variant ) {
            $url = _('/product/:index/:variant');
            $url = str_replace(':index', $this->product->getSlug(), $url);
            $url = str_replace(':variant', $this->variant->getKey(), $url);

            return env('APP_NUXT_URL').$url;
        }

        return env('APP_NUXT_URL').str_replace(':index', $this->product->getSlug(), _('/product/:index'));
    }
}