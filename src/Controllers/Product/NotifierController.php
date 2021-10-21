<?php

namespace AdminEshop\Controllers\Product;

use AdminEshop\Controllers\Controller;
use AdminEshop\Models\Products\ProductsNotification;
use Admin;

class NotifierController extends Controller
{
    public function notifyOnStock()
    {
        $validator = (new ProductsNotification)->getNotifierValidator()->validate();

        $data = $validator->getData();

        $row = [
            'email' => $data['email']
        ];

        if ( isset($data['variant_id']) ) {
            $product = Admin::getModel('ProductsVariant')->findOrFail($data['variant_id']);

            $row['product_id'] = $product->product_id;
        } else {
            $product = Admin::getModel('Product')->findOrFail($data['product_id']);
        }

        //If email is not registred yet
        if ( $product->notifications()->where('email', $data['email'])->where('notified', 0)->count() == 0 ) {
            $product->notifications()->create($row);
        }

        return autoAjax()->save(_('Ďakujeme! Hneď ako produkt naskladníme Vás budeme informovať.'));
    }
}
