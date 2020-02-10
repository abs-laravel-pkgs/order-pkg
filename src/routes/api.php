<?php
Route::group(['namespace' => 'Abs\OrderPkg\Api', 'middleware' => ['api']], function () {
	Route::group(['prefix' => 'order-pkg/api'], function () {
		Route::group(['middleware' => ['auth:api']], function () {
			// Route::get('taxes/get', 'TaxController@getTaxes');
		});
	});
});