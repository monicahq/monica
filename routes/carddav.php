<?php

use Illuminate\Support\Facades\Route;

$verbs = [
    'GET',
    'HEAD',
    'POST',
    'PUT',
    'PATCH',
    'DELETE',
    'PROPFIND',
    'PROPPATCH',
    'MKCOL',
    'COPY',
    'MOVE',
    'LOCK',
    'UNLOCK',
    'OPTIONS',
    'REPORT',
];

Illuminate\Routing\Router::$verbs = $verbs;

Route::group(['middleware' => ['auth.tokenonbasic']], function () use ($verbs) {
    Route::match($verbs, '{path?}', 'CardDAVController@init')->where('path', '(.)*');
});
