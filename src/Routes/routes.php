<?php

Route::group([ 'namespace' => 'AdminEshop\Controllers', 'middleware' => 'web' ], function(){
    Route::get('/store/b2b/{value}', 'Store\StoreController@setB2B')->name('basket::addItem');
    Route::post('/basket/add', 'Basket\BasketController@addItem')->name('basket::addItem');
});
?>