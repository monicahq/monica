<?php

namespace Tests\Unit;

use App\User;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UserTest extends TestCase
{
    use DatabaseTransactions;

    public function testUpdateContactViewPreference()
    {
        $user = new User;
        $user->contacts_sort_order = 'firstnameAZ';

        $this->assertEquals(
          $user->contacts_sort_order == 'lastnameAZ',
          $user->updateContactViewPreference('lastnameAZ')
        );
    }

    public function test_name_accessor_returns_name_in_the_user_preferred_way()
    {
        $user = new User;
        $user->first_name = 'John';
        $user->last_name = 'Doe';
        $user->name_order = 'firstname_first';

        $this->assertEquals(
          $user->name, 'John Doe'
        );

        $user->name_order = 'lastname_first';

        $this->assertEquals(
          $user->name, 'Doe John'
        );
    }
}
