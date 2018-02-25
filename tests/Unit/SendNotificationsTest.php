<?php

use App\Account;
use \Illuminate\Foundation\Testing\DatabaseTransactions;
use \Tests\FeatureTestCase;
use \Carbon\Carbon;


class SendNotificationsTest extends FeatureTestCase
{
    use DatabaseTransactions;

    public function test_send_notification_command()
    {
        // Setting up mock objects
        $account = factory(\App\Account::class)->create([]);

        $user = factory(\App\User::class)->create([
            'account_id' => $account->id
        ]);

        $contact = factory(\App\Contact::class)->create([
            'account_id' => $account->id
        ]);

        $reminder = factory(\App\Reminder::class)->create([
            'next_expected_date' => Carbon::now()->subMinute(1),
            'contact_id' => $contact->id
        ]);

        $command = new \App\Console\Commands\SendNotifications();
        $command->mocked = true;
        $command->handle();

        // Account should exist
        $this->assertEquals(1, count(\App\Account::find($account->id)->users));

        // Two notifications should be sent if account has no limitations
        $this->assertEquals(2, count($command->dispatched));

        // Account now has limitations
        config(['monica.requires_subscription' => true]);
        $account = factory(Account::class)->make([
            'has_access_to_paid_version_for_free' => false,
        ]);

        $command = new \App\Console\Commands\SendNotifications();
        $command->mocked = true;
        $command->handle();

        $this->assertEquals(1, count($command->dispatched));
    }
}