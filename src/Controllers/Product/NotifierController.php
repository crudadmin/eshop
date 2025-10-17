<?php

namespace AdminEshop\Controllers\Product;

use AdminEshop\Controllers\Controller;
use Localization;
use Admin;

class NotifierController extends Controller
{
    public function notifyOnStock()
    {
        $notificationsModel = Admin::getModel('ProductsNotification');
        $validator = $notificationsModel->getNotifierValidator()->validate();

        $data = $validator->getData();

        $row = [
            'email' => $data['email'],
            'language_id' => Localization::get()?->getKey(),
        ];

        if ( isset($data['variant_id']) ) {
            $product = Admin::getModel('ProductsVariant')->findOrFail($data['variant_id']);

            $row['variant_id'] = $data['variant_id'];
            $row['product_id'] = $product->product_id;
        } else {
            $product = Admin::getModel('Product')->findOrFail($data['product_id']);

            $row['product_id'] = $product->id;
        }

        //If email is not registred yet
        if ( $notificationsModel->where($row)->where('notified', 0)->count() == 0 ) {
            $notificationsModel->create($row);
        }

        return autoAjax()->save(_('Ďakujeme! Hneď ako produkt naskladníme Vás budeme informovať.'));
    }
}
