<?php

namespace AdminEshop\Models\Store;

use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;

class Store extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2018-01-03 17:40:15';

    /*
     * Template name
     */
    protected $name = 'Parametre eshopu';

    /*
     * Template title
     * Default ''
     */
    protected $title = '';

    protected $group = 'store';

    protected $single = true;

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
            'email' => 'name:Email obchodu|title:Slúži pre obdržanie kópie emailov z objednávok|email',
            Group::inline([
                'rounding' => 'name:Zaokrúhľovanie čísel|type:select|default:0|required',
                'decimal_separator' => 'name:Separator desatinných čísel|type:select|default:comma|required',
            ]),
            'default_image' => 'name:Obrázok pri produktoch bez fotografie|type:file|image|required',
            'Nastavenia skladu' => Group::tab([
                'stock_type' => 'name:Predvolené nastavenie skladu|default:show|type:select|index',
                'stock_sold' => 'name:Globálny text dostupnosti tovaru s nulovou skladovosťou|removeFromFormIfNot:stock_type,everytime'
            ])->icon('fa-bars')->add('hidden'),
        ];
    }

    public function options()
    {
        return [
            'rounding' => [
                2 => 'na 2 desetinné miesta',
                1 => 'na 1 desetinné miesto',
                0 => 'na celé čísla',
            ],
            'decimal_separator' => [
                'dot' => '. - Bodka',
                'comma' => ', - Čiarka',
            ],
            'stock_type' => [
                'show' => 'Zobraziť vždy s možnosťou objednania len ak je skladom',
                'everytime' => 'Zobrazit a objednat vždy, bez ohľadu na sklad',
                'hide' => 'Zobrazit a mať možnost objednat len ak je skladom',
            ],
        ];
    }

}