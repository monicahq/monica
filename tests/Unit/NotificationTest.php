<?php

namespace Tests\Unit;

use App\Account;
use Tests\TestCase;
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
}
