<?php

namespace Tests;

use Tests\Traits\Asserts;
use Tests\Traits\ApiSignIn;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiTestCase extends TestCase
{
    use ApiSignIn,
        Asserts,
        DatabaseTransactions;
}
