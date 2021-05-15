<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('memcached:stats', function () {
    if (config('cache.default') === 'memcached') {
        $hostStats = Cache::getMemcached()->getStats();
        foreach ($hostStats as $host => $stats) {
            $this->line('Host: '.$host);
            foreach ($stats as $key => $value) {
                $this->line($key.': '.$value);
            }
            $this->line('');
        }
    }
})->purpose('Display memcached statistics');
