<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DismissReminderOccurrence;
use App\Actions\GenerateReminderOccurrences;
use App\Enums\ReminderOccurrenceStatusEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\ReminderOccurrenceIsFinished;
use App\Jobs\LogUserAction;
use App\Models\FieldValue;
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

class DismissReminderOccurrenceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_dismisses_one_deadline_and_keeps_its_row(): void
    {
        Queue::fake();
        [$user, $occurrence] = $this->deadline();

        new DismissReminderOccurrence(user: $user, occurrence: $occurrence)->execute();

        $occurrence->refresh();
        $this->assertSame(ReminderOccurrenceStatusEnum::Dismissed, $occurrence->status);
        $this->assertNotNull($occurrence->dismissed_at);
        $this->assertModelExists($occurrence);
    }

    #[Test]
    public function dismissing_this_year_leaves_next_year_alone(): void
    {
        Queue::fake();
        CarbonImmutable::setTestNow('2027-05-01 06:00:00');
        $value = FieldValue::factory()->recurringDate('1984-05-12')->create();
        $reminder = Reminder::factory()->for($value, 'fieldValue')->create([
            'account_id' => $value->account_id,
            'vault_id' => $value->vault_id,
            'field_id' => $value->field_id,
            'remindable_id' => $value->valuable_id,
        ]);
        $user = User::factory()->create(['account_id' => $reminder->account_id]);
        new GenerateReminderOccurrences(reminder: $reminder)->execute();

        $thisYear = ReminderOccurrence::query()->where('generation_key', '1:2027-05-12:on::')->firstOrFail();
        new DismissReminderOccurrence(user: $user, occurrence: $thisYear)->execute();

        CarbonImmutable::setTestNow('2028-05-01 06:00:00');
        new GenerateReminderOccurrences(reminder: $reminder->refresh())->execute();

        $this->assertDatabaseHas('reminder_occurrences', [
            'generation_key' => '1:2028-05-12:on::',
            'status' => ReminderOccurrenceStatusEnum::Pending->value,
        ]);
    }

    #[Test]
    public function it_touches_no_sibling_of_the_same_cycle(): void
    {
        Queue::fake();
        [$user, $occurrence] = $this->deadline();
        $sibling = ReminderOccurrence::factory()->daysBefore()->create([
            'reminder_id' => $occurrence->reminder_id,
            'account_id' => $occurrence->account_id,
            'vault_id' => $occurrence->vault_id,
            'source_local_date' => $occurrence->source_local_date,
        ]);

        new DismissReminderOccurrence(user: $user, occurrence: $occurrence)->execute();

        $this->assertSame(ReminderOccurrenceStatusEnum::Pending, $sibling->refresh()->status);
    }

    #[Test]
    public function it_refuses_a_deadline_already_dealt_with(): void
    {
        Queue::fake();
        [$user, $occurrence] = $this->deadline();
        $occurrence->update(['status' => ReminderOccurrenceStatusEnum::Dismissed]);

        $this->expectException(ReminderOccurrenceIsFinished::class);

        new DismissReminderOccurrence(user: $user, occurrence: $occurrence)->execute();
    }

    #[Test]
    public function it_does_not_find_a_deadline_belonging_to_another_account(): void
    {
        Queue::fake();
        [$user] = $this->deadline();
        [, $elsewhere] = $this->deadline();

        $this->expectException(ModelNotFoundException::class);

        new DismissReminderOccurrence(user: $user, occurrence: $elsewhere)->execute();
    }

    #[Test]
    public function it_abandons_notifications_that_have_not_gone_out(): void
    {
        Queue::fake();
        [$user, $occurrence] = $this->deadline();
        $waiting = Notification::factory()->create([
            'account_id' => $occurrence->account_id,
            'user_id' => $user->id,
            'notifiable_id' => $occurrence->id,
        ]);

        new DismissReminderOccurrence(user: $user, occurrence: $occurrence)->execute();

        $this->assertNotNull($waiting->refresh()->failed_at);
        $this->assertSame('The reminder was dismissed.', $waiting->failure_reason);
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
        ]);

        new DismissReminderOccurrence(user: $user, occurrence: $occurrence)->execute();

        $this->assertNull($sent->refresh()->failed_at);
        $this->assertNotNull($sent->sent_at);
    }

    #[Test]
    public function it_writes_a_line_in_the_journal(): void
    {
        Queue::fake();
        [$user, $occurrence] = $this->deadline();

        new DismissReminderOccurrence(user: $user, occurrence: $occurrence)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::ReminderOccurrenceDismissal);
    }

    /** @return array{0: User, 1: ReminderOccurrence} */
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
}
