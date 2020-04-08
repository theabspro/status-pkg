<?php

Route::group(['namespace' => 'Abs\StatusPkg', 'middleware' => ['web', 'auth'], 'prefix' => 'status-pkg'], function () {

	//STATUSS
	Route::get('/status/get-list', 'StatusController@getStatusList')->name('getStatusList');
	Route::get('/status/get-form-data', 'StatusController@getStatusFormData')->name('getStatusFormData');
	Route::post('/status/save', 'StatusController@saveStatus')->name('saveStatus');
	Route::get('/status/delete', 'StatusController@deleteStatus')->name('deleteStatus');
	Route::get('/status/get-filter-data', 'StatusController@getStatusFilterData')->name('getStatusFilterData');

});