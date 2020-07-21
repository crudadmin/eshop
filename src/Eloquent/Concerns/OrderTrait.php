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
        //Set order into discounts factory
        OrderService::setOrder($this);

        $items = (new OrderItemsCollection($this->items))
                    ->fetchModels()
                    ->addOriginalObjects()
                    ->rewritePricesInModels()
                    ->applyOnOrderCart();

        OrderService::rebuildOrder($items);

        $this->save();

        //We want sync discounts in every other items in order
        $this->syncOrderItemsWithCartDiscounts($items, $mutatingItem);

        //Remove and add again all discounts
        $this->items()->where('identifier', 'discount')->delete();

        OrderService::addDiscountableItemsIntoOrder();
    }

    /**
     * We need update prices of every other item in order
     * because discoints may change prices in every order item
     *
     * @param  AdminEshop\Contracts\Collections\CartCollection  $items
     * @param  OrdersItem|null  $mutatingItem If is beign update state, we need update this item for correct rendering in table
     * @return  void
     */
    public function syncOrderItemsWithCartDiscounts($items, OrdersItem $mutatingItem = null)
    {
        foreach ($items as $item) {
            //If order item has setted manual price,
            //we does not want to modify this item.
            if ( $item->hasManualPrice == true ) {
                continue;
            }

            //Receive item model of given OrderItem
            //If identifier is missing, OrderItem itself will be returned
            $itemModel = $item->getItemModel();

            $hasChanges = false;

            //If price without vat has been changed
            if ( $item->price != $itemModel->priceWithoutVat ) {
                $item->price = $itemModel->priceWithoutVat;
                $hasChanges = true;
            }

            //If price with vat has been changed
            if ( $item->price_vat != $itemModel->priceWithVat ) {
                $item->price_vat = $itemModel->priceWithVat;
                $hasChanges = true;
            }

            //Save item changes
            if ( $hasChanges ) {
                $item->save();
            }

            //We want modify original mutating item, not his clone
            if ( $mutatingItem && $item->getKey() === $mutatingItem->getKey() ) {
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

    public function makeInvoice($type = null)
    {
        $data = array_merge($this->toArray(), [
            'order_id' => $this->getKey(),
            'company_name' => $this->company_name ?: $this->username,
            'city' => $this->city,
            'street' => $this->street,
            'zipcode' => $this->zipcode,

            'delivery_company_name' => $this->delivery_username ?: $this->company_name ?: $this->username,
            'delivery_city' => $this->delivery_city ?: $this->city,
            'delivery_street' => $this->delivery_street ?: $this->street,
            'delivery_zipcode' => $this->delivery_zipcode ?: $this->zipcode,
            'delivery_country_id' => $this->delivery_country_id ?: $this->country_id,

            'note' => $this->note,
            'price' => $this->price,
            'price_vat' => $this->price_vat,
            'payment_method_id' => $this->payment_method_id,
            'vs' => $this->number,
            'payment_date' => $this->created_at->addDays(getInvoiceSettings()->payment_term),
            'country' => 'sk',
        ]);

        //If is creating invoice, and order has proform
        if (
            $type == 'invoice'
            && $proform = $this->invoices()->where('type', 'proform')->select(['id'])->first()
        ) {
            $data['proform_id'] = $proform->getKey();
        }

        //Remove uneccessary columns from invoice
        foreach (['deleted_at', 'created_at', 'updated_at', 'client_id'] as $column) {
            if ( array_key_exists($column, $data) )
                unset($data[$column]);
        }

        //If invoice exists, regenerate it.
        if ( $invoice = $this->invoices()->where('type', $type)->first() ){
            //Delete invoice items for invoice regeneration
            $invoice->items()->forceDelete();

            $invoice->update($data);
        }

        //If invoice does not exists
        else {
            $invoice = invoice()->make($type, $data);
            $invoice->save();
        }

        $this->addMissingInvoiceOrderItems([], $invoice);

        return $invoice;
    }

    public function addMissingInvoiceOrderItems($items, $invoice)
    {
        //Add order items
        foreach ($this->items as $item) {
            $invoice->items()->create([
                'name' => $item->productName,
                'quantity' => $item->quantity,
                'vat' => $item->vat,
                'price' => $item->price,
                'price_vat' => $item->price_vat
            ]);
        }

        //Add delivery item
        if ( $this->delivery ) {
            $invoice->items()->create([
                'name' => $this->delivery->name,
                'quantity' => 1,
                'vat' => $this->delivery_vat,
                'price' => $this->delivery_price,
                'price_vat' => $this->deliveryPriceWithVat,
            ]);
        }

        //Add payment method
        if ( $this->payment_method ) {
            $invoice->items()->create([
                'name' => $this->payment_method->name,
                'quantity' => 1,
                'vat' => $this->payment_method_vat,
                'price' => $this->payment_method_price,
                'price_vat' => $this->paymentMethodPriceWithVat,
            ]);
        }
    }
}

?>