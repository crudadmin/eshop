<?php

namespace AdminEshop\Contracts\Concerns;

use Route;

trait HasRoutes
{
    public function routesForProducts()
    {
        Route::group(['namespace' => '\AdminEshop\Controllers\Cart'], function(){
            Route::post('/cart/add', 'CartController@addItem')->name('cart::addItem')->visible();
            Route::post('/cart/update', 'CartController@updateQuantity')->name('cart::updateQuantity')->visible();
            Route::post('/cart/remove', 'CartController@removeItem')->name('cart::removeItem')->visible();
        });
    }

    public function routesForDiscounts()
    {
        Route::group(['namespace' => '\AdminEshop\Controllers\Cart'], function(){
            Route::post('/cart/discount/add', 'CartController@addDiscountCode')->name('cart::addDiscountCode')->visible();
            Route::post('/cart/discount/remove', 'CartController@removeDiscountCode')->name('cart::removeDiscountCode')->visible();
        });
    }

    public function routesForCart()
    {
        Route::group(['namespace' => '\AdminEshop\Controllers\Cart'], function(){
            Route::get('/cart/fullSummary', 'CartController@getFullSummary')->name('cart::fullSummary')->visible();
            Route::get('/cart/delivery-locations/{id}', 'CartController@getDeliveryLocations')->name('cart::getDeliveryLocations')->visible();

            Route::post('/cart/setDelivery', 'CartController@setDelivery')->name('cart::setDelivery')->visible();
            Route::post('/cart/setPaymentMethod', 'CartController@setPaymentMethod')->name('cart::setPaymentMethod')->visible();
            Route::post('/cart/setCountry', 'CartController@setCountry')->name('cart::setCountry')->visible();
        });
    }

    public function routesForCartSubmit()
    {
        Route::group(['namespace' => '\AdminEshop\Controllers\Cart'], function(){
            Route::post('/cart/submit', 'CartController@submitOrder')->visible();
            Route::get('/cart/success/{id?}/{orderhash?}', 'CartController@success')->visible();
        });
    }

    public function routesForPayments()
    {
        Route::group(['namespace' => '\AdminEshop\Controllers\Payments'], function(){
            Route::get('/api/payments/gopay/{payment}/{type}/{hash}', 'GopayController@paymentStatus');
        });
    }
}