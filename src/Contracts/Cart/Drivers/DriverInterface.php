<?php

namespace AdminEshop\Contracts\Cart\Drivers;

interface DriverInterface
{
    /**
     * On create session driver. We need define default params
     *
     * @return  void
     */
    public function onCreate(array $initialData = []);

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
     * Flush item from driver
     *
     * @param string $key
     *
     * @return  void
     */
    public function forget($key = null);

    /**
     * Destroy whole cart instance
     *
     * @return  void
     */
    public function destroy();
}