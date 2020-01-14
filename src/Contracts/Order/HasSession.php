<?php

namespace AdminEshop\Contracts\Order;

use Cart;

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
     * Flush client data from session
     *
     * @return  this
     */
    public function flushFromSession()
    {
        session()->forget($this->sessionKey);
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