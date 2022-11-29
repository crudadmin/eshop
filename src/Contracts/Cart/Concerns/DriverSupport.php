<?php

namespace AdminEshop\Contracts\Cart\Concerns;

use Admin;
use AdminEshop\Contracts\Cart;
use AdminEshop\Contracts\Cart\Drivers\ProxyDriver;
use CartDriver;

trait DriverSupport
{
    /**
     * Cart driver
     *
     * @var  AdminEshop\Contracts\Cart\Drivers\ProxyDriver
     */
    protected $driver = [];

    /**
     * Flush driver data on order successfully completed
     *
     * @var  bool
     */
    public function flushOnComplete()
    {
        return true;
    }

    /**
     * On driver flush event
     */
    public function onDriverFlush()
    {

    }

    /**
     * Returns proxy cart driver
     *
     * @return AdminEshop\Contracts\Cart\Drivers\ProxyDriver
     */
    public function getDriver($token = null)
    {
        $token = $token ?: Cart::getCartToken();

        //Return cached driver
        if ( isset($this->driver[$token]) ){
            return $this->driver[$token];
        }

        //Cache driver for all other classes which requires driver
        $driver = Admin::cache('admineshop.cartDriver.'.$token, function() use ($token) {
            $driver = config('admineshop.cart.driver');

            return new $driver($token, CartDriver::getInitialData());
        });

        //Register given class that uses driver
        CartDriver::registerDriverClass($this);

        $this->driver[$token] = new ProxyDriver($driver, $this->getDriverKey());

        return $this->driver[$token];
    }

    public function getDriverKey()
    {
        return property_exists($this, 'driverKey')
                    ? $this->driverKey
                    : class_basename(get_class($this));
    }

    /**
     * Build storage key name by driver key
     *
     * @param  string  $key
     *
     * @return  string
     */
    public function keyName($key)
    {
        return implode('.', array_filter([$this->getDriverKey(), $key]));
    }
}