<?php

namespace AdminEshop\Contracts\Cart\Drivers;

use AdminEshop\Contracts\Cart\Drivers\DriverInterface;
use Str;

class BaseDriver
{
    protected $temporaryData = [];

    /**
     * Session identifier for stored key
     *
     * @var  string
     */
    const TOKEN_SESSION_KEY = 'cart_token';

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

    /*
     * Generate cart id
     */
    public function regenerateKey()
    {
        return Str::random(config('admineshop.cart.token.length', 12));
    }

    /*
     * Get customer key
     */
    public function getToken()
    {
        //Return key based on session
        if ( config('admineshop.cart.session') == true ) {
            //If cart key does exists in session
            if ( session()->has(self::TOKEN_SESSION_KEY) === true ) {
                $key = session()->get(self::TOKEN_SESSION_KEY);
            }

            //Save cart key into session, for next request...
            else {
                session()->put(self::TOKEN_SESSION_KEY, $key = $this->regenerateKey());
                session()->save();
            }

            return $key;
        }

        //Return key based on REST API header
        return request()->header(config('admineshop.cart.token.header_name', 'Cart-Token')) ?: $this->regenerateKey();
    }

    /**
     * Returns instance of actual cart session row
     *
     * @return  AdminModel|mixed
     */
    public function getCartSession()
    {

    }
}