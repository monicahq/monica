<?php

namespace Tests\Unit;

use App\User;
use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UserTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_account()
    {
        $account = factory('App\Account')->create([]);
        $user = factory('App\User')->create(['account_id' => $account->id]);

        $this->assertTrue($user->account()->exists());
    }

    public function test_it_belongs_to_many_changelogs()
    {
        $account = factory('App\Account')->create([]);
        $user = factory('App\User')->create(['account_id' => $account->id]);
        $changelog = factory('App\Changelog')->create([]);
        $user->changelogs()->sync($changelog->id);

        $user = factory('App\User')->create(['account_id' => $account->id]);
        $changelog = factory('App\Changelog')->create([]);
        $user->changelogs()->sync($changelog->id);

        $this->assertTrue($user->changelogs()->exists());
    }

    public function testUpdateContactViewPreference()
    {
        $user = factory(User::class)->create();
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
        $account = factory('App\Account')->create([]);
        $user = factory('App\User')->create(['account_id' => $account->id]);

        $this->assertFalse($user->hasAlreadyRatedToday());
    }

    public function test_you_cant_vote_if_you_have_already_voted_today()
    {
        $account = factory('App\Account')->create([]);
        $user = factory('App\User')->create(['account_id' => $account->id]);
        $day = factory('App\Day')->create([
            'account_id' => $account->id,
            'date' => now(),
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

    public function test_user_should_not_be_reminded_because_dates_are_different()
    {
        Carbon::setTestNow(Carbon::create(2017, 1, 1));
        $account = factory('App\Account')->create();
        $user = factory('App\User')->create(['account_id' => $account->id]);
        $reminder = factory('App\Reminder')->create(['account_id' => $account->id, 'next_expected_date' => '2018-02-01']);

        $this->assertFalse($user->shouldBeReminded($reminder->next_expected_date));
    }

    public function test_user_should_not_be_reminded_because_hours_are_different()
    {
        Carbon::setTestNow(Carbon::create(2017, 1, 1, 7, 0, 0));
        $account = factory('App\Account')->create(['default_time_reminder_is_sent' => '08:00']);
        $user = factory('App\User')->create(['account_id' => $account->id]);
        $reminder = factory('App\Reminder')->create(['account_id' => $account->id, 'next_expected_date' => '2017-01-01']);

        $this->assertFalse($user->shouldBeReminded($reminder->next_expected_date));
    }

    public function test_user_should_not_be_reminded_because_timezone_is_different()
    {
        Carbon::setTestNow(Carbon::create(2017, 1, 1, 7, 0, 0, 'Europe/London'));
        $account = factory('App\Account')->create(['default_time_reminder_is_sent' => '07:00']);
        $user = factory('App\User')->create(['account_id' => $account->id]);
        $reminder = factory('App\Reminder')->create(['account_id' => $account->id, 'next_expected_date' => '2017-01-01']);

        $this->assertFalse($user->shouldBeReminded($reminder->next_expected_date));
    }

    public function test_user_should_be_reminded()
    {
        Carbon::setTestNow(Carbon::create(2017, 1, 1, 17, 32, 12));
        $account = factory('App\Account')->create(['default_time_reminder_is_sent' => '17:00']);
        $user = factory('App\User')->create(['account_id' => $account->id]);
        $reminder = factory('App\Reminder')->create(['account_id' => $account->id, 'next_expected_date' => '2017-01-01']);

        $this->assertTrue($user->shouldBeReminded($reminder->next_expected_date));
    }

    public function test_it_marks_all_changelog_entries_as_read()
    {
        $account = factory('App\Account')->create([]);
        $user = factory('App\User')->create(['account_id' => $account->id]);
        $changelog = factory('App\Changelog')->create([]);
        $changelog->users()->sync($user->id);

        $this->assertDatabaseHas('changelog_user', [
            'user_id' => $user->id,
            'changelog_id' => $changelog->id,
            'read' => 0,
        ]);

        $user->markChangelogAsRead();

        $this->assertDatabaseHas('changelog_user', [
            'user_id' => $user->id,
            'changelog_id' => $changelog->id,
            'read' => 1,
        ]);
    }
}
