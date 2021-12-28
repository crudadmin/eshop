<?php

namespace AdminEshop\Eloquent\Concerns;

use Carbon\Carbon;

trait HasOrderNumber
{
    public function scopeGetLastOrderNumber($query)
    {
        $query
            ->where('number_prefix', $this->getOrderNumberResetPrefix())
            ->latest('id');
    }

    /**
     * This prefix indicates when should be order number reseted and start counting from beggining
     *
     * @return  string
     */
    public function getOrderNumberResetPrefix()
    {
        $prefix = config('admineshop.cart.order.number.prefix', '');

        $date = $this->created_at ? $this->created_at : Carbon::now();

        return $prefix.$date->format('y');
    }

    /**
     * This data prefix is only to fill some data into order number. But does not affect number generation
     *
     * @return  string
     */
    public function getOrderNumberData()
    {
        $date = $this->created_at ? $this->created_at : Carbon::now();

        return $date->format('m');
    }

    /*
     * Generate invoice number increment
     */
    public function setOrderNumber()
    {
        //If number is already set
        if ( $this->getRawOriginal('number') ) {
            return $this;
        }

        $pad = config('admineshop.cart.order.number.length', 6);

        $lastOrder = $this->newQuery()->getLastOrderNumber()->first();

        $resetPrefix = $this->getOrderNumberResetPrefix();
        $fullPrexix = $resetPrefix.$this->getOrderNumberData();

        //Get last invoice increment
        $lastCount = ! $lastOrder ? 0 : (int)substr($lastOrder->getRawOriginal('number'), strlen($fullPrexix));

        //Set invoice ID
        $nextNumber = substr($lastCount + 1, -$pad);

        $this->number = $fullPrexix . str_pad($nextNumber, $pad, 0, STR_PAD_LEFT);
        $this->number_prefix = $resetPrefix;

        return $this;
    }
}