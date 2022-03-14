<?php

namespace AdminEshop\Admin\Buttons;

use OrderService;
use Admin\Eloquent\AdminModel;
use Admin\Helpers\Button;
use Store;

class SetProductsDiscount extends Button
{
    /*
     * Here is your place for binding button properties for each row
     */
    public function __construct($row)
    {
        //Name of button on hover
        $this->name = 'Hromadná zľava';

        //Button classes
        $this->class = 'btn-default';

        //Button Icon
        $this->icon = 'fa-percentage';

        $this->type = 'action';

        $this->active = true;
    }

    /*
     * Ask question with form before action
     */
    public function question($row)
    {
        return $this->title('Vyberte zľavu pre vybrané produkty:')
                    ->component('SetProductDiscounts', [
                        'discount_operator' => [ 'default' => 'Žiadna zľava' ] + operator_types(),
                    ])
                    ->type('default');
    }

    /*
     * Firing callback on press button
     */
    public function fireMultiple($rows)
    {
        if ( $rows->count() ){
            $rows->each(function($product){
                if ( in_array($product->product_type, array_merge(Store::nonVariantsProductTypes(), ['variant'])) ){
                    $this->setProductDiscount($product);
                } else if ( in_array($product->product_type, Store::variantsProductTypes()) ) {
                    $product->variants->each(function($variant){
                        $this->setProductDiscount($variant);
                    });
                }
            });
        }

        return $this->success('Zľava pre vybrané produkty bola úspešne uložená.');
    }

    public function setProductDiscount($product)
    {
        $product->discount_operator = request('discount_operator', 'default');
        $product->discount = request('discount', null);

        $product->save();
    }
}