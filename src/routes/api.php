<?php
Route::group(['namespace' => 'Abs\StatusPkg\Api', 'middleware' => ['api']], function () {
	Route::group(['prefix' => 'status-pkg/api'], function () {
		Route::group(['middleware' => ['auth:api']], function () {
			// Route::get('taxes/get', 'TaxController@getTaxes');
		});
	});
});