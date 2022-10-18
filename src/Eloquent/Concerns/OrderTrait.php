<?php

namespace AdminEshop\Eloquent\Concerns;

use Admin;
use AdminEshop\Contracts\Collections\OrderItemsCollection;
use AdminEshop\Contracts\Discounts\DiscountCode;
use AdminEshop\Models\Orders\OrdersItem;
use AdminEshop\Models\Orders\OrdersStatus;
use Ajax;
use Cart;
use Discounts;
use Illuminate\Support\Facades\DB;
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
            return Admin::getModel('Delivery')
                            ->leftJoin('vats', 'deliveries.vat_id', '=', 'vats.id')
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
            return Admin::getModel('PaymentsMethod')->leftJoin('vats', 'payments_methods.vat_id', '=', 'vats.id')
                            ->select([
                                'payments_methods.id',
                                'payments_methods.name',
                                'payments_methods.price',
                                'vats.vat'
                            ])
                            ->get();
        });
    }

    protected function getClientName()
    {
        $clientName = str_limit(e(strip_tags($this->is_company ? $this->company_name : $this->username)), 20);

        if ( $this->client_id && $this->client ){
            return '<i class="fa fa-'.($this->client->isCompany ? 'building' : 'user').' mr-1" data-toggle="tooltip" title="'._('Klient č.').' '.$this->client_id.' / '.$this->client->clientName.'"></i> '.$clientName;
        }

        return $clientName;
    }

    protected function getPickupAddressWithName()
    {
        return implode(' - ', array_filter([$this->deliveryPickupName, $this->deliveryPickupAddress]));
    }

    protected function getDeliveryAddress()
    {
        if ( $pickupAddress = $this->getPickupAddressWithName() ){
            $address = $pickupAddress;
        } else {
            $prefix = $this->delivery_different ? 'delivery_' : '';

            $address = implode(', ', array_filter([
                e($this->{$prefix.'street'}),
                e($this->{$prefix.'city'}),
                e($this->{$prefix.'zipcode'}),
                e($this->{$prefix.'country'} ? $this->{$prefix.'country'}->name : null),
            ]));
        }

        return '<a href="https://maps.google.com/?q='.urlencode($address).'" target="_blank" data-toggle="tooltip" title="'.$address.'">'.str_limit($address, 20).'</a>';
    }

    protected function getStatusColumn()
    {
        if ( !$this->getField('status_id') ){
            return;
        }

        $color = $this->status?->color;

        return '
        <span style="'.($color ? ('color: '.$color) : '' ).'">
            '.e($this->status?->name).'
        </span>';
    }

    protected function getDeliveryStatusColumn()
    {
        $element = 'span';
        $color = '';
        $icon = '';
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

        if ( $trackingUrl = $this->deliveryTrackingUrl ){
            $element = 'a';
            $icon = '<i class="fa fa-binoculars mr-2 d-inline-block"></i>';
            $href = $trackingUrl;
        }

        if ( $message ) {
            return '
            <'.$element.' href="'.$trackingUrl.'" style="'.($color ? ('color: '.$color) : '' ).'" target="_blank">
                <span data-toggle="tooltip" title="'.e($tooltip).'">
                    '.$icon.e($message).'
                </span>
            </'.$element.'>';
        }
    }

    protected function getIsPaidStatusColumn()
    {
        $color = '';
        $tooltip = '';

        if ( $isPaid = $this->paid_at ){
            $color = 'green';
            $icon = '<i class="fa fa-check"></i>';
            $tooltip = 'Zaplatené '.$this->paid_at->format('d.m.Y H:i:s');
        }

        else {
            $color = 'red';
            $icon = '<i class="fa fa-times"></i>';
            $tooltip = 'Neuhradené';
        }

        // Yes/no is for sheet export
        return '
        <span style="'.($color ? ('color: '.$color) : '' ).'">
            <span data-toggle="tooltip" title="'.e($tooltip).'">
                '.$icon.' <div class="d-none">'.($isPaid ? 'Áno' : 'Nie').'</div>
            </span>
        </span>';
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
            if ( ($product = $item->getProduct()) && method_exists($product, 'getHeurekaItemIdAttribute') ) {
                return $product->heurekaItemId;
            }
        })->filter(function($item){
            return $item;
        })->toArray();
    }

    public function getFilterStates()
    {
        $states = [
            [
                'name' => _('Neupravená objednávka'),
                'color' => '#e3342f',
                'active' => function(){
                    return $this->created_at == $this->updated_at;
                },
                'query' => function($query){
                    return $query->whereRaw('created_at = updated_at');
                },
            ]
        ];

        foreach (OrdersStatus::get() as $status) {
            $states[] = [
                'name' => $status->name,
                'color' => $status->color,
                'active' => function() use ($status) {
                    return $this->status_id === $status->getKey();
                },
                'query' => function($query) use ($status) {
                    return $query->where('status_id', $status->getKey());
                },
            ];
        }

        return $states;
    }

    public function onRequiredStatusIdRelation($table, $schema, $builder)
    {
        if ( $schema->hasColumn($this->getTable(), 'status') == false ){
            return;
        }

        $statuses = OrdersStatus::whereNotNull('key')->get();

        foreach ($statuses as $status) {
            DB::table('orders')->whereIn('status', explode(',', $status->key))->update([
                'status_id' => $status->getKey(),
            ]);
        }
    }
}

?>