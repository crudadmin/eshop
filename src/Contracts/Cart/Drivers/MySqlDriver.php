<?php

namespace AdminEshop\Contracts\Cart\Drivers;

use AdminEshop\Contracts\Cart\Drivers\CartDriver;
use AdminEshop\Contracts\Cart\Drivers\DriverInterface;
use AdminEshop\Models\Store\CartToken;
use Arr;
use Str;

class MySqlDriver extends CartDriver implements DriverInterface
{
    /**
     * Session identifier for stored key
     *
     * @var  string
     */
    private $sessionIdentifierKey = 'cart_token';

    /**
     * CartRow
     *
     * @var  null|Admin\Core\Eloquent\AdminModel
     */
    private $cartRow = null;

    /**
     * On create session driver. We need define default params
     *
     * @return  void
     */
    public function onCreate(array $initialData = [])
    {
        //Create and save cart session with default initial data
        $this->getCartSession();
    }

    /*
     * Generate cart id
     */
    private function regenerateKey()
    {
        return Str::random(100);
    }

    /*
     * Get customer key
     */
    public function getToken()
    {
        //Return key based on session
        if ( config('admineshop.cart.session') == true ) {
            //If cart key does exists in session
            if ( session()->has($this->sessionIdentifierKey) === true ) {
                $key = session()->get($this->sessionIdentifierKey);
            }

            //Save cart key into session, for next request...
            else {
                session()->put($this->sessionIdentifierKey, $key = $this->regenerateKey());
                session()->save();
            }

            return $key;
        }

        //Return key based on REST API header
        return request()->header(config('admineshop.cart.header_token')) ?: $this->regenerateKey();
    }

    /**
     * Return cart row model row
     *
     * @return  CartSesion||null
     */
    public function getCartSession()
    {
        if ( $this->cartRow ){
            return $this->cartRow;
        }

        $cartKey = $this->getToken();

        if ( !($cartRow = CartToken::where('token', $cartKey)->first()) ){
            $cartRow = CartToken::create([
                'token' => $cartKey,
                'data' => $this->getInitialData() ?: [],
            ]);
        }

        return $this->cartRow = $cartRow;
    }

    /**
     * Set data into cart session
     *
     * @param  [type]  $key
     * @param  [type]  $value
     */
    public function set($key, $value)
    {
        //Merge existing data with new data set
        $data = array_merge($this->getCartSession()->data ?: [], [
            $key => $value,
        ]);

        //If empty values has been given, we want remove key
        if ( $value === null ) {
            unset($data[$key]);
        }

        $this->getCartSession()->update([ 'data' => $data ]);
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
        $data = (array)($this->getCartSession()->data ?: []);

        if ( ! $key ){
            return $data;
        }

        return Arr::get($data, $key) ?: $default;
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
        $this->set($key, null);
    }

    /**
     * Delete data from
     *
     * @return  void
     */
    public function destroy()
    {
        $this->getCartSession()->delete();

        if ( config('admineshop.cart.session') == true ) {
            session()->forget($this->sessionIdentifierKey);
            session()->save();
        }
    }
}