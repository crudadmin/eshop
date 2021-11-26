<?php

namespace AdminEshop\Eloquent\Concerns;

use Admin;
use AdminEshop\Contracts\Collections\OrderItemsCollection;
use AdminEshop\Contracts\Discounts\DiscountCode;
use AdminEshop\Models\Delivery\Delivery;
use AdminEshop\Models\Orders\OrdersItem;
use AdminEshop\Models\Store\PaymentsMethod;
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
                    ->setDiscountable()
                    ->addOriginalObjects()
                    ->rewritePricesInModels()
                    ->applyOnOrderCart();

        OrderService::buildOrder($items);

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
            if ( $item->hasManualPrice == true || $item->discountable == false ) {
                continue;
            }

            //Receive item model of given OrderItem
            //If identifier is missing, DefaultIdentifier will be initialized and OrderItem itself will be returned
            if ( !($itemModel = $item->getItemModel()) ) {
                continue;
            }

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
     * Count down products from order in stock counts
     *
     * @param  string  $type '-' or '+'
     * @return  void
     */
    public function syncStock($type, $message)
    {
        //Uncount quantity
        foreach ($this->items as $item) {
            //If is product without relationship, just relative item
            if ( !($product = $item->getProduct()) ) {
                continue;
            }

            $product->commitStockChange($type, $item->quantity, $this->getKey(), $message);
        }
    }

    public function getDeliveries()
    {
        return Admin::cache('order.options.deliveries', function(){
            return Delivery::leftJoin('vats', 'deliveries.vat_id', '=', 'vats.id')
                            ->select(array_filter([
                                'deliveries.id',
                                'deliveries.name',
                                'deliveries.price',
                                config('admineshop.delivery.multiple_locations.enabled') ? 'deliveries.multiple_locations' : null,
                                'vats.vat'
                            ]))
                            ->get();
        });
    }

    public function getPaymentMethods()
    {
        return Admin::cache('order.options.payment_methods', function(){
            return PaymentsMethod::leftJoin('vats', 'payments_methods.vat_id', '=', 'vats.id')
                            ->select([
                                'payments_methods.id',
                                'payments_methods.name',
                                'payments_methods.price',
                                'vats.vat'
                            ])
                            ->get();
        });
    }

    private function getClientName()
    {
        $clientName = str_limit(e(strip_tags($this->is_company ? $this->company_name : $this->username)), 20);

        if ( $this->client_id && $this->client ){
            return '<i class="fa fa-'.($this->client->isCompany ? 'building' : 'user').' mr-1" data-toggle="tooltip" title="Klient č. '.$this->client_id.' / '.$this->client->clientName.'"></i> '.$clientName;
        }

        return $clientName;
    }

    private function getDeliveryAddress()
    {
        $prefix = $this->delivery_different ? 'delivery_' : '';

        return implode(', ', array_filter([
            $this->{$prefix.'street'},
            $this->{$prefix.'city'},
            $this->{$prefix.'zipcode'},
            $this->{$prefix.'country'} ? $this->{$prefix.'country'}->name : null,
        ]));
    }

    protected function getDeliveryStatusText()
    {
        $color = '';
        $tooltip = '';

        if ( $this->delivery_status == 'ok' ){
            $color = 'green';
            $tooltip = 'Objednávka bola úspešne odoslaná do systému dopravnej služby.';
            $message = $this->getSelectOption('delivery_status');
        }

        else if ( $this->delivery_status == 'error' ){
            $color = 'red';
            $tooltip = 'Objednávka nebola odoslaná do systému dopravnej služby.';
            $message = $this->getSelectOption('delivery_status');
        }

        else {
            $tooltip = '';
            $message = $this->getSelectOption('delivery_status');
        }

        if ( $message ) {
            return '
            <span style="'.($color ? ('color: '.$color) : '' ).'">
                <span data-toggle="tooltip" title="'.e($tooltip).'">
                    '.e($message).'
                </span>
            </span>';
        }
    }

    public function getDeliveryTrackingUrlAttribute()
    {
        OrderService::setOrder($this);

        //If delivery provider is not set
        if ( !$this->delivery_identifier || !($provider = OrderService::getShippingProvider()) ){
            return;
        }

        return $provider->getTrackingUrl($this->delivery_identifier);
    }

    public function getFirstnameAttribute()
    {
        $names = explode(' ', $this->username);

        return $names[0] ?? null;
    }

    public function getLastnameAttribute()
    {
        $names = explode(' ', $this->username);

        if ( count($names) >= 2 ) {
            return end($names);
        }
    }

    public function getDeliveryFirstnameAttribute()
    {
        $names = explode(' ', $this->delivery_username);

        return $names[0] ?? null;
    }

    public function getDeliveryLastnameAttribute()
    {
        $names = explode(' ', $this->delivery_username);

        if ( count($names) >= 2 ) {
            return end($names);
        }
    }

    protected function getVatOptions()
    {
        return Store::getVats()->map(function($item){
            $item->vatValue = $item->vat.'%';
            return $item;
        })->pluck('vatValue', 'vat');
    }

    public function bootOrderIntoOrderService()
    {
        $order = OrderService::getOrder();

        //If order in payment helper is not set already
        if ( !$order || $order->getKey() != $this->getKey() ){
            OrderService::setOrder($this);
        }
    }

    public function getVerifiedCustomersItemsIds()
    {
        return $this->items->map(function($item){
            if ( ($product = $item->getProduct()) && method_exists($product, 'getHeurekaItemId') ) {
                return $product->getHeurekaItemId();
            }
        })->filter(function($item){
            return $item;
        })->toArray();
    }
}

?>