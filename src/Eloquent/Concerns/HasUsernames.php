<?php

namespace AdminEshop\Eloquent\Concerns;

use AdminEshop\Events\StockChanged;
use AdminEshop\Models\Products\Product;
use AdminEshop\Models\Products\ProductsStocksLog;
use AdminEshop\Models\Store\CartStockBlock;
use Store;
use Cart;

trait HasUsernames
{
    private function hasSplitedUsernames()
    {
        return config('admineshop.client.username_splitted', false) ? true : false;
    }

    /**
     * Basic username
     */
    public function getUsernameAttribute()
    {
        return $this->getDynamicUsername();
    }

    public function getFirstnameAttribute()
    {
        return $this->getDynamicFirstname();
    }

    public function getLastnameAttribute()
    {
        return $this->getDynamicLastname();
    }

    public function setUsernameAttribute($value)
    {
        $this->setDynamicNameParts($value);
    }

    public function setFirstnameAttribute($value)
    {
        $this->setDynamicUsername('firstname', $value);
    }

    public function setLastnameAttribute($value)
    {
        $this->setDynamicUsername('lastname', $value);
    }


    /**
     * Delivery username
     */
    public function getDeliveryUsernameAttribute()
    {
        return $this->getDynamicUsername('delivery_');
    }

    public function getDeliveryFirstnameAttribute()
    {
        return $this->getDynamicFirstname('delivery_');
    }

    public function getDeliveryLastnameAttribute()
    {
        return $this->getDynamicLastname('delivery_');
    }

    public function setDeliveryUsernameAttribute($value)
    {
        $this->setDynamicNameParts($value, 'delivery_');
    }

    public function setDeliveryFirstnameAttribute($value)
    {
        $this->setDynamicUsername('firstname', $value, 'delivery_');
    }

    public function setDeliveryLastnameAttribute($value)
    {
        $this->setDynamicUsername('lastname', $value, 'delivery_');
    }

    /**
     * Helpers
     *
     */
    protected function getDynamicUsername($prefix = '', $force = false)
    {
        if ( $this->hasSplitedUsernames() || $force === true ) {
            return implode(' ', array_filter([
                $this->{$prefix.'firstname'},
                $this->{$prefix.'lastname'}]
            ));
        }

        return $this->getEncryptedAttribute($prefix.'username');
    }

    protected function getDynamicFirstname($prefix = '')
    {
        $value = $this->getEncryptedAttribute($prefix.'firstname');

        if ( $this->hasSplitedUsernames() ){
            return $value;
        }

        $names = explode(' ', $this->{$prefix.'username'});

        return $names[0] ?? null;
    }

    protected function getDynamicLastname($prefix = '')
    {
        $value = $this->getEncryptedAttribute($prefix.'lastname');

        if ( $this->hasSplitedUsernames() ){
            return $value;
        }

        $names = explode(' ', $this->{$prefix.'username'});
        if ( count($names) >= 2 ) {
            return end($names);
        }
    }

    protected function setDynamicUsername($key, $value, $prefix = '')
    {
        $this->setEncryptedAttribute($prefix.$key, $value);

        $this->setEncryptedAttribute($prefix.'username', $this->getDynamicUsername($prefix));
    }

    protected function setDynamicNameParts($value, $prefix = '')
    {
        $this->attributes[$prefix.'username'] = $value;

        $this->attributes[$prefix.'firstname'] = $this->{$prefix.'firstname'};
        $this->attributes[$prefix.'lastname'] = $this->{$prefix.'lastname'};
    }
}