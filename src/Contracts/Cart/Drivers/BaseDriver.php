<?php

namespace AdminEshop\Contracts\Cart\Drivers;

use AdminEshop\Contracts\Cart\Drivers\DriverInterface;
use Str;
use Admin;

class BaseDriver
{
    protected $temporaryData = [];

    /**
     * Cart token
     *
     * @var  cart token
     */
    protected $token;

    /**
     * Session identifier for stored key
     *
     * @var  string
     */
    const TOKEN_SESSION_KEY = 'cart_token';

    /**
     * Initialize cart driver
     *
     * @param  string|null  $token
     * @param  array  $initialData
     */
    public function __construct($token, array $initialData = [])
    {
        $this->setToken($token);

        $this->boot($initialData);
    }

    /**
     * Boot driver
     *
     * @param  array  $initialData
     */
    public function boot(array $initialData = [])
    {
        //...
    }

    /**
     * Set cart token
     *
     * @param  string  $token
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

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
        //Custom token
        if ( $this->token ){
            return $this->token;
        }

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

        //Dost not generate token automatically if header is empty
        if ( request()->header(config('admineshop.cart.token.header_initializator')) ) {
            return $this->regenerateKey();
        }
    }

    /**
     * Returns instance of actual cart session row
     *
     * @return  AdminModel|mixed
     */
    public function getCartSession()
    {
    }

    /**
     * Returns cart token eloquent
     *
     * @return AdminModel
     */
    public function getCartTokenModel()
    {
        return Admin::getModel('CartToken');
    }
}