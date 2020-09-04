<?php

namespace AdminEshop\Contracts\Cart\Concerns;

use AdminEshop\Contracts\Cart\Drivers\ProxyDriver;
use Cart;
use CartDriver;
use Admin;

trait DriverSupport
{
    /**
     * Cart driver
     *
     * @var  AdminEshop\Contracts\Cart\Drivers\ProxyDriver
     */
    protected $driver;

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
     * Returns proxy cart driver
     *
     * @return AdminEshop\Contracts\Cart\Drivers\ProxyDriver
     */
    public function getDriver()
    {
        if ( $this->driver ){
            return $this->driver;
        }

        //Cache driver for all other classes which requires driver
        $driver = Admin::cache('admineshop.cartDriver', function(){
            $driver = config('admineshop.cart.driver');

            return new $driver(
                CartDriver::getInitialData()
            );
        });

        //Register given class that uses driver
        CartDriver::registerDriverClass($this);

        //Register all classes which accessed to proxy driver and
        //then flush all classes with true param...
        // dump(get_class($this));

        $this->driver = new ProxyDriver($driver, $this->getDriverKey());

        return $this->driver;
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