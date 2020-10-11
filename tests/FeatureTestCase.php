<?php

namespace Tests;

use Tests\Traits\SignIn;
use Tests\Traits\Asserts;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class FeatureTestCase extends TestCase
{
    use SignIn,
        Asserts,
        DatabaseTransactions;
}
