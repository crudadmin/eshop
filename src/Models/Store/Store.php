<?php

namespace AdminEshop\Models\Store;

use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Store as BaseStore;

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

    protected $publishable = false;
    protected $sortable = false;

    /*
     * Automatic form and database generation
     * @name - field name
     * @placeholder - field placeholder
     * @type - field type | string/text/editor/select/integer/decimal/file/password/date/datetime/time/checkbox/radio
     * ... other validation methods from laravel
     */
    public function fields()
    {
        return array_filter(array_merge(
            [
                'email' => 'name:Email obchodu|title:Slúži pre obdržanie kópie emailov z objednávok|email',
                'email_logo' => 'name:Logo v emailoch|type:file|image',
                'default_image' => 'name:Obrázok pri produktoch bez fotografie|type:file|image|required',
            ],
            config('admineshop.stock.store_rules', true) ?
                [
                    'Nastavenia skladu' => Group::tab([
                        'stock_type' => 'name:Predvolené nastavenie skladu|default:show|type:select|index',
                        'stock_sold' => 'name:Globálny text dostupnosti tovaru s nulovou skladovosťou|removeFromFormIfNot:stock_type,everytime'.(BaseStore::isEnabledLocalization() ? '|locale' : '')
                    ])->icon('fa-bars')->add('hidden'),
                ] : []
        ));
    }

    public function options()
    {
        return [
            'stock_type' => [
                'show' => _('Zobraziť vždy s možnosťou objednania len ak je skladom'),
                'everytime' => _('Zobrazit a objednat vždy, bez ohľadu na sklad'),
                'hide' => _('Zobrazit a mať možnost objednat len ak je skladom'),
            ],
        ];
    }

    public function onTableCreate($table, $schema)
    {
        $this->insert([
            'default_image' => '',
        ]);
    }

    public function setBootstrapResponse()
    {
        return $this->setVisible(['id', 'email']);
    }
}