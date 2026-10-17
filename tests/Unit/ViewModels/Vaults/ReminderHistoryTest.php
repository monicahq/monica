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
use Carbon\CarbonImmutable;
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
            'action' => UserActionEnum::ReminderOccurrenceForgotten->value,
            'parameters' => ['name' => 'Birthday', 'date' => '12 May 2026'],
            'loggable_type' => ReminderOccurrence::class,
            'loggable_id' => $occurrence->id,
        ]);

        $entries = new ReminderHistory(user: $user, reminder: $reminder)->get();

        $this->assertCount(1, $entries);
        $this->assertSame('Forgot Birthday on 12 May 2026', $entries[0]['title']);
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

    #[Test]
    public function it_names_whichever_channel_carried_it(): void
    {
        $reminder = Reminder::factory()->create();
        $user = $this->userFor($reminder);
        $occurrence = ReminderOccurrence::factory()->notified()->create([
            'reminder_id' => $reminder->id,
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
        ]);

        foreach (NotificationChannelEnum::cases() as $channel) {
            Notification::factory()->create([
                'account_id' => $reminder->account_id,
                'user_id' => $user->id,
                'channel' => $channel,
                'notifiable_type' => ReminderOccurrence::class,
                'notifiable_id' => $occurrence->id,
                'sent_at' => now()->subHour(),
            ]);
        }

        $notes = array_column(new ReminderHistory(user: $user, reminder: $reminder)->get(), 'note');

        $this->assertContains('It appeared in the application.', $notes);
        $this->assertContains('It went out by email.', $notes);
        $this->assertContains('It went out on Telegram.', $notes);
        $this->assertContains('It went out by text message.', $notes);
    }

    #[Test]
    public function it_still_says_somebody_was_told_once_the_per_channel_records_are_pruned(): void
    {
        $reminder = Reminder::factory()->create();
        $user = $this->userFor($reminder);
        ReminderOccurrence::factory()->notified()->create([
            'reminder_id' => $reminder->id,
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
            'notified_at' => now()->subMonths(13),
        ]);

        $entries = new ReminderHistory(user: $user, reminder: $reminder)->get();

        $this->assertCount(1, $entries);
        $this->assertSame('Notification sent', $entries[0]['title']);
        $this->assertSame('Which channel carried it is no longer kept.', $entries[0]['note']);
    }

    #[Test]
    public function it_says_that_once_rather_than_alongside_the_record_that_is_still_there(): void
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

        $notes = array_column(new ReminderHistory(user: $user, reminder: $reminder)->get(), 'note');

        $this->assertNotContains('Which channel carried it is no longer kept.', $notes);
    }

    #[Test]
    public function it_pages_the_merge_fifty_at_a_time_newest_first(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-28 12:00:00', 'UTC'));
        $reminder = Reminder::factory()->create(['timezone' => 'UTC']);
        $user = User::factory()->create(['account_id' => $reminder->account_id, 'date_format' => 'Y-m-d']);
        $moments = $this->oneHundredAndTwentyEventsAcrossTheThreeSources($reminder, $user);

        $first = new ReminderHistory(user: $user, reminder: $reminder, page: 1);
        $second = new ReminderHistory(user: $user, reminder: $reminder, page: 2);
        $third = new ReminderHistory(user: $user, reminder: $reminder, page: 3);

        $this->assertSame(array_slice($moments, 0, 50), $this->moments($first->get()));
        $this->assertTrue($first->hasMore());
        $this->assertTrue($second->hasMore());
        $this->assertSame(array_slice($moments, 100, 20), $this->moments($third->get()));
        $this->assertFalse($third->hasMore());
        $this->assertSame(
            $moments,
            [...$this->moments($first->get()), ...$this->moments($second->get()), ...$this->moments($third->get())],
        );
    }

    #[Test]
    public function it_offers_more_when_one_source_alone_fills_the_page(): void
    {
        $reminder = Reminder::factory()->create();
        $user = $this->userFor($reminder);

        Log::factory()->count(51)->create([
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
            'user_id' => $user->id,
            'action' => UserActionEnum::ReminderUpdate->value,
            'parameters' => ['label' => 'Birthday', 'name' => 'Michael Scott'],
            'loggable_type' => Reminder::class,
            'loggable_id' => $reminder->id,
        ]);

        $first = new ReminderHistory(user: $user, reminder: $reminder, page: 1);
        $second = new ReminderHistory(user: $user, reminder: $reminder, page: 2);

        $this->assertCount(50, $first->get());
        $this->assertTrue($first->hasMore());
        $this->assertCount(1, $second->get());
        $this->assertFalse($second->hasMore());
    }

    /** @return list<string> */
    private function oneHundredAndTwentyEventsAcrossTheThreeSources(Reminder $reminder, User $user): array
    {
        $moments = [];

        foreach (range(1, 120) as $minutes) {
            $at = now()->subMinutes($minutes);
            $moments[] = $at->format('Y-m-d H:i');

            match ($minutes % 3) {
                0 => Log::factory()->create([
                    'account_id' => $reminder->account_id,
                    'vault_id' => $reminder->vault_id,
                    'user_id' => $user->id,
                    'action' => UserActionEnum::ReminderUpdate->value,
                    'parameters' => ['label' => 'Birthday', 'name' => 'Michael Scott'],
                    'loggable_type' => Reminder::class,
                    'loggable_id' => $reminder->id,
                    'created_at' => $at,
                ]),
                1 => Notification::factory()->create([
                    'account_id' => $reminder->account_id,
                    'user_id' => $user->id,
                    'channel' => NotificationChannelEnum::Email,
                    'notifiable_type' => ReminderOccurrence::class,
                    'notifiable_id' => ReminderOccurrence::factory()->daysFromNow(-$minutes)->notified()->create([
                        'reminder_id' => $reminder->id,
                        'account_id' => $reminder->account_id,
                        'vault_id' => $reminder->vault_id,
                    ])->id,
                    'sent_at' => $at,
                ]),
                default => ReminderOccurrence::factory()->daysFromNow(-$minutes)->notified()->create([
                    'reminder_id' => $reminder->id,
                    'account_id' => $reminder->account_id,
                    'vault_id' => $reminder->vault_id,
                    'notified_at' => $at,
                ]),
            };
        }

        return $moments;
    }

    /**
     * @param  list<array{date: string, time: string, title: string, note: string, tone: string}>  $entries
     * @return list<string>
     */
    private function moments(array $entries): array
    {
        return array_map(fn (array $entry): string => "{$entry['date']} {$entry['time']}", $entries);
    }

    private function userFor(Reminder $reminder): User
    {
        return User::factory()->create(['account_id' => $reminder->account_id]);
    }
}
