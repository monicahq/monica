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

// @codeCoverageIgnoreStart
Artisan::command('memcached:stats', function () {
    if (config('cache.default') === 'memcached') {
        /** @var \Illuminate\Console\Command */
        /** @psalm-suppress InvalidScope */
        $command = $this;
        $hostStats = Cache::getMemcached()->getStats();
        foreach ($hostStats as $host => $stats) {
            $command->line('Host: '.$host);
            foreach ($stats as $key => $value) {
                $command->line($key.': '.$value);
            }
            $command->line('');
        }
    }
})->purpose('Display memcached statistics');
// @codeCoverageIgnoreEnd
