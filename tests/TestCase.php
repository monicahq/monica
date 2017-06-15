<?php

namespace Tests;

use Faker\Factory;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public $faker;

    /**
     * TestCase constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // Makes a Faker Factory available to all tests.
        $this->faker = Factory::create();
    }
}
