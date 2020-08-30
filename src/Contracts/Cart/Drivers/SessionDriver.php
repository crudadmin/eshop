<?php

namespace AdminEshop\Contracts\Cart\Drivers;

use AdminEshop\Contracts\Cart\Drivers\DriverInterface;

class SessionDriver implements DriverInterface
{
    /*
     * Session key for basket items
     */
    private $key = 'cart';

    /**
     * On create session driver. We need define default params
     *
     * @return  void
     */
    public function __construct(array $initialData = [])
    {
        //Boot session driver with default values
        if ( session()->has($this->key) === false ){
            session()->put($this->key, $initialData);
            session()->save();
        }
    }

    /**
     * Set data into cart session
     *
     * @param  string|null  $key
     * @param  mixed  $value
     */
    public function set($key, $value)
    {
        session()->put($this->key.($key ? '.'.$key : ''), $value);
        session()->save();
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
        return session($this->key.($key ? ('.'.$key) : ''), $default);
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
        session()->forget($this->key.($key ? ('.'.$key) : ''));
        session()->save();
    }
}