<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\ViewModels\Vaults\ReminderDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReminderDeliveryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_says_which_channels_a_notification_will_use(): void
    {
        $reminder = Reminder::factory()->create();
        $user = $this->userFor($reminder);

        $delivery = new ReminderDelivery(user: $user, reminder: $reminder)->get();

        $this->assertNotSame('', $delivery['channels']);
    }

    #[Test]
    public function it_says_what_the_notification_will_say(): void
    {
        $reminder = Reminder::factory()->create(['name' => 'Birthday, Michael Scott']);

        $delivery = new ReminderDelivery(user: $this->userFor($reminder), reminder: $reminder)->get();

        $this->assertStringContainsString('Birthday, Michael Scott', $delivery['says']);
    }

    #[Test]
    public function it_says_when_one_last_went_out(): void
    {
        $reminder = Reminder::factory()->create();
        $user = $this->userFor($reminder);
        $occurrence = ReminderOccurrence::factory()->notified()->create([
            'reminder_id' => $reminder->id,
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
        ]);

        Notification::factory()->create([
            'account_id' => $reminder->account_id,
            'user_id' => $user->id,
            'notifiable_type' => ReminderOccurrence::class,
            'notifiable_id' => $occurrence->id,
            'sent_at' => now()->subDay(),
        ]);

        $delivery = new ReminderDelivery(user: $user, reminder: $reminder)->get();

        $this->assertNotNull($delivery['lastSent']);
    }

    #[Test]
    public function it_says_a_reminder_has_never_reached_anybody(): void
    {
        $reminder = Reminder::factory()->create();

        $delivery = new ReminderDelivery(user: $this->userFor($reminder), reminder: $reminder)->get();

        $this->assertNull($delivery['lastSent']);
        $this->assertNotSame('', $delivery['lastSentNote']);
    }

    #[Test]
    public function it_ignores_a_notification_that_has_not_gone_out(): void
    {
        $reminder = Reminder::factory()->create();
        $user = $this->userFor($reminder);
        $occurrence = ReminderOccurrence::factory()->create([
            'reminder_id' => $reminder->id,
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
        ]);

        Notification::factory()->create([
            'account_id' => $reminder->account_id,
            'user_id' => $user->id,
            'notifiable_type' => ReminderOccurrence::class,
            'notifiable_id' => $occurrence->id,
            'sent_at' => null,
        ]);

        $this->assertNull(new ReminderDelivery(user: $user, reminder: $reminder)->get()['lastSent']);
    }

    #[Test]
    public function it_says_nothing_is_switched_on_when_nothing_is(): void
    {
        $reminder = Reminder::factory()->create();
        $user = $this->userFor($reminder);

        NotificationPreference::query()
            ->where('account_id', $reminder->account_id)
            ->update(['in_app_is_enabled' => false, 'email_is_enabled' => false, 'push_is_enabled' => false]);

        $delivery = new ReminderDelivery(user: $user, reminder: $reminder->refresh())->get();

        $this->assertNotSame('', $delivery['channels']);
        $this->assertTrue($delivery['isSilent']);
    }

    private function userFor(Reminder $reminder): User
    {
        return User::factory()->create(['account_id' => $reminder->account_id]);
    }
}
