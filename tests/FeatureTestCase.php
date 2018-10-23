<?php

namespace Tests;

use Tests\Traits\SignIn;

class FeatureTestCase extends TestCase
{
    use SignIn;

    protected function getTablePrefix() {
        return \DB::connection()->getTablePrefix();
    }
}
