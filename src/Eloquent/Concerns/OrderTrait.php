<?php

namespace AdminEshop\Eloquent\Concerns;

use Admin;
use AdminEshop\Contracts\Collections\OrderItemsCollection;
use AdminEshop\Contracts\Discounts\DiscountCode;
use AdminEshop\Models\Orders\OrdersItem;
use Ajax;
use Cart;
use Discounts;
use OrderService;
use Store;

trait OrderTrait
{
    /**
     * Recalculate order price
     * when new order is created or items in order has been updated/removed...
     *
     * @return  void
     */
    public function calculatePrices(OrdersItem $mutatingItem = null)
    {
        $price = 0;
        $priceWithTax = 0;

        //Set order into discounts factory
        OrderService::setOrder($this);

        $items = (new OrderItemsCollection($this->items))
                    ->fetchModels()
                    ->rewritePricesInModels()
                    ->applyOnOrderCart();

        OrderService::rebuildOrder($items);

        $this->save();

        $this->syncOrderItemsWithCartDiscounts($items, $mutatingItem);
    }

    public function syncOrderItemsWithCartDiscounts($items, OrdersItem $mutatingItem = null)
    {
        foreach ($this->items as $item) {
            //If order item has setted manual price,
            //we does not want to modify this item.
            if ( $item->hasManualPrice ) {
                continue;
            }

            $hasChanges = false;

            //If price without tax has been changed
            if ( $item->price != $item->priceWithoutTax ) {
                $item->price = $item->priceWithoutTax;
                $hasChanges = true;
            }

            //If price with tax has been changed
            if ( $item->price_tax != $item->priceWithTax ) {
                $item->price_tax = $item->priceWithTax;
                $hasChanges = true;
            }

            //Save item changes
            if ( $hasChanges ) {
                $item->save();
            }

            //We want modify original mutating item, not his clone
            if ( $item->getKey() === $mutatingItem->getKey() ) {
                $this->cloneChangesIntoOriginalItem($item, $mutatingItem);
            }
        }
    }

    /**
     * We need clone changes into original items
     * because changed wont be applied in item comming to request
     *
     * @param  OrdersItem  $item
     * @param  OrdersItem  $mutatingItem
     *
     * @return  void
     */
    public function cloneChangesIntoOriginalItem(OrdersItem $item, OrdersItem $mutatingItem)
    {
        foreach ($item->getAttributes() as $key => $value) {
            $mutatingItem->{$key} = $value;
        }
    }

    /**
     * Count down products from order in warehouse counts
     *
     * @param  string  $type '-' or '+'
     * @return  void
     */
    public function syncWarehouse($type, $message)
    {
        //Uncount quantity
        foreach ($this->items as $item) {
            //If is product without relationship, just relative item
            if ( !($product = $item->getProduct()) ) {
                continue;
            }

            $product->commitWarehouseChange($type, $item->quantity, $this->getKey(), $message);
        }
    }
}

?>