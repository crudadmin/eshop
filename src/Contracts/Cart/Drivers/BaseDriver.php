<?php

namespace AdminEshop\Contracts\Cart\Drivers;

use AdminEshop\Contracts\Cart\Drivers\DriverInterface;

class BaseDriver
{
    protected $temporaryData = [];

    /**
     * Set temporary data
     *
     * @param  string  $key
     *
     * @param  mixed  $value
     */
    public function setTemporary($key, $value)
    {
        $this->temporaryData[$key] = $value;

        return $this;
    }

    /**
     * Check if driver has temporary data
     *
     * @param  string  $key
     *
     * @return  bool
     */
    public function hasTemporary($key)
    {
        return array_key_exists($key, $this->temporaryData);
    }

    /**
     * Get temporary data
     *
     * @param  string  $key
     *
     * @return  bool
     */
    public function getTemporary($key)
    {
        return $this->temporaryData[$key];
    }

    /**
     * Remove temporary data
     *
     * @param  string  $key
     * @return  this
     */
    public function removeTemporary($key)
    {
        if ( $this->hasTemporary($key) ) {
            unset($this->temporaryData[$key]);
        }

        return $this;
    }
}