<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\AnswerReminderOccurrence;
use App\Enums\ReminderAnswerEnum;
use App\Enums\ReminderOccurrenceCancelledReasonEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Notification;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AnswerReminderOccurrenceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_that_somebody_remembered_a_date_due_today(): void
    {
        Queue::fake();
        [$user, $occurrence] = $this->deadline(daysFromNow: 0);

        $answered = new AnswerReminderOccurrence(
            user: $user,
            occurrence: $occurrence,
            answer: ReminderAnswerEnum::Remembered,
        )->execute();

        $this->assertInstanceOf(ReminderOccurrence::class, $answered);
        $this->assertDatabaseHas('reminder_occurrences', [
            'id' => $occurrence->id,
            'answer' => ReminderAnswerEnum::Remembered->value,
            'answered_at' => '2027-05-12 10:00:00',
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ReminderOccurrenceRemembered
                && $job->account->id === $user->account_id
                && $job->user->id === $user->id
                && $job->vault->id === $occurrence->vault_id
                && $job->isAbout($occurrence)
                && $job->parameters === ['name' => 'Ask Dwight about the beet harvest', 'date' => '2027-05-12'],
        );
    }

    #[Test]
    public function it_records_that_somebody_forgot_a_date_from_the_day_after(): void
    {
        Queue::fake();
        [$user, $occurrence] = $this->deadline(daysFromNow: -1);

        new AnswerReminderOccurrence(
            user: $user,
            occurrence: $occurrence,
            answer: ReminderAnswerEnum::Forgotten,
        )->execute();

        $this->assertDatabaseHas('reminder_occurrences', [
            'id' => $occurrence->id,
            'answer' => ReminderAnswerEnum::Forgotten->value,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ReminderOccurrenceForgotten,
        );
    }

    #[Test]
    public function it_refuses_to_say_somebody_remembered_before_the_day(): void
    {
        Queue::fake();
        [$user, $occurrence] = $this->deadline(daysFromNow: 1);

        $this->expectException(AuthorizationException::class);

        new AnswerReminderOccurrence(
            user: $user,
            occurrence: $occurrence,
            answer: ReminderAnswerEnum::Remembered,
        )->execute();
    }

    #[Test]
    public function it_refuses_to_say_somebody_forgot_on_the_day_itself(): void
    {
        Queue::fake();
        [$user, $occurrence] = $this->deadline(daysFromNow: 0);

        $this->expectException(AuthorizationException::class);

        new AnswerReminderOccurrence(
            user: $user,
            occurrence: $occurrence,
            answer: ReminderAnswerEnum::Forgotten,
        )->execute();
    }

    #[Test]
    public function it_refuses_a_second_answer_whatever_it_says(): void
    {
        Queue::fake();
        [$user, $occurrence] = $this->deadline(daysFromNow: -1);

        new AnswerReminderOccurrence(
            user: $user,
            occurrence: $occurrence,
            answer: ReminderAnswerEnum::Remembered,
        )->execute();

        try {
            new AnswerReminderOccurrence(
                user: $user,
                occurrence: $occurrence->refresh(),
                answer: ReminderAnswerEnum::Forgotten,
            )->execute();
            $this->fail('A second answer was accepted.');
        } catch (AuthorizationException) {
            $this->assertSame(ReminderAnswerEnum::Remembered, $occurrence->refresh()->answer);
        }
    }

    #[Test]
    public function it_refuses_an_answer_sent_from_a_screen_that_had_not_seen_the_first_one(): void
    {
        Queue::fake();
        [$user, $occurrence] = $this->deadline(daysFromNow: -1);
        $stale = ReminderOccurrence::query()->findOrFail($occurrence->id);

        new AnswerReminderOccurrence(
            user: $user,
            occurrence: $occurrence,
            answer: ReminderAnswerEnum::Remembered,
        )->execute();

        $this->expectException(AuthorizationException::class);

        new AnswerReminderOccurrence(
            user: $user,
            occurrence: $stale,
            answer: ReminderAnswerEnum::Forgotten,
        )->execute();
    }

    #[Test]
    public function it_refuses_a_cancelled_date(): void
    {
        Queue::fake();
        [$user, $occurrence] = $this->deadline(daysFromNow: -1);
        $occurrence->update([
            'cancelled_at' => now(),
            'cancelled_reason' => ReminderOccurrenceCancelledReasonEnum::RecordArchived,
        ]);

        $this->expectException(AuthorizationException::class);

        new AnswerReminderOccurrence(
            user: $user,
            occurrence: $occurrence->refresh(),
            answer: ReminderAnswerEnum::Forgotten,
        )->execute();
    }

    #[Test]
    public function it_refuses_a_date_of_another_account(): void
    {
        Queue::fake();
        [, $occurrence] = $this->deadline(daysFromNow: 0);
        $somebodyElse = User::factory()->create(['timezone' => 'UTC']);

        $this->expectException(ModelNotFoundException::class);

        new AnswerReminderOccurrence(
            user: $somebodyElse,
            occurrence: $occurrence,
            answer: ReminderAnswerEnum::Remembered,
        )->execute();
    }

    #[Test]
    public function it_drops_the_notifications_not_sent_yet_and_keeps_those_already_sent(): void
    {
        Queue::fake();
        [$user, $occurrence] = $this->deadline(daysFromNow: 0);
        $sent = Notification::factory()->sent()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'notifiable_id' => $occurrence->id,
        ]);
        $waiting = Notification::factory()->email()->create([
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'notifiable_id' => $occurrence->id,
        ]);

        new AnswerReminderOccurrence(
            user: $user,
            occurrence: $occurrence,
            answer: ReminderAnswerEnum::Remembered,
        )->execute();

        $this->assertNotNull($waiting->refresh()->failed_at);
        $this->assertNull($waiting->sent_at);
        $this->assertNotNull($sent->refresh()->sent_at);
        $this->assertNull($sent->failed_at);
    }

    #[Test]
    public function it_leaves_the_next_date_of_the_same_reminder_waiting(): void
    {
        Queue::fake();
        [$user, $occurrence] = $this->deadline(daysFromNow: 0);
        $next = ReminderOccurrence::factory()->daysFromNow(365)->create([
            'reminder_id' => $occurrence->reminder_id,
            'account_id' => $occurrence->account_id,
            'vault_id' => $occurrence->vault_id,
        ]);

        new AnswerReminderOccurrence(
            user: $user,
            occurrence: $occurrence,
            answer: ReminderAnswerEnum::Remembered,
        )->execute();

        $this->assertDatabaseHas('reminder_occurrences', [
            'id' => $next->id,
            'answer' => null,
            'cancelled_at' => null,
        ]);
    }

    /** @return array{0: User, 1: ReminderOccurrence} */
    private function deadline(int $daysFromNow): array
    {
        CarbonImmutable::setTestNow('2027-05-12 10:00:00');

        $user = User::factory()->create(['timezone' => 'UTC']);
        $contact = Contact::factory()->create(['account_id' => $user->account_id, 'first_name' => 'Dwight']);
        $reminder = Reminder::factory()->manual($contact, '2027-05-01')->create([
            'name' => 'Ask Dwight about the beet harvest',
            'timezone' => 'UTC',
        ]);

        $occurrence = ReminderOccurrence::factory()->daysFromNow($daysFromNow)->create([
            'reminder_id' => $reminder->id,
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
        ]);

        return [$user, $occurrence];
    }
}
