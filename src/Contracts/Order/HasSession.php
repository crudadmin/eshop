<?php

namespace AdminEshop\Contracts\Order;

trait HasSession
{
    /**
     * Session key of stored order
     *
     * @var  string
     */
    protected $sessionKey = 'cart.orderData';

    /**
     * Store row into session
     *
     * @return  this
     */
    public function storeIntoSession()
    {
        session()->put($this->sessionKey, $this->getRequestData());
        session()->save();

        return $this;
    }

    /**
     * Get row data from session
     *
     * @return  this
     */
    public function getFromSession()
    {
        return session($this->sessionKey, []);
    }
}
?>