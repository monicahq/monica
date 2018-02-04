<?php

namespace Tests\Unit;

use App\User;
use App\Account;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UserTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_account()
    {
        $account = factory(Account::class)->create([]);
        $user = factory('App\User')->create(['account_id' => $account->id]);

        $this->assertTrue($user->account()->exists());
    }

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

    public function test_it_gets_the_right_metric_symbol()
    {
        $user = new User;
        $user->metric = 'fahrenheit';

        $this->assertEquals(
          'F', $user->getMetricSymbol()
        );

        $user->metric = 'celsius';
        $this->assertEquals(
          'C', $user->getMetricSymbol()
        );
    }

    public function test_you_can_vote_if_you_havent_voted_yet_today()
    {
        $account = factory(Account::class)->create([]);
        $user = factory('App\User')->create(['account_id' => $account->id]);

        $this->assertFalse($user->hasAlreadyRatedToday());
    }

    public function test_you_cant_vote_if_you_have_already_voted_today()
    {
        $account = factory(Account::class)->create([]);
        $user = factory('App\User')->create(['account_id' => $account->id]);
        $day = factory('App\Day')->create([
            'account_id' => $account->id,
            'date' => \Carbon\Carbon::now(),
        ]);

        $this->assertTrue($user->hasAlreadyRatedToday());
    }

    public function test_it_gets_2fa_secret_attribute()
    {
        $user = new User;

        $this->assertNull($user->getGoogle2faSecretAttribute(null));

        $string = 'pass1234';

        $this->assertEquals(
            $string,
            $user->getGoogle2faSecretAttribute(encrypt($string))
        );
    }

    public function test_it_gets_fluid_layout()
    {
        $user = new User;
        $user->fluid_container = true;

        $this->assertEquals(
            'container-fluid',
            $user->getFluidLayout()
        );

        $user->fluid_container = false;

        $this->assertEquals(
            'container',
            $user->getFluidLayout()
        );
    }

    public function test_it_gets_the_locale()
    {
        $user = new User;
        $user->locale = 'en';

        $this->assertEquals(
            'en',
            $user->locale
        );
    }
}
