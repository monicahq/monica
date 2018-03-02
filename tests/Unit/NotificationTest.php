<?php

namespace Tests\Unit;

use App\Account;
use Tests\TestCase;
use App\Notification;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class NotificationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_belongs_to_account()
    {
        $account = factory(Account::class)->create([]);
        $notification = factory('App\Notification')->create(['account_id' => $account->id]);

        $this->assertTrue($notification->account()->exists());
    }

    public function test_it_belongs_to_contact()
    {
        $account = factory(Account::class)->create([]);
        $contact = factory('App\Contact')->create(['account_id' => $account->id]);
        $notification = factory('App\Notification')->create([
            'account_id' => $account->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertTrue($notification->contact()->exists());
    }

    public function test_it_belongs_to_reminder()
    {
        $account = factory(Account::class)->create([]);
        $reminder = factory('App\Reminder')->create(['account_id' => $account->id]);
        $notification = factory('App\Notification')->create([
            'account_id' => $account->id,
            'reminder_id' => $reminder->id,
        ]);

        $this->assertTrue($notification->reminder()->exists());
    }

    public function test_it_indicates_how_many_emails_should_be_sent_before_a_notification_is_deleted()
    {
        $notification = new Notification;
        $this->assertEquals(0, $notification->delete_after_number_of_emails_sent);

        $notification->setNumberOfEmailsNeededForDeletion(3);
        $this->assertEquals(3, $notification->delete_after_number_of_emails_sent);
    }

    public function test_it_deletes_a_notification_if_all_emails_have_been_sent()
    {
        $account = factory(Account::class)->create([]);
        $reminder = factory('App\Reminder')->create(['account_id' => $account->id]);
        $notification = factory('App\Notification')->create([
            'account_id' => $account->id,
            'reminder_id' => $reminder->id,
        ]);

        $notification->delete_after_number_of_emails_sent = 3;
        $notification->number_of_emails_sent = 2;
        $notification->save();

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
        ]);

        $notification->incrementNumberOfEmailsSentAndCheckDeletioNStatus();

        $this->assertDatabaseMissing('notifications', [
            'id' => $notification->id,
        ]);
    }
}
