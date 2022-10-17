<?php

namespace AdminEshop\Models\Store;

use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Gogol\Invoices\Admin\Rules\SetDefault;
use Store;

class Currency extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2021-10-17 13:51:25';

    /*
     * Template name
     */
    protected $name = 'Meny';

    /*
     * Template title
     * Default ''
     */
    protected $title = '';

    protected $group = 'store';

    protected $reversed = true;

    protected $publishable = false;

    protected $sortable = false;

    protected $icon = 'fa-coins';

    protected $settings = [
        'title.insert' => 'Nová mena',
        'title.update' => ':name',
        'columns.id.hidden' => true,
    ];

    protected $rules = [
        SetDefault::class,
    ];

    public function settings()
    {
        return [
            'decimals.places' => Store::getDecimalPlaces(),
            'decimals.rounding' => Store::getRounding(),
        ];
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
            'name' => 'name:Názov meny|required',
            'code' => 'name:Kód meny|placeholder:EUR,USD|required',
            'char' => 'name:Značka meny|required|max:6|placeholder:€, $, EUR, USD...',
            'rate' => 'name:Kurz|type:decimal|title:Kurz voči predvolenej mene|required',
            'default' => 'name:Predvolená mena|type:checkbox|default:0',
            'Nastavenia cien' => Group::tab([
                'decimal_places' => 'name:Zobrazovať ceny na|type:select|default:2|required',
                'decimal_rounding' => 'name:Zaokrúhľovanie cien na|type:select|default:2|title:'.(
                    config('admineshop.prices.round_without_vat', false)
                        ? 'Zaokruhľovanie platí pre ceny bez DPH a taktiež pre ceny s DPH. Ak je cena bez DPH 1.625, výsledna cena s DPH bude 1.96'
                        : 'Zaokruhľovanie platí pre všetky ceny s DPH. Ceny produktov bez DPH sa nezaokruhľujú, z týchto nezaokruhlených cien je vypočítana finálna cena s DPH.'
                    ).'|required',
                'decimal_separator' => 'name:Separator desatinných čísel|type:select|default:comma|required',
            ])->icon('fa-money'),
        ];
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
        ];
    }

    public function onTableCreate()
    {
        //When roles table is created, set all users as super admins.
        $this->create([
            'name' => 'Euro',
            'code' => 'eur',
            'char' => '€',
            'rate' => 1,
            'default' => 1,
            'decimal_places' => $this->getField('decimal_places')['default'] ?? null,
            'decimal_rounding' => $this->getField('decimal_rounding')['default'] ?? null,
            'decimal_separator' => $this->getField('decimal_separator')['default'] ?? null,
        ]);
    }

    public function setResponse()
    {
        return $this->setVisible([
            'id', 'name', 'char', 'code',
            'decimal_places', 'decimal_rounding', 'decimal_separator'
        ]);
    }
}