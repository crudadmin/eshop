<?php

namespace AdminEshop\Contracts;

use AdminEshop\Contracts\Order\HasRequest;
use AdminEshop\Contracts\Order\HasSession;
use AdminEshop\Contracts\Order\HasValidation;
use Admin;
use Store;
use Cart;

class OrderService
{
    use HasRequest,
        HasSession,
        HasValidation;

    /**
     * Order row
     *
     * @var  Admin\Eloquent\AdminModel|null
     */
    protected $order;

    public function store()
    {
        $row = $this->buildOrderData();

        $this->order = Admin::getModel('Order')->create($row);
    }

    /**
     * Returns order row data
     *
     * @return  array
     */
    public function buildOrderData()
    {
        $row = $this->getRequestData();

        $row = $this->addDelivery($row);
        $row = $this->addPaymentMethod($row);
        $row = $this->addOrderPrices($row);

        return $row;
    }

    /**
     * Add items from cart into order
     *
     * @return this;
     */
    public function addItemsIntoOrder()
    {
        $cart = Cart::all();

        foreach ($cart as $item) {
            $product = (@$item->variant ?: $item->product);

            $this->order->items()->create([
                'product_id' => $item->id,
                'variant_id' => @$item->variant_id,
                'quantity' => @$item->quantity,
                'price' => @$product->priceWithoutTax,
                'tax' => Store::getTaxValueById($product->tax_id),
                'price_tax' => @$product->priceWithTax,
            ]);
        }

        $this->order->syncWarehouse();
    }

    /**
     * Add prices into order
     *
     * @param  array  $row
     * @return  array
     */
    public function addOrderPrices($row)
    {
        $summary = Cart::getSummary();

        $row['price'] = $summary['priceWithoutTax'];
        $row['price_tax'] = $summary['priceWithTax'];

        return $row;
    }

    /**
     * Add delivery field into order row
     *
     * @param  array  $row
     * @return array
     */
    public function addDelivery($row)
    {
        $delivery = Cart::getSelectedDelivery();

        $row['delivery_tax'] = Store::getTaxes()->where('id', $delivery->tax_id)->first()->tax;
        $row['delivery_price'] = $delivery->priceWithoutTax;
        $row['delivery_id'] = $delivery->getKey();

        return $row;
    }

    /**
     * Add delivery field into order row
     *
     * @param  array  $row
     * @return array
     */
    public function addPaymentMethod($row)
    {
        $paymentMethod = Cart::getSelectedPaymentMethod();

        $row['payment_method_tax'] = Store::getTaxes()->where('id', $paymentMethod->tax_id)->first()->tax;
        $row['payment_method_price'] = $paymentMethod->priceWithoutTax;
        $row['payment_method_id'] = $paymentMethod->getKey();

        return $row;
    }

    /**
     * Return
     *
     * @param
     * @return Response
     */
    public function errorResponse()
    {
        return response()->json([
            'orderErrors' => $this->getErrorMessages(),
            'cart' => Cart::fullCartResponse(),
        ], 422);
    }
}

?>