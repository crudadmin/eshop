<?php

Route::group([ 'namespace' => 'AdminEshop\Controllers', 'middleware' => 'web' ], function(){
    Route::get('/store/b2b/{value}', 'Store\StoreController@setB2B')->name('store::setB2B');

    Route::post('/basket/add', 'Basket\BasketController@addItem')->name('basket::addItem');
    Route::post('/basket/remove', 'Basket\BasketController@removeItem')->name('basket::removeItem');
    Route::post('/basket/updateQuantity', 'Basket\BasketController@updateQuantity')->name('basket::updateQuantity');
    Route::post('/basket/addCode', 'Basket\BasketController@addDiscountCode')->name('basket::addDiscountCode');
});
?>