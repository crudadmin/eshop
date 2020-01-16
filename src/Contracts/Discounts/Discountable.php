<?php

namespace AdminEshop\Contracts\Discounts;

use AdminEshop\Models\Orders\Order;

interface Discountable {
    /**
     * Returns if discount is active on website
     *
     * @return  bool
     */
    public function isActive();

    /**
     * Returns if discount is active in administration
     *
     * @return  bool
     */
    public function isActiveInAdmin(Order $order);
}

?>