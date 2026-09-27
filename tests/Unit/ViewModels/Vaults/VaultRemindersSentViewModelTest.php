<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\NotificationChannelEnum;
use App\Enums\ReminderAnswerEnum;
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
        $this->assertEquals('2027-03-05T09:00:00+00:00', $rows[0]->sentIso());
        $this->assertEquals('2027-03-03T09:00:00+00:00', $rows[1]->sentIso());
        $this->assertEquals('2027-03-01T09:00:00+00:00', $rows[2]->sentIso());
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

        $this->assertEquals(['In the app', 'Email'], $rows[0]->channels());
    }

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
    public function it_says_what_was_answered_for_each_one(): void
    {
        $vault = Vault::factory()->create();
        $user = User::factory()->create(['account_id' => $vault->account_id]);
        $reminder = $this->reminder($vault);

        $this->sentOccurrence($reminder, '2027-03-05 09:00:00', ReminderAnswerEnum::Remembered);
        $this->sentOccurrence($reminder, '2027-03-03 09:00:00', ReminderAnswerEnum::Forgotten);
        $this->sentOccurrence($reminder, '2027-03-01 09:00:00');

        $rows = new VaultRemindersSentViewModel(user: $user, vault: $vault)->rows();

        $this->assertEquals(['You remembered', 'success'], [$rows[0]->outcome(), $rows[0]->tone()]);
        $this->assertEquals(['You forgot', 'neutral'], [$rows[1]->outcome(), $rows[1]->tone()]);
        $this->assertEquals(['No answer yet', 'warning'], [$rows[2]->outcome(), $rows[2]->tone()]);
    }

    #[Test]
    public function a_notification_that_went_out_never_reads_as_remembered(): void
    {
        $vault = Vault::factory()->create();
        $user = User::factory()->create(['account_id' => $vault->account_id]);
        $occurrence = $this->sentOccurrence($this->reminder($vault), '2027-03-01 09:00:00');
        $this->notification($occurrence, $user, NotificationChannelEnum::Email);

        $rows = new VaultRemindersSentViewModel(user: $user, vault: $vault)->rows();

        $this->assertSame('No answer yet', $rows[0]->outcome());
    }

    #[Test]
    public function it_never_says_a_reminder_was_completed(): void
    {
        $vault = Vault::factory()->create();
        $user = User::factory()->create(['account_id' => $vault->account_id]);

        foreach ([null, ...ReminderAnswerEnum::cases()] as $answer) {
            $this->sentOccurrence($this->reminder($vault), '2027-03-01 09:00:00', $answer);
        }

        foreach (new VaultRemindersSentViewModel(user: $user, vault: $vault)->rows() as $row) {
            $this->assertStringNotContainsStringIgnoringCase('complet', $row->outcome());
            $this->assertStringNotContainsStringIgnoringCase('done', $row->outcome());
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
    public function it_counts_every_tab(): void
    {
        $vault = Vault::factory()->create();
        $user = User::factory()->create(['account_id' => $vault->account_id]);
        $reminder = $this->reminder($vault);

        $this->sentOccurrence($reminder, '2027-03-01 09:00:00', ReminderAnswerEnum::Remembered);

        $viewModel = new VaultRemindersSentViewModel(user: $user, vault: $vault);

        $this->assertEquals(1, $viewModel->sentCount());
        $this->assertEquals(1, $viewModel->historyCount());
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
        ?ReminderAnswerEnum $answer = null,
    ): ReminderOccurrence {
        $sent = CarbonImmutable::parse($notifiedAt);

        return ReminderOccurrence::factory()->create([
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
            'reminder_id' => $reminder->id,
            'local_date' => $sent->toDateString(),
            'scheduled_for' => $sent,
            'generation_key' => 'sent:'.$notifiedAt.':'.self::$written++,
            'notifications_created_at' => $sent,
            'notified_at' => $sent,
            'answer' => $answer,
            'answered_at' => $answer === null ? null : $sent->addHour(),
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
