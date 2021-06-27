<?php

namespace AdminEshop\Models\Products;

use AdminEshop\Admin\Rules\SetDefaultGalleryImage;
use AdminEshop\Eloquent\Concerns\HasProductImage;
use Admin\Eloquent\AdminModel;

class ProductsGallery extends AdminModel
{
    use HasProductImage;

    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2018-01-11 17:45:15';

    /*
     * Template name
     */
    protected $name = 'Galéria produktu';

    protected $inTab = true;
    protected $withoutParent = true;

    protected $rules = [
        SetDefaultGalleryImage::class,
    ];

    /*
     * Automatic form and database generation
     * @name - field name
     * @placeholder - field placeholder
     * @type - field type | string/text/editor/select/integer/decimal/file/password/date/datetime/time/checkbox/radio
     * ... other validation methods from laravel
     */
    protected $fields = [
        'image' => 'name:Obrázok|image|multirows',
        'default' => 'name:Hlavná fotografia produktu|type:checkbox|default:0',
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

    public function setDetailResponse()
    {
        $this->setVisible(['id', 'detailThumbnail']);

        $this->append(['detailThumbnail']);
    }
}