<?php

namespace AdminEshop\Models\Store;

use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Store as StoreFacade;

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
                'Nastavenia cien' => Group::tab([
                    'decimal_places' => 'name:Zobrazovať ceny na|type:select|default:2|required',
                    'decimal_rounding' => 'name:Zaokrúhľovanie cien na|type:select|default:2|title:'.(
                        config('admineshop.prices.round_without_vat', false)
                            ? 'Zaokruhľovanie platí pre ceny bez DPH a taktiež pre ceny s DPH. Ak je cena bez DPH 1.625, výsledna cena s DPH bude 1.96'
                            : 'Zaokruhľovanie platí pre všetky ceny s DPH. Ceny produktov bez DPH sa nezaokruhľujú, z týchto nezaokruhlených cien je vypočítana finálna cena s DPH.'
                        ).'|required',
                    'decimal_separator' => 'name:Separator desatinných čísel|type:select|default:comma|required',
                ])->icon('fa-money'),
                'default_image' => 'name:Obrázok pri produktoch bez fotografie|type:file|image|required',
            ],
            config('admineshop.stock.store_rules', true) ?
                [
                    'Nastavenia skladu' => Group::tab([
                        'stock_type' => 'name:Predvolené nastavenie skladu|default:show|type:select|index',
                        'stock_sold' => 'name:Globálny text dostupnosti tovaru s nulovou skladovosťou|removeFromFormIfNot:stock_type,everytime'
                    ])->icon('fa-bars')->add('hidden'),
                ] : []
        ));
    }

    public function options()
    {
        $decimalPlaces = [
            3 => '3 '._('desetinné miesta'),
            2 => '2 '._('desetinné miesta'),
            1 => '1 '._('desetinné miesto'),
            0 => _('celé čísla'),
        ];

        return [
            'decimal_places' => $decimalPlaces,
            'decimal_rounding' => $decimalPlaces,
            'decimal_separator' => [
                'dot' => _('. - Bodka'),
                'comma' => _(', - Čiarka'),
            ],
            'stock_type' => [
                'show' => _('Zobraziť vždy s možnosťou objednania len ak je skladom'),
                'everytime' => _('Zobrazit a objednat vždy, bez ohľadu na sklad'),
                'hide' => _('Zobrazit a mať možnost objednat len ak je skladom'),
            ],
        ];
    }

    public function settings()
    {
        return [
            'decimals.places' => StoreFacade::getDecimalPlaces(),
            'decimals.rounding' => StoreFacade::getRounding(),
        ];
    }

    public function onTableCreate($table, $schema)
    {
        $this->insert([
            'decimal_places' => $this->getField('decimal_places')['default'] ?? null,
            'decimal_rounding' => $this->getField('decimal_rounding')['default'] ?? null,
            'decimal_separator' => $this->getField('decimal_separator')['default'] ?? null,
            'default_image' => '',
        ]);
    }

    public function setBootstrapResponse()
    {
        return $this->setVisible(['id', 'email', 'decimal_places', 'decimal_rounding', 'decimal_separator']);
    }
}