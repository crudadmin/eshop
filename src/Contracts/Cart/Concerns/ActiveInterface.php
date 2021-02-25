<?php

namespace AdminEshop\Contracts\Cart\Concerns;

use AdminEshop\Models\Orders\Order;

interface ActiveInterface
{
    /**
     * Returns active state with response on website
     *
     * @return  bool
     */
    public function isActive();

    /**
     * Returns active state with response in administration
     *
     * @return  bool
     */
    public function isActiveInAdmin(Order $order);

    /**
     * Set if active response should be cached in database for further order editing
     *
     * @return  bool
     */
    public function isCachableResponse();
}