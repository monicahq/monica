<?php

namespace Tests;

use App\Helpers\DateHelper;
use Illuminate\Support\Facades\App;
use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        App::setLocale('en');
        DateHelper::setLocale('en');

        return $app;
    }
}
