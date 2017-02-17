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
}
