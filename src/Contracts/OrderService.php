<?php

namespace AdminEshop\Contracts;

use Cart;
use Store;

class OrderService
{
    /**
     * Clean order row
     *
     * @param  array  $row
     * @return  this
     */
    public function prepareOrderRow($oldRow)
    {
        $row = $this->cleanOrderRow($oldRow);
        $row = $this->addDelivery($row);
        $row = $this->addPaymentMethod($row);
        $row = $this->addOrderPrices($row);

        return $row;
    }

    /**
     * Add items from cart into order
     *
     * @param  AdminModel  $order
     */
    public function addItemsIntoOrder($order)
    {
        $cart = Cart::all();

        foreach ($cart as $item) {
            $product = (@$item->variant ?: $item->product);

            $order->items()->create([
                'product_id' => $item->id,
                'variant_id' => @$item->variant_id,
                'quantity' => @$item->quantity,
                'price' => @$product->priceWithoutTax,
                'tax' => Store::getTaxValueById($product->tax_id),
                'price_tax' => @$product->priceWithTax,
            ]);
        }
    }

    /**
     * Clean order row
     *
     * @param  array  $row
     * @return  $row
     */
    public function cleanOrderRow($row)
    {
        $row = $this->cleanDiferentDeliveryDetails($row);
        $row = $this->cleanCompanyDetails($row);

        return $row;
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
     * Reset different delivery fields
     * If different delivery is not present
     *
     * @param  array  $row
     * @return array
     */
    public function cleanDiferentDeliveryDetails($row)
    {
        if ( @$row['delivery_different'] != 1 ) {
            foreach ([
                'delivery_username', 'delivery_phone', 'delivery_street',
                'delivery_city', 'delivery_zipcode', 'delivery_city', 'delivery_country_id'
            ] as $key) {
                $row[$key] = null;
            }
        }

        return $row;
    }

    /**
     * Reset company fields
     * If company is not present
     *
     * @param  array  $row
     * @return array
     */
    public function cleanCompanyDetails($row)
    {
        if ( @$row['company_id'] != 1 ) {
            foreach (['company_name', 'company_id', 'company_tax_id', 'company_vat_id'] as $key) {
                $row[$key] = null;
            }
        }

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
}

?>