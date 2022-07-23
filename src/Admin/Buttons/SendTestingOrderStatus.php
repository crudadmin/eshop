<?php

namespace AdminEshop\Admin\Buttons;

use AdminEshop\Mail\OrderStatus;
use AdminEshop\Models\Orders\Order;
use Admin\Eloquent\AdminModel;
use Admin\Helpers\Button;
use Illuminate\Support\Facades\Mail;
use OrderService;
use Store;
use Localization;

class SendTestingOrderStatus extends Button
{
    /*
     * Button type
     * button|action|multiple
     */
    public $type = 'multiple';

    //Button classes
    public $class = 'btn-primary';

    //Button Icon
    public $icon = 'fa-envelope-o';

    const SESSION_ORDER_KEY = 'status_test_number';

    public function __construct($row)
    {
        $this->name = _('Odoslať testovaci email');

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

        if ( !($order = Order::latest()->select(['number'])->first()) ){
            return $this->error(_('Pre otestovanie vytvorte aspoň jednú objednávku.'));
        }

        $number = session()->get(self::SESSION_ORDER_KEY, $order->number);

        return $this
                ->warning(sprintf(_('Na Váš email <strong>%s</strong> bude odoslaná správa s týmto stavom objednávky.'), $email))
                ->component('SendTestOrderStatusEmail', [
                    'order_number' => $number,
                ]);
    }

    /**
     * Firing callback on press button
     * @param Admin\Models\Model $row
     * @return object
     */
    public function fire(AdminModel $status)
    {
        //Boot website localization for templates
        Localization::boot();

        $number = request('order_number');

        if ( !($order = Order::where('number', $number)->first()) ){
            return $this->error(_('Táto objednávka nebola nájdená.'));
        }

        session()->put(self::SESSION_ORDER_KEY, $number);
        session()->save();

        $order->status_id = $status->getKey();

        //We need rewrite order email for testing purpose
        $order->email = $this->getStoreEmail();

        if ( $status->default === true ) {
            OrderService::setOrder($order)->setOrderCartItems($order)->sentClientEmail(null, false);
        } else {
            Mail::to($order->email)->send(new OrderStatus($order));
        }

        return $this->success('Email bol úspešne odoslaný na Vašu adresu.');
    }
}