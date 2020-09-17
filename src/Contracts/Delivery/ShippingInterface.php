<?php

namespace AdminEshop\Contracts\Delivery;

use AdminEshop\Contracts\Delivery\ShippingResponse;

interface ShippingInterface
{
    /**
     * Creates package and returns shipping response with all neccessary data
     *
     * @return  AdminEshop\Contracts\Delivery\ShippingResponse
     */
    public function createPackage() : ShippingResponse;
}