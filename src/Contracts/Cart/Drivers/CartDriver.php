<?php

namespace AdminEshop\Contracts\Cart\Drivers;

use AdminEshop\Contracts\CartItem;
use AdminEshop\Contracts\Collections\CartCollection;

class CartDriver
{
    /**
     * Initial data for new cart session instance
     *
     * @var  array
     */
    protected $initialData = [];

    /**
     * Constructor
     *
     * @param  array  $initialData
     */
    public function __construct(array $initialData = [])
    {
        $this->initialData = $initialData;

        //Fire on create
        $this->onCreate($initialData);
    }

    /**
     * Returns initial data
     *
     * @return  array|null
     */
    public function getInitialData()
    {
        return $this->initialData ?: [];
    }
}