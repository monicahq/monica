<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
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
        $app = require __DIR__.'/../../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        App::setLocale('en');

        // set the bcrypt hashing rounds (the minimum allowed).
        // this reduces the amount of cycles needed to manage users.
        Hash::setRounds(4);

        return $app;
    }
}
