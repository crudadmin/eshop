<?php

namespace AdminEshop\Admin\Buttons;

use Admin\Eloquent\AdminModel;
use Admin\Helpers\Button;
use Illuminate\Support\Collection;
use OrderService;

class SendShippmentButton extends Button
{
    /*
     * Button type
     * button|action|multiple
     */
    public $type = 'multiple';

    //Name of button on hover
    public $name = 'Odoslať do dopravnej služby';

    //Button classes
    public $class = 'btn-default';

    //Button Icon
    public $icon = 'fa-truck';

    /**
     * Here you can set your custom properties for each row
     * @param Admin\Models\Model $row
     */
    public function __construct($row)
    {
        $this->active = in_array($row->delivery_status, ['new', 'error'])
                        && OrderService::setOrder($row)->getShippingProvider($row->delivery_id);
    }

    /**
     * Firing callback on press button
     * @param Admin\Models\Model $row
     * @return object
     */
    public function fire(AdminModel $row)
    {
        OrderService::setOrder($row)->sendShipping();

        $row->refresh();

        if ( $row->delivery_status == 'new' ) {
            return $this->message('Zasielka bude za malý okamih odoslaná na do zvolenej dopravnej služby. Sledujte stav objednávky doručenia.');
        }

        if ( $row->delivery_status == 'ok' ) {
            return $this->message('Zásielka bola úspešne odoslaná do zvolenej dopravnej služby.');
        }

        return $this->error('Nastala nečakná chyba. Skontrolujte hlásenia pre danu objednávku.');
    }

    /**
     * Firing callback on press action for multiple items
     * @param Illuminate\Support\Collection $rows
     */
    // public function fireMultiple(Collection $rows)
    // {
    //     return $this->error('Your multiple rows action callback.');
    // }
}