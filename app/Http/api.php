<?php

if (App::environment('production')) {
    URL::forceScheme('https');
}

$api = app('Dingo\Api\Routing\Router');

$api->version(['v1'], function ($api) {
  $api->post('/authenticate', "App\Http\Controllers\Api\AuthController@authenticate");
});

$api->version(['v1'],['middleware' => 'jwt.auth'], function ($api) {
  $api->get('/dashboard', 'App\Http\Controllers\Api\DashboardController@index');
  $api->get('/journal', 'App\Http\Controllers\Api\JournalController@index');
});

?>
