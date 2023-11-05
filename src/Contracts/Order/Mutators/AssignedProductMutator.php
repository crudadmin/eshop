<?php

namespace AdminEshop\Contracts\Order\Mutators;

use AdminEshop\Contracts\CartItem;
use AdminEshop\Contracts\Collections\CartCollection;
use AdminEshop\Contracts\Order\Mutators\Mutator;
use App\Models\Product\Product;

class AssignedProductMutator extends Mutator
{
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
     * Mutation of cart response request
     *
     * @param  $response
     * @return  array
     */
    public function mutateCartResponse($response) : array
    {
        return array_merge($response, [
            // ...
        ]);
    }

    /**
     * Items will be added into cart
     *
     * @param  mixed  $box
     *
     * @return  AdminEshop\Contracts\Collections\CartCollection
     */
    // public function addHiddenCartItems($package) : CartCollection
    public function addCartItems($activeResponse) : CartCollection
    {
        $addItems = [];

        $this->getCartItems()->each(function($item) use (&$addItems) {
            if ( !($model = $item->getItemModel()) ){
                return;
            }

            if ( !($assignedProduct = $model->assignedProduct) ){
                return;
            }

            $cartItem = new CartItem(
                $assignedProduct->getIdentifier(),
                $item->quantity
            );

            $cartItem->setParentIdentifier(
                $item->getIdentifierClass()
            );

            $addItems[] = $cartItem;
        });

        return (new CartCollection($addItems))->each(function($item){
            $item->setDiscounts(false);
        });
    }
}

?>