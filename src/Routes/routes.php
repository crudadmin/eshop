<?php

Route::group([ 'namespace' => 'AdminEshop\Controllers', 'middleware' => 'web' ], function(){
    Route::get('/store/b2b/{value}', 'Store\StoreController@setB2B')->name('store::setB2B');

    Route::post('/cart/add', 'Cart\CartController@addItem')->name('cart::addItem');
    Route::post('/cart/remove', 'Cart\CartController@removeItem')->name('cart::removeItem');
    Route::post('/cart/updateQuantity', 'Cart\CartController@updateQuantity')->name('cart::updateQuantity');
    Route::post('/cart/addDiscountCode', 'Cart\CartController@addDiscountCode')->name('cart::addDiscountCode');
    Route::post('/cart/removeDiscountCode', 'Cart\CartController@removeDiscountCode')->name('cart::removeDiscountCode');
    Route::post('/cart/setDelivery', 'Cart\CartController@setDelivery')->name('cart::setDelivery');
    Route::post('/cart/setPaymentMethod', 'Cart\CartController@setPaymentMethod')->name('cart::setPaymentMethod');

    Route::get('/api/payments/gopay/{payment}/{type}/{hash}', 'Payments\GopayController@paymentStatus');
});
?>