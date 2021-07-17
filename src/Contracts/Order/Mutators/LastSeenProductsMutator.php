<?php

namespace AdminEshop\Contracts\Order\Mutators;

use Admin;
use AdminEshop\Contracts\Order\Mutators\Mutator;
use AdminEshop\Models\Orders\Order;
use AdminEshop\Models\Products\Product;
use Cart;

class LastSeenProductsMutator extends Mutator
{
    /*
     * driver key for delivery
     */
    const LAST_SEEN_KEY = 'last_seen_ids';

    /**
     * Returns if mutators is active
     * And sends state to other methods
     *
     * @return  bool
     */
    public function isActive()
    {
        return true;
    }

    /**
     * Returns if mutators is active in administration
     * And sends state to other methods
     *
     * @return  bool
     */
    public function isActiveInAdmin(Order $order)
    {
        return false;
    }

    /**
     * Save product as viewed into queue
     *
     * @param  Product  $product
     */
    public function setSeenProduct(Product $product)
    {
        $seen = $this->getSeenProductsIds();

        //Save last 10 viewed products
        $seen = array_slice(array_unique(array_merge([$product->getKey()], $seen)), 0, 15);

        $this->getDriver()->set(self::LAST_SEEN_KEY, $seen);

        return $this;
    }

    public function getSeenProductsIds()
    {
        return $this->getDriver()->get(self::LAST_SEEN_KEY, []);
    }
}

?>