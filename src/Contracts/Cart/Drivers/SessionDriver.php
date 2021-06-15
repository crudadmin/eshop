<?php

namespace AdminEshop\Contracts\Cart\Drivers;

use AdminEshop\Contracts\Cart\Drivers\BaseDriver;
use AdminEshop\Contracts\Cart\Drivers\DriverInterface;

class SessionDriver extends BaseDriver implements DriverInterface
{
    /*
     * Session key for basket items
     */
    const SESSION_KEY = 'cart';

    /**
     * On create session driver. We need define default params
     *
     * @return  void
     */
    public function __construct(array $initialData = [])
    {
        //Boot session driver with default values
        if ( session()->has(self::SESSION_KEY) === false ){
            session()->put(self::SESSION_KEY, $initialData);
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
        session()->put(self::SESSION_KEY.'.'.$key, $value);
        session()->save();

        return $this;
    }

    /**
     * Replace whole data
     *
     * @param  array  $data
     *
     * @return  this
     */
    public function replace(array $data)
    {
        session()->put(self::SESSION_KEY, $data);
        session()->save();

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
        return session(self::SESSION_KEY.($key ? ('.'.$key) : ''), $default);
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
        session()->forget(self::SESSION_KEY.($key ? ('.'.$key) : ''));
        session()->save();
    }
}