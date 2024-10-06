<?php

namespace Tests\Unit\Domains\Settings\ManageNotificationChannels\Services;

use App\Domains\Settings\ManageNotificationChannels\Services\ScheduleAllContactRemindersForNotificationChannel;
use App\Models\Contact;
use App\Models\ContactReminder;
use App\Models\UserNotificationChannel;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ScheduleAllContactRemindersForNotificationChannelTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_schedules_reminders(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));

        $regis = $this->createUser();
        $regis->timezone = 'UTC';
        $regis->save();

        $vaultA = $this->createVault($regis->account);
        $vaultA = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vaultA);
        $contact = Contact::factory()->create(['vault_id' => $vaultA->id]);

        $contactReminderA = ContactReminder::factory()->create([
            'contact_id' => $contact->id,
            'type' => ContactReminder::TYPE_ONE_TIME,
            'day' => 2,
            'month' => 10,
            'year' => 2000,
        ]);

        $vaultB = $this->createVault($regis->account);
        $vaultB = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vaultB);
        $contact = Contact::factory()->create(['vault_id' => $vaultB->id]);

        $contactReminderB = ContactReminder::factory()->create([
            'contact_id' => $contact->id,
            'type' => ContactReminder::TYPE_ONE_TIME,
            'day' => 2,
            'month' => 10,
            'year' => 1090,
        ]);
        $channel = UserNotificationChannel::factory()->create([
            'user_id' => $regis->id,
            'preferred_time' => '18:00',
        ]);

        (new ScheduleAllContactRemindersForNotificationChannel)->execute([
            'account_id' => $regis->account_id,
            'author_id' => $regis->id,
            'user_notification_channel_id' => $channel->id,
        ]);

        $this->assertDatabaseHas('contact_reminder_scheduled', [
            'contact_reminder_id' => $contactReminderA->id,
            'user_notification_channel_id' => $channel->id,
            'scheduled_at' => '2018-10-02 18:00:00',
        ]);
        $this->assertDatabaseHas('contact_reminder_scheduled', [
            'contact_reminder_id' => $contactReminderB->id,
            'user_notification_channel_id' => $channel->id,
            'scheduled_at' => '2018-10-02 18:00:00',
        ]);
    }
}
