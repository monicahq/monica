<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\SendTestReminderNotification;
use App\Models\NotificationPreference;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use App\Notifications\ReminderDue;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SendTestReminderNotificationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_sends_the_reminder_to_whoever_asked(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $occurrence = $this->deadline($user);

        new SendTestReminderNotification(user: $user, occurrence: $occurrence)->execute();

        Notification::assertSentTo($user, ReminderDue::class);
    }

    #[Test]
    public function it_says_what_the_real_one_will_say(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $occurrence = $this->deadline($user);

        new SendTestReminderNotification(user: $user, occurrence: $occurrence)->execute();

        Notification::assertSentTo($user, ReminderDue::class, function (ReminderDue $notification) use ($occurrence): bool {
            return $notification->reminderName === 'Stanley Hudson'
                && $notification->whenItIsAbout === 'Due '.$occurrence->local_date->locale('en')->isoFormat('LL');
        });
    }

    #[Test]
    public function it_leaves_the_deadline_exactly_as_it_was(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $occurrence = $this->deadline($user);
        $was = [
            'notified_at' => $occurrence->notified_at,
            'notifications_created_at' => $occurrence->notifications_created_at,
            'scheduled_for' => $occurrence->scheduled_for->format('Y-m-d H:i:s'),
        ];

        new SendTestReminderNotification(user: $user, occurrence: $occurrence)->execute();

        $occurrence->refresh();

        $this->assertEquals($was['notified_at'], $occurrence->notified_at);
        $this->assertEquals($was['notifications_created_at'], $occurrence->notifications_created_at);
        $this->assertNull($occurrence->answer);
        $this->assertEquals($was['scheduled_for'], $occurrence->scheduled_for->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_goes_out_during_quiet_hours(): void
    {
        Notification::fake();
        CarbonImmutable::setTestNow('2027-03-01 03:00:00');

        $user = User::factory()->create();
        $occurrence = $this->deadline($user);

        NotificationPreference::query()
            ->where('account_id', $user->account_id)
            ->update(['quiet_hours_are_enabled' => true, 'quiet_hours_start' => '22:00', 'quiet_hours_end' => '08:00']);

        new SendTestReminderNotification(user: $user, occurrence: $occurrence)->execute();

        Notification::assertSentTo($user, ReminderDue::class);
    }

    #[Test]
    public function it_never_reaches_the_list_of_what_was_sent(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $occurrence = $this->deadline($user);

        new SendTestReminderNotification(user: $user, occurrence: $occurrence)->execute();

        $this->assertEquals(
            0,
            ReminderOccurrence::query()->sentInVault($occurrence->vault)->count(),
        );
    }

    #[Test]
    public function it_refuses_a_deadline_of_another_account(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $stranger = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new SendTestReminderNotification(
            user: $stranger,
            occurrence: $this->deadline($user),
        )->execute();
    }

    private function deadline(User $user): ReminderOccurrence
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        NotificationPreference::factory()->create(['account_id' => $user->account_id]);

        $rule = Reminder::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'name' => 'Stanley Hudson',
            'timezone' => 'Europe/Paris',
        ]);

        $instant = CarbonImmutable::now()->addDays(3);

        return ReminderOccurrence::factory()->for($rule)->create([
            'account_id' => $rule->account_id,
            'vault_id' => $rule->vault_id,
            'generation_key' => '1:'.$instant->toDateString(),
            'scheduled_for' => $instant,
            'local_date' => $instant->toDateString(),
            'notified_at' => null,
        ]);
    }
}
