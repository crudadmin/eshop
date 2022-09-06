<?php

namespace AdminEshop\Contracts\Cart\Drivers;

use AdminEshop\Contracts\Cart\Drivers\BaseDriver;
use AdminEshop\Contracts\Cart\Drivers\DriverInterface;
use Arr;

class MySqlDriver extends BaseDriver implements DriverInterface
{
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
    public function __construct(array $initialData = [])
    {
        //Create and save cart session with default initial data
        $this->getCartSession($initialData);
    }

    /**
     * Return cart row model row
     *
     * @param array|null $initialData
     *
     * @return  CartSesion||null
     */
    public function getCartSession($initialData = [])
    {
        if ( $this->cartRow ){
            return $this->cartRow;
        }

        $model = $this->getCartTokenModel();

        if ( $cartToken = $this->getToken() ) {
            //Fetch or create new token row
            if ( !($cartRow = $model->where('token', $cartToken)->first()) ){
                $cartRow = $model->fill([
                    'token' => $cartToken,
                    'data' => $initialData ?: [],
                ])->setClientIfEmpty();

                $cartRow->save();
            } else {
                $cartRow->setClientIfEmpty(true);
            }
        }

        //If token has not been sent, we create anonymous cart token
        else {
            $cartRow = $model->newInstance([
                'data' => $initialData
            ]);
        }

        return $this->cartRow = $cartRow;
    }

    /**
     * Set data into cart session
     *
     * @param  string|null  $key
     * @param  mixed  $value
     */
    public function set($key, $value)
    {
        //Merge existing data with new data set
        $data = $this->getCartSession()->data ?: [];

        //If empty values has been given, we want remove key
        if ( $value === null && array_key_exists($key, $data) ) {
            unset($data[$key]);
        } else {
            Arr::set($data, $key, $value);
        }

        $this->getCartSession()->setClientIfEmpty()->update([ 'data' => $data ]);

        return $this;
    }

    /**
     * Replace all data
     *
     * @param  array  $data
     *
     * @return  this
     */
    public function replace(array $data)
    {
        $this->getCartSession()->update([ 'data' => $data ]);

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
}