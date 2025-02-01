<?php

namespace Tests\Unit\Domains\Contact\ManageReminders\Services;

use App\Domains\Contact\ManageReminders\Services\ScheduleContactReminderForUser;
use App\Models\Contact;
use App\Models\ContactReminder;
use App\Models\User;
use App\Models\UserNotificationChannel;
use App\Models\Vault;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ScheduleContactReminderForUserTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_schedules_a_reminder_of_date_in_the_past_in_utc(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));

        $regis = $this->createUser();
        $regis->timezone = 'UTC';
        $regis->save();

        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $contactReminder = ContactReminder::factory()->create([
            'contact_id' => $contact->id,
            'type' => ContactReminder::TYPE_ONE_TIME,
            'day' => 2,
            'month' => 10,
            'year' => 2000,
        ]);
        $channel = UserNotificationChannel::factory()->create([
            'user_id' => $regis->id,
            'preferred_time' => '18:00',
        ]);

        $this->executeService($regis, $contactReminder, $channel, '2018-10-02 18:00:00');
    }

    /** @test */
    public function it_schedules_a_reminder_of_date_in_the_past_in_another_timezone(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));

        $regis = $this->createUser();
        $regis->timezone = 'America/New_York';
        $regis->save();

        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $contactReminder = ContactReminder::factory()->create([
            'contact_id' => $contact->id,
            'type' => ContactReminder::TYPE_ONE_TIME,
            'day' => 2,
            'month' => 10,
            'year' => 2000,
        ]);
        $channel = UserNotificationChannel::factory()->create([
            'user_id' => $regis->id,
            'preferred_time' => '18:00',
        ]);

        $this->executeService($regis, $contactReminder, $channel, '2018-10-02 22:00:00');
    }

    /** @test */
    public function it_schedules_a_reminder_of_date_in_the_past_in_another_timezone_again(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));

        $regis = $this->createUser();
        $regis->timezone = 'Asia/Sakhalin';
        $regis->save();

        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $contactReminder = ContactReminder::factory()->create([
            'contact_id' => $contact->id,
            'type' => ContactReminder::TYPE_ONE_TIME,
            'day' => 2,
            'month' => 10,
            'year' => 2000,
        ]);
        $channel = UserNotificationChannel::factory()->create([
            'user_id' => $regis->id,
            'preferred_time' => '18:00',
        ]);

        $this->executeService($regis, $contactReminder, $channel, '2018-10-02 07:00:00');
    }

    /** @test */
    public function it_schedules_a_reminder_of_date_in_the_future_in_utc(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));

        $regis = $this->createUser();
        $regis->timezone = 'Asia/Sakhalin';
        $regis->save();

        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);

        $contactReminder = ContactReminder::factory()->create([
            'contact_id' => $contact->id,
            'type' => ContactReminder::TYPE_ONE_TIME,
            'day' => 2,
            'month' => 10,
            'year' => 2200,
        ]);
        $channel = UserNotificationChannel::factory()->create([
            'user_id' => $regis->id,
            'preferred_time' => '18:00',
        ]);

        $this->executeService($regis, $contactReminder, $channel, '2200-10-02 07:00:00');
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new ScheduleContactReminderForUser)->execute($request);
    }

    private function executeService(User $user, ContactReminder $reminder, UserNotificationChannel $channel, string $expectedDate): void
    {
        $request = [
            'contact_reminder_id' => $reminder->id,
            'user_id' => $user->id,
        ];

        (new ScheduleContactReminderForUser)->execute($request);

        $this->assertDatabaseHas('contact_reminder_scheduled', [
            'user_notification_channel_id' => $channel->id,
            'contact_reminder_id' => $reminder->id,
            'scheduled_at' => $expectedDate,
        ]);
    }
}
