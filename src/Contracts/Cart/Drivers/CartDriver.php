<?php

namespace AdminEshop\Contracts\Cart\Drivers;

use AdminEshop\Contracts\CartItem;
use AdminEshop\Contracts\Cart\Concerns\DriverSupport;
use AdminEshop\Contracts\Collections\CartCollection;

class CartDriver
{
    use DriverSupport;

    /**
     * We need set driver key for this class as global, withous scope
     *
     * @var  null|string
     */
    protected $driverKey = null;

    /**
     * Cart initialization data.
     * You can set initialization data via onCreate function
     *
        CartDriver::setInitialData(function(){
            return [
                'my_initial_data' => 123
            ];
        });
     *
     * @var  array
     */
    protected $onCreateData = [];

    /**
     * All registred classes which accessed driver data
     *
     * @var  array
     */
    protected $registredDrivers = [];

    /*
     * On cart create
     */
    public function setInitialData(callable $onCreate)
    {
        $this->onCreateData = $onCreate();
    }

    /**
     * Register driver class
     *
     * @param  object  $class
     * @return  void
     */
    public function registerDriverClass(object $class)
    {
        $this->registredDrivers[get_class($class)] = $class;
    }

    /*
     * Returns registerer driver classes
     */
    public function getRegistredDriverClasses()
    {
        return $this->registredDrivers;
    }

    /*
     * Returns creation data
     */
    public function getInitialData()
    {
        return $this->onCreateData;
    }

    /*
     * Flush all data from driver, except whitespaced
     */
    public function flushAllExceptWhitespaced()
    {
        //Retrieve all drivers classes, and remove non-whitespaced driver scopes
        $registredDriverClasses = $this->getRegistredDriverClasses();

        $data = $this->getDriver()->get();

        foreach ($registredDriverClasses as $class) {
            $driverKey = $class->getDriver()->getDriverKey();

            //Skip whitespaced driver classes
            if ( $class->flushOnComplete() === false || ! $driverKey ){
                continue;
            }

            //Remove exact root scope key
            if ( array_key_exists($driverKey, $data) ){
                unset($data[$driverKey]);
            }

            foreach ($data as $dataKey => $value) {
                //If key starts with level prefix myDriverClass.somethingelse
                if ( substr($dataKey, 0, strlen($driverKey) + 1) == $driverKey.'.' ){
                    unset($data[$dataKey]);
                }
            }

            $class->onDriverFlush();
        }

        $this->getDriver()->replace($data);
    }
}