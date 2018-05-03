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
    'UNLOCK'
];

Illuminate\Routing\Router::$verbs = $verbs;
Route::match($verbs, '{path?}', 'CardDAV\\CardDAVController@init')->where('path', '(.)*')->middleware('auth.basic.once');
