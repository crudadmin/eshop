<?php

namespace AdminEshop\Contracts\Cart\Drivers;

interface DriverInterface
{
    /**
     * Set data into cart session
     *
     * @param  string  $key
     * @param  mixed  $value
     */
    public function set($key, $value);

    /**
     * Get data from driver
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return  mixed
     */
    public function get($key, $default = null);

    /**
     * Flush item from session
     *
     * @param string $key
     *
     * @return  void
     */
    public function forget($key = null);
}