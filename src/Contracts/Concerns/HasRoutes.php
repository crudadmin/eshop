<?php

namespace AdminEshop\Contracts\Concerns;

use Route;

trait HasRoutes
{
    public function routesForProducts()
    {
        Route::group(['namespace' => '\AdminEshop\Controllers'], function(){
            Route::post('/cart/add', 'Cart\CartController@addItem')->name('cart::addItem')->visible();
            Route::post('/cart/toggle', 'Cart\CartController@toggleItems')->name('cart::toggleItems')->visible();
            Route::post('/cart/update', 'Cart\CartController@updateQuantity')->name('cart::updateQuantity')->visible();
            Route::post('/cart/remove', 'Cart\CartController@removeItem')->name('cart::removeItem')->visible();
        });
    }

    public function routesForDiscounts()
    {
        Route::group(['namespace' => '\AdminEshop\Controllers'], function(){
            Route::post('/cart/discount/add', 'Cart\CartController@addDiscountCode')->name('cart::addDiscountCode')->visible();
            Route::post('/cart/discount/remove', 'Cart\CartController@removeDiscountCode')->name('cart::removeDiscountCode')->visible();
        });
    }

    public function routesForCart()
    {
        Route::group(['namespace' => '\AdminEshop\Controllers'], function(){
            Route::get('/cart', 'Cart\CartController@getFullSummary')->name('cart::fullSummary')->visible();
            Route::get('/cart/delivery-locations/{id}', 'Cart\CartController@getDeliveryLocations')->name('cart::getDeliveryLocations')->visible();

            Route::post('/cart/setDelivery', 'Cart\CartController@setDelivery')->name('cart::setDelivery')->visible();
            Route::post('/cart/setPaymentMethod', 'Cart\CartController@setPaymentMethod')->name('cart::setPaymentMethod')->visible();
            Route::post('/cart/setCountry', 'Cart\CartController@setCountry')->name('cart::setCountry')->visible();
        });
    }

    public function routesForCartSubmit()
    {
        Route::group(['namespace' => '\AdminEshop\Controllers'], function(){
            Route::post('/cart/submit', 'Cart\CartController@submitOrder')->visible();
            Route::get('/cart/success/{id?}/{orderhash?}', 'Cart\CartController@success')->visible();
        });
    }

    public function routesForPayments()
    {
        Route::group(['namespace' => '\AdminEshop\Controllers\Payments'], function(){
            Route::get('/_store/payments/create/{payment}/{type}/{hash}', 'PaymentController@paymentStatus');
            Route::get('/_store/payments/post-payment/{order}/{hash}', 'PaymentController@postPayment');
        });

    }

    public function routesForPacketaShipping()
    {
        Route::group(['namespace' => '\AdminEshop\Controllers\Shipping'], function(){
            Route::post('/cart/shipping/packeta/point', 'PacketaController@setPoint')->visible();
        });
    }

    public function routesForProfileAddress()
    {
        Route::group(['namespace' => '\AdminEshop\Controllers'], function(){
            Route::get('/auth/addresses', 'Client\AddressController@get')->visible();
            Route::put('/auth/addresses', 'Client\AddressController@store')->visible();
            Route::post('/auth/addresses/{id}', 'Client\AddressController@update')->visible();
            Route::post('/auth/addresses/{id}/default', 'Client\AddressController@setDefault')->visible();
            Route::delete('/auth/addresses/{id}', 'Client\AddressController@delete')->visible();
        });
    }

    public function routesForProfileOrders()
    {
        Route::group(['namespace' => '\AdminEshop\Controllers'], function(){
            Route::get('/auth/orders', 'Order\OrderController@index')->visible();
            Route::get('/auth/orders/{id}', 'Order\OrderController@show')->visible();
        });
    }
}