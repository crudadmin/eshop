<?php

namespace AdminEshop\Contracts\Cart\Drivers;

use AdminEshop\Contracts\Cart\Drivers\DriverInterface;
use Cart;

class ProxyDriver
{
    /**
     * Scope key for given class
     *
     * @var  string
     */
    private $driverKey;

    /**
     * Cart driver
     *
     * @var  AdminEshop\Contracts\Cart\Drivers\CartDriver
     */
    private $driver;

    /**
     * Set key scope for given driver
     *
     * @param  string|null  $driverKey
     */
    public function __construct(DriverInterface $driver, $driverKey)
    {
        $this->driver = $driver;

        $this->driverKey = $driverKey;
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        //Forward calls into selected drivers
        if ( method_exists($this, $method) ) {
            return $this->{$method}(...$parameters);
        }

        return $this->driver->{$method}(...$parameters);
    }

    /**
     * Set data into cart session
     *
     * @param  string  $key
     * @param  mixed  $value
     */
    public function set($key, $value)
    {
        $this->driver->set($this->keyName($key) ?: null, $value);

        return $this;
    }

    /**
     * Get data from driver
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return  mixed
     */
    public function get($key = '', $default = null)
    {
        return $this->driver->get($this->keyName($key), $default);
    }

    /**
     * Flush item from session
     *
     * @param string $key
     *
     * @return  void
     */
    public function forget($key = null)
    {
        return $this->driver->forget($this->keyName($key));
    }

    /**
     * Get driver key
     *
     * @return  string
     */
    public function getDriverKey()
    {
        return $this->driverKey;
    }

    /**
     * Build storage key name by driver key
     *
     * @param  string  $key
     *
     * @return  string
     */
    private function keyName($key)
    {
        return implode('.', array_filter([$this->driverKey, $key]));
    }
}