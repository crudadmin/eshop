<?php

namespace AdminEshop\Models\Store;

use AdminEshop\Admin\Rules\GenerateDiscountCode;
use AdminEshop\Contracts\Discounts\DiscountCode;
use AdminEshop\Contracts\Discounts\FreeDeliveryByCode;
use AdminEshop\Contracts\Discounts\FreeDeliveryFromPrice;
use Admin\Eloquent\AdminModel;
use Admin\Fields\Group;
use Carbon\Carbon;
use Discounts;
use Store;

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

    protected $group = 'store';

    protected $icon = 'fa-percent';

    protected $sortable = false;

    public function active()
    {
        return Discounts::isRegistredDiscount(DiscountCode::class);
    }

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
                'min_order_price' => 'name:Minimálna cena objednávky (€)|title:S DPH|type:decimal',
                Group::inline([
                    'valid_from' => 'name:Platný od dátumu|type:date|title:Pri neuvedenom dátume platí neobmedzene',
                    'valid_to' => 'name:Platný do dátumu|type:date|title:Pri neuvedenom dátume platí neobmedzene',
                ]),
                Group::inline([
                    'usage' => 'name:Maximálny počet využia (ks)|title:Limit počtu použitia kupónu. Pri prázdnej hodnote plati neobmedzene.|type:integer|inAdmin:default:1',
                    'used' => 'name:Počet využitia kupónu|disabled|title:Koľko krát bol kupón využitý a použitý pri objednávke|type:integer|default:0',
                ]),
            ])->id('settings'),

            'Vyberte jednu alebo viac zliav' => Group::half([
                'discount_percentage' => 'name:Zľava v %|title:Z celkovej ceny objednávky|min:0|max:100|type:decimal|readonlyIfNot:discount_price,|required_without_all:discount_price,free_delivery',
                'discount_price' => 'name:Zľava v €|title:Z celkovej ceny objednávky, bez DPH.|min:0|type:decimal|readonlyIfNot:discount_percentage,|required_without_all:discount_percentage,free_delivery',
                'free_delivery' => 'name:Doprava zdarma|type:checkbox|default:0',
            ])->inline(),
        ];
    }

    protected $settings = [
        'title.insert' => 'Nový zľavový kód',
        'title.update' => 'Upravujete kód :code',
    ];

    protected $rules = [
        GenerateDiscountCode::class
    ];

    public function mutateFields($fields)
    {
        parent::mutateFields($fields);

        //If free delivery by discount code is not registred, we want hide this fields for discount codes
        if ( Discounts::isRegistredDiscount(FreeDeliveryByCode::class) == false ){
            $fields->field('free_delivery', function($field){
                $field->invisible = true;
            });
        }
    }

    /**
     * Returns array of dicount names for given discount code
     *
     * => text with vat
     * => text without vat
     *
     * @return  [type]
     */
    public function getNameArrayAttribute()
    {
        $value = '';
        $freeDeliveryText = '';

        //If is only discount from order sum
        if (!is_null($this->discount_price)) {
            $value = Store::priceFormat($this->discount_price);
            $valueWithVat = Store::priceFormat(Store::priceWithVat($this->discount_price));
        }

        //If is percentual discount
        else if (!is_null($this->discount_percentage)) {
            $value .= $this->discount_percentage.' %';
        }

        //If has free delivery
        if ( $this->free_delivery ) {
            $freeDeliveryText .= _('Doprava zdarma');
        }

        return [
            'withVat' => $this->buildName(@$valueWithVat ?: $value, $freeDeliveryText),
            'withoutVat' => $this->buildName($value, $freeDeliveryText),
        ];
    }

    /**
     * You can modify here text for € or % values
     * for example from "10%" you can make "Zľava 10%"
     * Zla
     *
     * @param  string  $text
     * @return  string
     */
    public function buildName($value, $freeDeliveryText = null)
    {
        $separator = $value && $freeDeliveryText ? ' + ' : '';

        return $value.$separator.$freeDeliveryText;
    }

    /**
     * Returns discount text value indicator without vat
     * "10% + Doprava zdarma" or "10€ + Doprava zdarma"
     *
     * @return  string
     */
    public function getNameWithoutVatAttribute()
    {
        return $this->nameArray['withoutVat'] ?? null;
    }

    /**
     * Returns discount text value indicator with vat
     * "10% + Doprava zdarma" or "10€ + Doprava zdarma"
     *
     * @return  string
     */
    public function getNameWithVatAttribute()
    {
        return $this->nameArray['withVat'] ?? null;
    }

    /**
     * Check if coupon is active
     *
     * @return  bool
     */
    public function getIsActiveAttribute()
    {
        return $this->isBeforeValidDate === false && $this->isExpired === false && $this->isUsed === false;
    }

    /**
     * Check if coupon has been used over limit
     *
     * @return  bool
     */
    public function getIsUsedAttribute()
    {
        //Discount code without usage restriction
        if ( is_null($this->usage) ){
            return false;
        }

        return $this->used >= $this->usage;
    }

    /**
     * Check if coupon has been expired
     *
     * @return  bool
     */
    public function getIsExpiredAttribute()
    {
        return $this->valid_to && Carbon::now()->setTime(0, 0, 0) > $this->valid_to;
    }

    /**
     * Check if coupon has been expired
     *
     * @return  bool
     */
    public function getIsBeforeValidDateAttribute()
    {
        return $this->valid_from && Carbon::now()->setTime(0, 0, 0) < $this->valid_from;
    }

    public function setDiscountResponse()
    {
        $this->setVisible(['id', 'code', 'isActive']);

        $this->append('isActive', 'name');

        return $this;
    }
}