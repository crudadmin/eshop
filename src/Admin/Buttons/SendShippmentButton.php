<?php

namespace AdminEshop\Admin\Buttons;

use AdminEshop\Models\Orders\Order;
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
        $this->name = _('Odoslať do dopravnej služby');

        $this->active = $this->getActiveStatus($row);
    }

    private function getActiveStatus($row)
    {
        if ( in_array($row->delivery_status, ['new', 'error']) ) {
            return $this->getShippingProvider($row)?->isActive() ?: false;
        }

        return false;
    }

    private function getShippingProvider(Order $order)
    {
        if ( $provider = OrderService::setOrder($order)->getShippingProvider($order->delivery_id) ){
            return $provider;
        }
    }

    public function question(Order $order)
    {
        return $this->getShippingProvider($order)->buttonQuestion($this);
    }

    /**
     * Firing callback on press button
     * @param Admin\Models\Model $row
     * @return object
     */
    public function fire(AdminModel $row)
    {
        $options = $this->getShippingProvider($row)->getButtonOptions($this);

        //Button response returned
        if ( $options instanceof Button ){
            return $options;
        }

        OrderService::setOrder($row)->sendShipping($options);

        $row->refresh();

        if ( $row->delivery_status == 'new' ) {
            return $this->message(_('Zasielka bude za malý okamih odoslaná na do zvolenej dopravnej služby. Sledujte stav objednávky doručenia.'));
        }

        if ( $row->delivery_status == 'ok' ) {
            return $this->message(_('Zásielka bola úspešne odoslaná do zvolenej dopravnej služby.'));
        }

        return $this->error(_('Nastala nečakná chyba. Skontrolujte hlásenia pre danu objednávku.'));
    }
}