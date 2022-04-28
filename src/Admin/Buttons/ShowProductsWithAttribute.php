<?php

namespace AdminEshop\Admin\Buttons;

use Admin\Eloquent\AdminModel;
use Admin\Helpers\Button;

class ShowProductsWithAttribute extends Button
{
    /*
     * Here is your place for binding button properties for each row
     */
    public function __construct(AdminModel $row)
    {
        //Name of button on hover
        $this->name = _('Zobraziť priradené pridukty');

        $this->class = 'btn-default';

        //Button Icon
        $this->icon = 'fa-binoculars';

        $this->active = true;
    }

    /*
     * Firing callback on press button
     */
    public function fire(AdminModel $row)
    {
        $query = $row->products();
        $rows = [];

        foreach ($query->select('products.id', 'products.name', 'products.code')->get() as $product) {
            $rows[] = implode(' - ', array_filter([
                $product->id,
                $product->name,
                $product->code,
            ]));
        }

        return $this->title(_('Produkty s priradeným atribútom').' ('.count($rows).')')->message(
            '<textarea class="form-control" rows="20">'.implode("\n", $rows).'</textarea>'
        );
    }
}