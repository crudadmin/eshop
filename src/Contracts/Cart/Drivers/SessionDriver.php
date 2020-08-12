<?php

namespace AdminEshop\Contracts\Cart\Drivers;

use AdminEshop\Contracts\Cart\Drivers\CartDriver;
use AdminEshop\Contracts\Cart\Drivers\DriverInterface;

class SessionDriver extends CartDriver implements DriverInterface
{
    /*
     * Session key for basket items
     */
    private $key = 'cart';

    /**
     * Set data into cart session
     *
     * @param  [type]  $key
     * @param  [type]  $value
     */
    public function set($key, $value)
    {
        session()->put($this->key.'.'.$key, $value);
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

    /**
     * Delete data from
     *
     * @return  void
     */
    public function destroy()
    {
        session()->forget($this->key);
        session()->save();
    }
}