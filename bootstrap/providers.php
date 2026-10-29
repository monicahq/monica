<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\DavServiceProvider;
use App\Providers\TelescopeServiceProvider;

return [
    AppServiceProvider::class,
    DavServiceProvider::class,
    TelescopeServiceProvider::class,
];
