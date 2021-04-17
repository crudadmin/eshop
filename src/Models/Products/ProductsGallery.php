<?php

namespace AdminEshop\Models\Products;

use Admin\Eloquent\AdminModel;

class ProductsGallery extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2018-01-11 17:45:15';

    /*
     * Template name
     */
    protected $name = 'Galéria produktu';

    protected $belongsToModel = Product::class;

    protected $inTab = true;
    protected $withoutParent = true;

    /*
     * Automatic form and database generation
     * @name - field name
     * @placeholder - field placeholder
     * @type - field type | string/text/editor/select/integer/decimal/file/password/date/datetime/time/checkbox/radio
     * ... other validation methods from laravel
     */
    protected $fields = [
        'image' => 'name:Obrázok|image|multirows',
    ];

    protected $settings = [
        'title.insert' => 'Nový obrázok',
        'title.update' => 'Upravujete obrázok',
        'title.rows' => 'Zoznam obrázkov',
        'increments' => false,
        'grid.enabled' => false,
    ];

    public function belongsToModel()
    {
        return config('admineshop.gallery.eloquents', []);
    }
}