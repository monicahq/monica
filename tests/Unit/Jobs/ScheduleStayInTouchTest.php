<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/



namespace Tests\Unit\Jobs;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User\User;
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use Illuminate\Support\Facades\Mail;
use App\Notifications\StayInTouchEmail;
use App\Jobs\StayInTouch\ScheduleStayInTouch;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class ScheduleStayInTouchTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_dispatches_an_email()
    {
        NotificationFacade::fake();

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

        NotificationFacade::assertSentTo($user, StayInTouchEmail::class,
            function ($notification, $channels) use ($contact) {
                return $channels[0] == 'mail'
                && $notification->assertSentFor($contact);
            }
        );

        $notifications = NotificationFacade::sent($user, StayInTouchEmail::class);
        $message = $notifications[0]->toMail($user);

        $this->assertArraySubset(['You asked to be reminded to stay in touch with John Doe every 5 days.'], $message->introLines);

        $this->assertDatabaseHas('contacts', [
            'stay_in_touch_trigger_date' => '2017-01-06 07:00:00',
        ]);
    }

    public function test_it_doesnt_dispatches_an_email_if_free_account()
    {
        NotificationFacade::fake();

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

        NotificationFacade::assertNotSentTo($user, StayInTouchEmail::class);
        NotificationFacade::assertNothingSent();

        $this->assertDatabaseHas('contacts', [
            'stay_in_touch_trigger_date' => '2017-01-01 07:00:00',
        ]);
    }
}
