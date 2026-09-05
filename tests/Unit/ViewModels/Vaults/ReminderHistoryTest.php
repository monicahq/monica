<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\NotificationChannelEnum;
use App\Enums\UserActionEnum;
use App\Models\Log;
use App\Models\Notification;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\ViewModels\Vaults\ReminderHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReminderHistoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reads_what_was_done_to_the_rule(): void
    {
        $reminder = Reminder::factory()->create();
        $user = $this->userFor($reminder);

        Log::factory()->create([
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
            'user_id' => $user->id,
            'action' => UserActionEnum::ReminderCreation->value,
            'parameters' => ['label' => 'Birthday', 'name' => 'Michael Scott'],
            'loggable_type' => Reminder::class,
            'loggable_id' => $reminder->id,
        ]);

        $entries = new ReminderHistory(user: $user, reminder: $reminder)->get();

        $this->assertCount(1, $entries);
        $this->assertSame('Asked to be reminded of Birthday on Michael Scott', $entries[0]['title']);
    }

    #[Test]
    public function it_reads_what_was_done_to_one_of_its_occurrences(): void
    {
        $reminder = Reminder::factory()->create();
        $user = $this->userFor($reminder);
        $occurrence = ReminderOccurrence::factory()->create([
            'reminder_id' => $reminder->id,
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
        ]);

        Log::factory()->create([
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
            'user_id' => $user->id,
            'action' => UserActionEnum::ReminderOccurrenceDismissal->value,
            'parameters' => ['name' => 'Birthday', 'date' => '12 May 2026'],
            'loggable_type' => ReminderOccurrence::class,
            'loggable_id' => $occurrence->id,
        ]);

        $entries = new ReminderHistory(user: $user, reminder: $reminder)->get();

        $this->assertCount(1, $entries);
        $this->assertSame('Dismissed the reminder Birthday of 12 May 2026', $entries[0]['title']);
    }

    #[Test]
    public function it_reads_the_notifications_that_actually_went_out(): void
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
            'channel' => NotificationChannelEnum::Email,
            'notifiable_type' => ReminderOccurrence::class,
            'notifiable_id' => $occurrence->id,
            'sent_at' => now()->subHour(),
        ]);

        $entries = new ReminderHistory(user: $user, reminder: $reminder)->get();

        $this->assertCount(1, $entries);
        $this->assertSame('Notification sent', $entries[0]['title']);
        $this->assertStringContainsString('email', $entries[0]['note']);
    }

    #[Test]
    public function it_leaves_out_a_notification_that_never_went_out(): void
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

        $this->assertSame([], new ReminderHistory(user: $user, reminder: $reminder)->get());
    }

    #[Test]
    public function it_puts_the_newest_first(): void
    {
        $reminder = Reminder::factory()->create();
        $user = $this->userFor($reminder);

        Log::factory()->create([
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
            'user_id' => $user->id,
            'action' => UserActionEnum::ReminderCreation->value,
            'parameters' => ['label' => 'Birthday', 'name' => 'Michael Scott'],
            'loggable_type' => Reminder::class,
            'loggable_id' => $reminder->id,
            'created_at' => now()->subYear(),
        ]);

        Log::factory()->create([
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
            'user_id' => $user->id,
            'action' => UserActionEnum::ReminderUpdate->value,
            'parameters' => ['label' => 'Birthday', 'name' => 'Michael Scott'],
            'loggable_type' => Reminder::class,
            'loggable_id' => $reminder->id,
            'created_at' => now()->subDay(),
        ]);

        $entries = new ReminderHistory(user: $user, reminder: $reminder)->get();

        $this->assertSame('Changed the reminder on Birthday on Michael Scott', $entries[0]['title']);
        $this->assertSame('Asked to be reminded of Birthday on Michael Scott', $entries[1]['title']);
    }

    #[Test]
    public function it_ignores_what_belongs_to_another_reminder(): void
    {
        $reminder = Reminder::factory()->create();
        $other = Reminder::factory()->create([
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
        ]);
        $user = $this->userFor($reminder);

        Log::factory()->create([
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
            'user_id' => $user->id,
            'action' => UserActionEnum::ReminderCreation->value,
            'parameters' => ['label' => 'Birthday', 'name' => 'Michael Scott'],
            'loggable_type' => Reminder::class,
            'loggable_id' => $other->id,
        ]);

        $this->assertSame([], new ReminderHistory(user: $user, reminder: $reminder)->get());
    }

    #[Test]
    public function it_has_nothing_to_say_about_a_reminder_nothing_has_happened_to(): void
    {
        $reminder = Reminder::factory()->create();

        $this->assertSame(
            [],
            new ReminderHistory(user: $this->userFor($reminder), reminder: $reminder)->get(),
        );
    }

    private function userFor(Reminder $reminder): User
    {
        return User::factory()->create(['account_id' => $reminder->account_id]);
    }
}
