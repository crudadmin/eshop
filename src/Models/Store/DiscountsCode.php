<?php

namespace AdminEshop\Models\Store;

use AdminEshop\Admin\Rules\GenerateDiscountCode;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;

class DiscountsCode extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2020-01-10 10:51:25';

    /*
     * Template name
     */
    protected $name = 'Zľavové kódy';

    /*
     * Template title
     * Default ''
     */
    protected $title = '';

    protected $group = 'store.settings';

    protected $icon = 'fa-percent';

    /*
     * Automatic form and database generation
     * @name - field name
     * @placeholder - field placeholder
     * @type - field type | string/text/editor/select/integer/decimal/file/password/date/datetime/time/checkbox/radio
     * ... other validation methods from laravel
     */
    public function fields($row)
    {
        return [
            'Nastavenia kódu' => Group::half([
                'code' => 'name:Kód|min:5|max:30|index|title:Ak pole bude obsahovať prázdnu hodnotu, kód sa vygeneruje automatický.|placeholder:Zadajte kód zľavu|unique:discounts_codes,code,'.(isset($row) ? $row->getKey() : 'NULL').',id,deleted_at,NULL',
                Group::fields([
                    'price_limit' => 'name:Minimálna cena objednávky (€)|title:Bez DPH|type:decimal',
                    'usage' => 'name:Maximálny počet využia (ks)|title:Limit počtu použitia kupónu|type:integer|default:1',
                    'used' => 'name:Využitý|title:Koľko krát bol kupón využitý a použitý pri objednávke|type:integer|default:0',
                ])->inline(),
            ]),

            'Vyberte jednu alebo viac zliav' => Group::half([
                'discount_percent' => 'name:Zľava v %|min:0|type:decimal|removeFromFormIfNot:discount_price,|required_without_all:discount_price,free_delivery',
                'discount_price' => 'name:Zľava v €|min:0|type:decimal|removeFromFormIfNot:discount_percent,|required_without_all:discount_percent,free_delivery',
                'free_delivery' => 'name:Doprava zdarma|type:checkbox|default:0',
            ])->inline(),

            'fb_id' => 'name:FB Id|invisible',
        ];
    }

    protected $settings = [
        'title.insert' => 'Nový zľavový kód',
        'title.update' => 'Upravujete kód :code',
    ];

    protected $rules = [
        GenerateDiscountCode::class
    ];
}