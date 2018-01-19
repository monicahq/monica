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
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        // Makes a Faker Factory available to all tests.
        $this->faker = Factory::create();
    }

    /**
     * Create a user and sign in as that user. If a user
     * object is passed, then sign in as that user.
     *
     * @param null $user
     * @return mixed
     */
    public function signIn($user = null)
    {
        if (is_null($user)) {
            $user = factory('App\User')->create();
        }

        $this->be($user);

        return $user;
    }
}
