<?php

namespace AdminEshop\Admin\Buttons;

use AdminEshop\Mail\OrderStatus;
use AdminEshop\Models\Orders\Order;
use Admin\Eloquent\AdminModel;
use Admin\Helpers\Button;
use Illuminate\Support\Facades\Mail;
use OrderService;
use Store;

class SendTestingOrderStatus extends Button
{
    /*
     * Button type
     * button|action|multiple
     */
    public $type = 'multiple';

    //Name of button on hover
    public $name = 'Odoslať testovaci email';

    //Button classes
    public $class = 'btn-primary';

    //Button Icon
    public $icon = 'fa-envelope-o';

    public function __construct($row)
    {
        $this->active = $row->email_send == true || $row->default === true;
    }

    private function getStoreEmail()
    {
        return Store::getSettings()->email;
    }

    public function question(AdminModel $order)
    {
        if ( !($email = $this->getStoreEmail()) ){
            return $this->error(_('Pre odoslanie testovacieho emailu si nastavte email v nastaveniach obchodu.'));
        }

        return $this->warning(sprintf(_('Na Váš email %s bude odoslaná posledná vytvorená objednávka s týmto stavom objednávky.'), $email));
    }

    /**
     * Firing callback on press button
     * @param Admin\Models\Model $row
     * @return object
     */
    public function fire(AdminModel $status)
    {
        $order = Order::latest()->first();
        $order->status_id = $status->getKey();

        $email = $this->getStoreEmail();

        if ( $status->default === true ) {
            OrderService::setOrder($order)->setOrderCartItems($order)->sentClientEmail(null, $email, false);
        } else {
            Mail::to($email)->send(new OrderStatus($order));
        }

        return $this->success('Email bol úspešne odoslaný na Vašu adresu.');
    }
}