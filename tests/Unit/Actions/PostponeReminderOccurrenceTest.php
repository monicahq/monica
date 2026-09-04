<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\PostponeReminderOccurrence;
use App\Enums\ReminderOccurrenceStatusEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\ReminderCannotBePostponedIntoThePast;
use App\Exceptions\ReminderOccurrenceIsFinished;
use App\Jobs\LogUserAction;
use App\Models\Notification;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PostponeReminderOccurrenceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_the_deadline_and_remembers_where_it_started(): void
    {
        Queue::fake();
        [$user, $occurrence] = $this->deadline();
        $original = $occurrence->original_scheduled_for;
        $until = CarbonImmutable::now()->addDays(3)->startOfHour();

        new PostponeReminderOccurrence(user: $user, occurrence: $occurrence, until: $until)->execute();

        $occurrence->refresh();
        $this->assertTrue($occurrence->scheduled_for->equalTo($until));
        $this->assertTrue($occurrence->postponed_to->equalTo($until));
        $this->assertTrue($occurrence->original_scheduled_for->equalTo($original));
        $this->assertSame(1, $occurrence->postponed_count);
        $this->assertSame(ReminderOccurrenceStatusEnum::Postponed, $occurrence->status);
    }

    #[Test]
    public function it_moves_the_local_day_along_with_the_instant(): void
    {
        Queue::fake();
        [$user, $occurrence] = $this->deadline();
        $until = CarbonImmutable::parse('2027-08-20 07:00', 'UTC');
        $this->travelTo('2027-08-01 00:00:00');

        new PostponeReminderOccurrence(user: $user, occurrence: $occurrence, until: $until)->execute();

        $this->assertSame('2027-08-20', $occurrence->refresh()->local_date->format('Y-m-d'));
    }

    #[Test]
    public function it_touches_no_sibling_of_the_same_cycle(): void
    {
        Queue::fake();
        [$user, $occurrence, $sibling] = $this->deadlineWithSibling();
        $before = $sibling->scheduled_for;

        new PostponeReminderOccurrence(
            user: $user,
            occurrence: $occurrence,
            until: CarbonImmutable::now()->addDays(3),
        )->execute();

        $this->assertTrue($before->equalTo($sibling->refresh()->scheduled_for));
        $this->assertSame(ReminderOccurrenceStatusEnum::Pending, $sibling->status);
        $this->assertSame(0, $sibling->postponed_count);
    }

    #[Test]
    public function it_touches_neither_the_rule_nor_the_date_it_reads(): void
    {
        Queue::fake();
        [$user, $occurrence] = $this->deadline();
        $reminder = $occurrence->reminder;
        $date = $reminder->fieldValue->value_date;

        new PostponeReminderOccurrence(
            user: $user,
            occurrence: $occurrence,
            until: CarbonImmutable::now()->addDays(3),
        )->execute();

        $this->assertSame(1, $reminder->refresh()->schedule_revision);
        $this->assertEquals($date, $reminder->fieldValue->refresh()->value_date);
    }

    #[Test]
    public function it_counts_every_time_somebody_puts_it_off(): void
    {
        Queue::fake();
        [$user, $occurrence] = $this->deadline();

        new PostponeReminderOccurrence(user: $user, occurrence: $occurrence, until: CarbonImmutable::now()->addDay())->execute();
        new PostponeReminderOccurrence(user: $user, occurrence: $occurrence->refresh(), until: CarbonImmutable::now()->addDays(2))->execute();

        $this->assertSame(2, $occurrence->refresh()->postponed_count);
    }

    #[Test]
    public function it_abandons_notifications_for_the_old_instant(): void
    {
        Queue::fake();
        [$user, $occurrence] = $this->deadline();
        $waiting = Notification::factory()->create([
            'account_id' => $occurrence->account_id,
            'user_id' => $user->id,
            'notifiable_id' => $occurrence->id,
            'scheduled_for' => $occurrence->scheduled_for,
            'held_until' => $occurrence->scheduled_for,
        ]);

        new PostponeReminderOccurrence(
            user: $user,
            occurrence: $occurrence,
            until: CarbonImmutable::now()->addDays(3),
        )->execute();

        $this->assertNotNull($waiting->refresh()->failed_at);
        $this->assertSame('The reminder was postponed.', $waiting->failure_reason);
    }

    #[Test]
    public function it_keeps_notifications_already_sent_as_history(): void
    {
        Queue::fake();
        [$user, $occurrence] = $this->deadline();
        $sent = Notification::factory()->sent()->create([
            'account_id' => $occurrence->account_id,
            'user_id' => $user->id,
            'notifiable_id' => $occurrence->id,
            'scheduled_for' => $occurrence->scheduled_for,
            'held_until' => $occurrence->scheduled_for,
        ]);

        new PostponeReminderOccurrence(
            user: $user,
            occurrence: $occurrence,
            until: CarbonImmutable::now()->addDays(3),
        )->execute();

        $this->assertNull($sent->refresh()->failed_at);
        $this->assertNotNull($sent->sent_at);
    }

    #[Test]
    public function it_refuses_to_move_a_deadline_backwards(): void
    {
        Queue::fake();
        [$user, $occurrence] = $this->deadline();

        $this->expectException(ReminderCannotBePostponedIntoThePast::class);

        new PostponeReminderOccurrence(
            user: $user,
            occurrence: $occurrence,
            until: CarbonImmutable::now()->subDay(),
        )->execute();
    }

    #[Test]
    public function it_refuses_a_deadline_already_dealt_with(): void
    {
        Queue::fake();
        [$user, $occurrence] = $this->deadline();
        $occurrence->update(['status' => ReminderOccurrenceStatusEnum::Dismissed]);

        $this->expectException(ReminderOccurrenceIsFinished::class);

        new PostponeReminderOccurrence(
            user: $user,
            occurrence: $occurrence,
            until: CarbonImmutable::now()->addDay(),
        )->execute();
    }

    #[Test]
    public function it_does_not_find_a_deadline_belonging_to_another_account(): void
    {
        Queue::fake();
        [$user] = $this->deadline();
        [, $elsewhere] = $this->deadline();

        $this->expectException(ModelNotFoundException::class);

        new PostponeReminderOccurrence(
            user: $user,
            occurrence: $elsewhere,
            until: CarbonImmutable::now()->addDay(),
        )->execute();
    }

    #[Test]
    public function it_writes_a_line_in_the_journal(): void
    {
        Queue::fake();
        [$user, $occurrence] = $this->deadline();

        new PostponeReminderOccurrence(
            user: $user,
            occurrence: $occurrence,
            until: CarbonImmutable::now()->addDay(),
        )->execute();

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::ReminderOccurrencePostponement);
    }

    /**
     * @return array{0: User, 1: ReminderOccurrence}
     */
    private function deadline(): array
    {
        $reminder = Reminder::factory()->create();
        $user = User::factory()->create(['account_id' => $reminder->account_id]);
        $occurrence = ReminderOccurrence::factory()->create([
            'reminder_id' => $reminder->id,
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
        ]);

        return [$user, $occurrence];
    }

    /**
     * @return array{0: User, 1: ReminderOccurrence, 2: ReminderOccurrence}
     */
    private function deadlineWithSibling(): array
    {
        [$user, $occurrence] = $this->deadline();

        $sibling = ReminderOccurrence::factory()->daysBefore()->create([
            'reminder_id' => $occurrence->reminder_id,
            'account_id' => $occurrence->account_id,
            'vault_id' => $occurrence->vault_id,
            'source_local_date' => $occurrence->source_local_date,
        ]);

        return [$user, $occurrence, $sibling];
    }
}
