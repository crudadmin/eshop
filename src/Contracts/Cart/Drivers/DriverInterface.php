<?php

namespace AdminEshop\Contracts\Cart\Drivers;

interface DriverInterface
{
    /**
     * On create session driver. We need define default params
     *
     * @return  void
     */
    public function __construct(array $initialData = []);

    /**
     * Set data into cart session
     * If key is present as null, we want replace whole object data with given value
     *
     * @param  string|null  $key
     * @param  mixed  $value
     */
    public function set($key, $value);

    /**
     * Replace all data in driver
     *
     * @param  array  $data
     */
    public function replace(array $data);

    /**
     * Get data from driver
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return  mixed
     */
    public function get($key, $default = null);

    /**
     * Flush item from driver
     *
     * @param string $key
     *
     * @return  void
     */
    public function forget($key = null);
}