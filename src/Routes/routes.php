<?php

if ( config('admin_eshop.routes.enabled.products') === true ) {
    Store::routesForProducts();
}

if ( config('admin_eshop.routes.enabled.discounts') === true ) {
    Store::routesForDiscounts();
}

if ( config('admin_eshop.routes.enabled.cart') === true ) {
    Store::routesForCart();
}

if ( config('admin_eshop.routes.enabled.cart_submit') === true ) {
    Store::routesForCartSubmit();
}

if ( config('admin_eshop.routes.enabled.cart_payments') === true ) {
    Store::routesForPayments();
}

Store::routesForFeeds();

Route::group([ 'namespace' => '\AdminEshop\Controllers', 'middleware' => ['web', 'admin'] ], function(){
    Route::get('/admin/orders/{id}/items', 'AdminController@getOrderItems');
});
?>