<?php

namespace AdminEshop\Contracts\Concerns;

use Route;

trait HasRoutes
{
    public function routesForBootstrap()
    {
        Route::group(['namespace' => '\AdminEshop\Controllers\Bootstrap'], function(){
            Route::any('/bootstrap', 'BootstrapController@index')->visible();
        });
    }

    public function routesForListing()
    {
        Route::group(['namespace' => '\AdminEshop\Controllers\Listing'], function(){
            Route::any('/listing/{category?}', 'ListingController@index')->visible();
        });
    }

    public function routesForProductDetail()
    {
        Route::group(['namespace' => '\AdminEshop\Controllers\Product'], function(){
            Route::any('/product/{slug?}', 'ProductController@show')->visible();
        });
    }

    public function routesForProducts()
    {
        Route::group(['namespace' => '\AdminEshop\Controllers'], function(){
            Route::post('/cart/add', 'Cart\CartController@addItem')->name('cart::addItem')->visible();
            Route::post('/cart/toggle', 'Cart\CartController@toggleItems')->name('cart::toggleItems')->visible();
            Route::post('/cart/update', 'Cart\CartController@updateQuantity')->name('cart::updateQuantity')->visible();
            Route::post('/cart/remove', 'Cart\CartController@removeItem')->name('cart::removeItem')->visible();
            Route::post('/cart/notify', 'Product\NotifierController@notifyOnStock')->name('cart::notifyOnStock')->visible();
        });
    }

    public function routesForDiscounts()
    {
        Route::group(['namespace' => '\AdminEshop\Controllers'], function(){
            Route::post('/cart/discount/add', 'Cart\DiscountController@addDiscountCode')->name('cart::addDiscountCode')->visible();
            Route::post('/cart/discount/remove', 'Cart\DiscountController@removeDiscountCode')->name('cart::removeDiscountCode')->visible();
        });
    }

    public function routesForCart()
    {
        Route::group(['namespace' => '\AdminEshop\Controllers'], function(){
            Route::get('/cart', 'Cart\CartController@getFullSummary')->name('cart::fullSummary')->visible();
            Route::any('/cart/validate/{type}', 'Cart\CartController@passesValidation')->name('cart::validate')->visible();
            Route::get('/cart/delivery-locations/{id}', 'Cart\CartController@getDeliveryLocations')->name('cart::getDeliveryLocations')->visible();

            Route::post('/cart/setDelivery', 'Cart\CartController@setDelivery')->name('cart::setDelivery')->visible();
            Route::post('/cart/setPaymentMethod', 'Cart\CartController@setPaymentMethod')->name('cart::setPaymentMethod')->visible();
            Route::post('/cart/setCountry', 'Cart\CartController@setCountry')->name('cart::setCountry')->visible();
        });
    }

    public function routesForCartSubmit()
    {
        Route::group(['namespace' => '\AdminEshop\Controllers'], function(){
            Route::post('/cart/account-exists', 'Cart\CartController@checkAccountExistance')->middleware('throttle:20')->visible();
            Route::post('/cart/address', 'Cart\CartController@storeAddress')->visible();
            Route::post('/cart/submit', 'Cart\CartController@submitOrder')->visible();
            Route::get('/cart/success/{id?}/{orderhash?}', 'Cart\CartController@success')->visible();
        });
    }

    public function routesForPayments()
    {
        Route::group(['namespace' => '\AdminEshop\Controllers\Payments'], function(){
            Route::get('/_store/payments/create/{payment}/{type}/{hash}', 'PaymentController@paymentStatus');
            Route::get('/_store/payments/post-payment/{order}/{hash}', 'PaymentController@postPayment');
            Route::any('/_store/payments/webhooks/{type}', 'PaymentController@webhooks');
        });

    }

    public function routesForFavourites()
    {
        Route::group(['namespace' => '\AdminEshop\Controllers\Client'], function(){
            Route::get('/auth/favourites', 'FavouriteController@index')->visible();
            Route::post('/auth/favourites', 'FavouriteController@toggleFavourite')->visible();
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

    public function routesForHeureka()
    {
        $this->routesForFeeds();
    }

    public function routesForFeeds()
    {
        Route::group(['namespace' => '\AdminEshop\Controllers'], function(){
            //Legaxy route
            Route::get('/_store/heureka/feed', 'Feed\FeedController@index');
            Route::get('/_store/feed/{feed}', 'Feed\FeedController@index');
        });
    }

    public function routesForSearch()
    {
        Route::group(['namespace' => '\AdminEshop\Controllers'], function(){
            Route::any('/search', 'SearchController@index')->visible();
        });
    }
}