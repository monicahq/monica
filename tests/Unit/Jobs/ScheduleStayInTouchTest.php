<?php

namespace Tests\Unit\Jobs;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User\User;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\Mail;
use App\Notifications\StayInTouchEmail;
use Illuminate\Support\Facades\Notification;
use App\Jobs\StayInTouch\ScheduleStayInTouch;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ScheduleStayInTouchTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_dispatches_an_email()
    {
        Notification::fake();

        Carbon::setTestNow(Carbon::create(2017, 1, 1, 7, 0, 0, 'America/New_York'));

        $account = factory(Account::class)->create([
            'default_time_reminder_is_sent' => '07:00',
            'has_access_to_paid_version_for_free' => 1,
        ]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
            'stay_in_touch_trigger_date' => '2017-01-01 07:00:00',
            'stay_in_touch_frequency' => 5,
        ]);
        $user = factory(User::class)->create([
            'account_id' => $account->id,
            'email' => 'john@doe.com',
            'timezone' => 'America/New_York',
        ]);

        dispatch(new ScheduleStayInTouch($contact));

        Notification::assertSentTo($user, StayInTouchEmail::class,
            function ($notification, $channels) use ($contact) {
                return $channels[0] == 'mail'
                && $notification->assertSentFor($contact);
            }
        );

        $this->assertDatabaseHas('contacts', [
            'stay_in_touch_trigger_date' => '2017-01-06 07:00:00',
        ]);
    }

    public function test_it_doesnt_dispatches_an_email_if_free_account()
    {
        Notification::fake();

        Carbon::setTestNow(Carbon::create(2017, 1, 1, 7, 0, 0, 'America/New_York'));

        config(['monica.requires_subscription' => true]);

        $account = factory(Account::class)->create([
            'default_time_reminder_is_sent' => '07:00',
            'has_access_to_paid_version_for_free' => 0,
        ]);
        $contact = factory(Contact::class)->create([
            'account_id' => $account->id,
            'stay_in_touch_trigger_date' => '2017-01-01 07:00:00',
            'stay_in_touch_frequency' => 5,
        ]);
        $user = factory(User::class)->create([
            'account_id' => $account->id,
            'email' => 'john@doe.com',
            'timezone' => 'America/New_York',
        ]);

        dispatch(new ScheduleStayInTouch($contact));

        Notification::assertNotSentTo($user, StayInTouchEmail::class);

        $this->assertDatabaseHas('contacts', [
            'stay_in_touch_trigger_date' => '2017-01-01 07:00:00',
        ]);
    }
}
