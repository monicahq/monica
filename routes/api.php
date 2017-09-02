<?php

Route::group(['middleware' => ['auth:api']], function () {
    Route::get('index', 'Api\\DefaultController@index');
});
