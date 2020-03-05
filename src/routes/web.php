<?php

Route::group(['namespace' => 'Abs\OrderPkg', 'middleware' => ['web', 'auth'], 'prefix' => 'order-pkg'], function () {
	//FAQs
	Route::get('/orders/get-list', 'OrderController@getOrderList')->name('getOrderList');
	Route::get('/order/get-form-data', 'OrderController@getOrderFormData')->name('getOrderFormData');
	Route::post('/order/save', 'OrderController@saveOrder')->name('saveOrder');
	Route::get('/order/view', 'OrderController@viewOrderAdmin')->name('viewOrderAdmin');
	Route::get('/order/delete/{id}', 'OrderController@deleteOrder')->name('deleteOrder');

	Route::post('order/add-log', 'OrderController@addOrderLog')->name('addOrderLog');

});

Route::group(['namespace' => 'Abs\OrderPkg', 'middleware' => ['web'], 'prefix' => 'order-pkg'], function () {
	//FAQs
	Route::get('/orders/get', 'OrderController@getOrders')->name('getOrders');
});
