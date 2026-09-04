<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\NotificationChannelEnum;
use App\Enums\ReminderOccurrenceStatusEnum;
use App\Models\Notification;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\VaultRemindersSentViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultRemindersSentViewModelTest extends TestCase
{
    use RefreshDatabase;

    private static int $written = 0;

    #[Test]
    public function it_lists_what_has_been_sent_newest_first(): void
    {
        $vault = Vault::factory()->create();
        $user = User::factory()->create(['account_id' => $vault->account_id]);
        $reminder = $this->reminder($vault);

        $this->sentOccurrence($reminder, '2027-03-01 09:00:00');
        $this->sentOccurrence($reminder, '2027-03-05 09:00:00');
        $this->sentOccurrence($reminder, '2027-03-03 09:00:00');

        $rows = new VaultRemindersSentViewModel(user: $user, vault: $vault)->rows();

        $this->assertCount(3, $rows);
        $this->assertEquals('2027-03-05T09:00:00+00:00', $rows[0]['iso']);
        $this->assertEquals('2027-03-03T09:00:00+00:00', $rows[1]['iso']);
        $this->assertEquals('2027-03-01T09:00:00+00:00', $rows[2]['iso']);
    }

    #[Test]
    public function it_leaves_out_a_deadline_that_was_never_sent(): void
    {
        $vault = Vault::factory()->create();
        $user = User::factory()->create(['account_id' => $vault->account_id]);
        $reminder = $this->reminder($vault);

        ReminderOccurrence::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'reminder_id' => $reminder->id,
            'notified_at' => null,
        ]);

        $this->assertEquals([], new VaultRemindersSentViewModel(user: $user, vault: $vault)->rows());
    }

    #[Test]
    public function it_leaves_out_what_another_vault_has_sent(): void
    {
        $vault = Vault::factory()->create();
        $user = User::factory()->create(['account_id' => $vault->account_id]);
        $other = Vault::factory()->create(['account_id' => $vault->account_id]);

        $this->sentOccurrence($this->reminder($other), '2027-03-01 09:00:00');

        $this->assertEquals([], new VaultRemindersSentViewModel(user: $user, vault: $vault)->rows());
    }

    #[Test]
    public function it_says_how_the_reminder_reached_somebody(): void
    {
        $vault = Vault::factory()->create();
        $user = User::factory()->create(['account_id' => $vault->account_id]);
        $occurrence = $this->sentOccurrence($this->reminder($vault), '2027-03-01 09:00:00');

        $this->notification($occurrence, $user, NotificationChannelEnum::InApp);
        $this->notification($occurrence, $user, NotificationChannelEnum::Email);

        $rows = new VaultRemindersSentViewModel(user: $user, vault: $vault)->rows();

        $this->assertEquals(['In the app', 'By email'], $rows[0]['channels']);
    }

    /**
     * One deadline that reached somebody twice over is one thing that happened,
     * so it is one row with two marks rather than two rows.
     */
    #[Test]
    public function a_deadline_sent_on_two_channels_is_still_one_row(): void
    {
        $vault = Vault::factory()->create();
        $user = User::factory()->create(['account_id' => $vault->account_id]);
        $occurrence = $this->sentOccurrence($this->reminder($vault), '2027-03-01 09:00:00');

        $this->notification($occurrence, $user, NotificationChannelEnum::InApp);
        $this->notification($occurrence, $user, NotificationChannelEnum::Email);

        $this->assertCount(1, new VaultRemindersSentViewModel(user: $user, vault: $vault)->rows());
    }

    #[Test]
    public function it_says_what_became_of_each_one(): void
    {
        $vault = Vault::factory()->create();
        $user = User::factory()->create(['account_id' => $vault->account_id]);
        $reminder = $this->reminder($vault);

        $this->sentOccurrence($reminder, '2027-03-05 09:00:00', ReminderOccurrenceStatusEnum::Dismissed);
        $this->sentOccurrence($reminder, '2027-03-03 09:00:00', ReminderOccurrenceStatusEnum::Postponed);
        $this->sentOccurrence($reminder, '2027-03-01 09:00:00', ReminderOccurrenceStatusEnum::Notified);

        $rows = new VaultRemindersSentViewModel(user: $user, vault: $vault)->rows();

        $this->assertEquals(['Dismissed', 'neutral'], [$rows[0]['outcome'], $rows[0]['tone']]);
        $this->assertEquals(['Put off', 'info'], [$rows[1]['outcome'], $rows[1]['tone']]);
        $this->assertEquals(['Still open', 'warning'], [$rows[2]['outcome'], $rows[2]['tone']]);
    }

    /**
     * The product has no notion of a reminder being completed, so no wording
     * here may claim one.
     */
    #[Test]
    public function it_never_says_a_reminder_was_completed(): void
    {
        $vault = Vault::factory()->create();
        $user = User::factory()->create(['account_id' => $vault->account_id]);

        foreach (ReminderOccurrenceStatusEnum::cases() as $status) {
            $this->sentOccurrence($this->reminder($vault), '2027-03-01 09:00:00', $status);
        }

        foreach (new VaultRemindersSentViewModel(user: $user, vault: $vault)->rows() as $row) {
            $this->assertStringNotContainsStringIgnoringCase('complet', $row['outcome']);
            $this->assertStringNotContainsStringIgnoringCase('done', $row['outcome']);
        }
    }

    #[Test]
    public function it_pages_a_long_history(): void
    {
        $vault = Vault::factory()->create();
        $user = User::factory()->create(['account_id' => $vault->account_id]);
        $reminder = $this->reminder($vault);

        for ($i = 1; $i <= VaultRemindersSentViewModel::PER_PAGE + 3; $i++) {
            $this->sentOccurrence($reminder, CarbonImmutable::parse('2027-03-01 09:00:00')->addDays($i)->toDateTimeString());
        }

        $viewModel = new VaultRemindersSentViewModel(user: $user, vault: $vault);

        $this->assertCount(VaultRemindersSentViewModel::PER_PAGE, $viewModel->rows());
        $this->assertTrue($viewModel->hasMorePages());
        $this->assertNotNull($viewModel->nextPageUrl());
    }

    #[Test]
    public function it_counts_both_tabs(): void
    {
        $vault = Vault::factory()->create();
        $user = User::factory()->create(['account_id' => $vault->account_id]);
        $reminder = $this->reminder($vault);

        $this->sentOccurrence($reminder, '2027-03-01 09:00:00', ReminderOccurrenceStatusEnum::Dismissed);

        ReminderOccurrence::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'reminder_id' => $reminder->id,
            'scheduled_for' => CarbonImmutable::now()->addDays(3),
            'status' => ReminderOccurrenceStatusEnum::Pending,
            'notified_at' => null,
        ]);

        $viewModel = new VaultRemindersSentViewModel(user: $user, vault: $vault);

        $this->assertEquals(1, $viewModel->sentCount());
        $this->assertEquals(1, $viewModel->activeCount());
    }

    private function reminder(Vault $vault): Reminder
    {
        return Reminder::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'timezone' => 'UTC',
        ]);
    }

    private function sentOccurrence(
        Reminder $reminder,
        string $notifiedAt,
        ReminderOccurrenceStatusEnum $status = ReminderOccurrenceStatusEnum::Notified,
    ): ReminderOccurrence {
        return ReminderOccurrence::factory()->create([
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
            'reminder_id' => $reminder->id,
            'generation_key' => 'sent:'.$notifiedAt.':'.$status->value.':'.self::$written++,
            'notified_at' => CarbonImmutable::parse($notifiedAt),
            'status' => $status,
        ]);
    }

    private function notification(ReminderOccurrence $occurrence, User $user, NotificationChannelEnum $channel): Notification
    {
        return Notification::factory()->create([
            'account_id' => $occurrence->account_id,
            'user_id' => $user->id,
            'channel' => $channel,
            'notifiable_type' => ReminderOccurrence::class,
            'notifiable_id' => $occurrence->id,
        ]);
    }
}
