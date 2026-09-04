<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\GenerateReminderOccurrences;
use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\ReminderMomentDirectionEnum;
use App\Enums\ReminderMomentUnitEnum;
use App\Enums\ReminderOccurrenceStatusEnum;
use App\Models\FieldValue;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GenerateReminderOccurrencesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_one_deadline_for_every_moment_of_the_cycle(): void
    {
        CarbonImmutable::setTestNow('2027-05-01 06:00:00');
        $reminder = $this->rule('1984-05-12');

        $written = new GenerateReminderOccurrences(reminder: $reminder)->execute();

        $this->assertSame(2, $written);
        $this->assertDatabaseHas('reminder_occurrences', [
            'reminder_id' => $reminder->id,
            'source_local_date' => '2027-05-12',
            'moment_direction' => ReminderMomentDirectionEnum::Before->value,
            'moment_amount' => 7,
            'moment_unit' => ReminderMomentUnitEnum::Days->value,
            'generation_key' => '1:2027-05-12:before:7:days',
            'status' => ReminderOccurrenceStatusEnum::Pending->value,
        ]);
        $this->assertDatabaseHas('reminder_occurrences', [
            'reminder_id' => $reminder->id,
            'generation_key' => '1:2027-05-12:on::',
        ]);
    }

    #[Test]
    public function it_stores_the_instant_in_utc_and_the_day_in_the_zone_of_the_rule(): void
    {
        CarbonImmutable::setTestNow('2027-05-01 06:00:00');
        $reminder = $this->rule('1984-05-12');

        new GenerateReminderOccurrences(reminder: $reminder)->execute();

        $occurrence = ReminderOccurrence::query()->where('generation_key', '1:2027-05-12:on::')->firstOrFail();

        $this->assertSame('2027-05-12 07:00:00', $occurrence->scheduled_for->format('Y-m-d H:i:s'));
        $this->assertSame('2027-05-12', $occurrence->local_date->format('Y-m-d'));
        $this->assertTrue($occurrence->scheduled_for->equalTo($occurrence->original_scheduled_for));
    }

    #[Test]
    public function it_copies_the_compartment_of_the_rule_onto_every_deadline(): void
    {
        CarbonImmutable::setTestNow('2027-05-01 06:00:00');
        $reminder = $this->rule('1984-05-12');

        new GenerateReminderOccurrences(reminder: $reminder)->execute();

        $this->assertSame(0, ReminderOccurrence::query()
            ->where('reminder_id', $reminder->id)
            ->where(fn ($query) => $query
                ->where('account_id', '!=', $reminder->account_id)
                ->orWhere('vault_id', '!=', $reminder->vault_id))
            ->count());
    }

    #[Test]
    public function it_writes_nothing_the_second_time_over_the_same_window(): void
    {
        CarbonImmutable::setTestNow('2027-05-01 06:00:00');
        $reminder = $this->rule('1984-05-12');

        new GenerateReminderOccurrences(reminder: $reminder)->execute();
        $again = new GenerateReminderOccurrences(reminder: $reminder)->execute();

        $this->assertSame(0, $again);
        $this->assertSame(2, ReminderOccurrence::query()->where('reminder_id', $reminder->id)->count());
    }

    #[Test]
    public function it_produces_one_row_when_two_workers_generate_the_same_deadline_at_once(): void
    {
        CarbonImmutable::setTestNow('2027-05-01 06:00:00');
        $reminder = $this->rule('1984-05-12');

        new GenerateReminderOccurrences(reminder: $reminder)->execute();

        $written = ReminderOccurrence::query()->insertOrIgnore([
            'uuid' => (string) Str::uuid(),
            'account_id' => $reminder->account_id,
            'vault_id' => $reminder->vault_id,
            'reminder_id' => $reminder->id,
            'source_local_date' => '2027-05-12',
            'moment_direction' => ReminderMomentDirectionEnum::On->value,
            'generation_key' => '1:2027-05-12:on::',
            'scheduled_for' => now(),
            'original_scheduled_for' => now(),
            'local_date' => '2027-05-12',
            'status' => ReminderOccurrenceStatusEnum::Pending->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(0, $written);
        $this->assertSame(2, ReminderOccurrence::query()->where('reminder_id', $reminder->id)->count());
    }

    #[Test]
    public function it_never_writes_a_deadline_that_has_already_gone(): void
    {
        CarbonImmutable::setTestNow('2027-05-08 06:00:00');
        $reminder = $this->rule('1984-05-12');

        new GenerateReminderOccurrences(reminder: $reminder)->execute();

        $this->assertDatabaseMissing('reminder_occurrences', ['generation_key' => '1:2027-05-12:before:7:days']);
        $this->assertDatabaseHas('reminder_occurrences', ['generation_key' => '1:2027-05-12:on::']);
    }

    #[Test]
    public function it_writes_nothing_for_a_rule_that_has_gone_quiet(): void
    {
        CarbonImmutable::setTestNow('2027-05-01 06:00:00');
        $reminder = $this->rule('1984-05-12');
        $reminder->update([
            'is_active' => false,
            'inactive_reason' => ReminderInactiveReasonEnum::RecordArchived,
        ]);

        $this->assertSame(0, new GenerateReminderOccurrences(reminder: $reminder)->execute());
        $this->assertDatabaseCount('reminder_occurrences', 0);
    }

    #[Test]
    public function it_writes_nothing_for_a_rule_somebody_took_away(): void
    {
        CarbonImmutable::setTestNow('2027-05-01 06:00:00');
        $reminder = $this->rule('1984-05-12');
        $reminder->delete();

        $this->assertSame(0, new GenerateReminderOccurrences(reminder: $reminder)->execute());
    }

    #[Test]
    public function it_writes_nothing_for_a_rule_whose_date_has_been_cleared(): void
    {
        CarbonImmutable::setTestNow('2027-05-01 06:00:00');
        $reminder = $this->rule('1984-05-12');
        $reminder->fieldValue->delete();
        $reminder->refresh()->load('fieldValue');

        $this->assertSame(0, new GenerateReminderOccurrences(reminder: $reminder)->execute());
    }

    #[Test]
    public function it_writes_the_deadlines_of_a_rule_that_fires_once_and_then_no_more(): void
    {
        CarbonImmutable::setTestNow('2027-05-01 06:00:00');
        $reminder = $this->rule('2027-05-12', ReminderCadenceEnum::Once);

        $this->assertSame(2, new GenerateReminderOccurrences(reminder: $reminder)->execute());

        CarbonImmutable::setTestNow('2028-05-01 06:00:00');

        $this->assertSame(0, new GenerateReminderOccurrences(reminder: $reminder)->execute());
        $this->assertSame(2, ReminderOccurrence::query()->where('reminder_id', $reminder->id)->count());
    }

    #[Test]
    public function it_stops_at_the_far_end_of_the_window(): void
    {
        CarbonImmutable::setTestNow('2027-01-01 06:00:00');
        config(['monica.reminder_generation_window_in_days' => 30]);
        $reminder = $this->rule('1984-05-12');

        $this->assertSame(0, new GenerateReminderOccurrences(reminder: $reminder)->execute());
    }

    #[Test]
    public function it_gives_a_deadline_a_new_identity_once_the_schedule_has_changed(): void
    {
        CarbonImmutable::setTestNow('2027-05-01 06:00:00');
        $reminder = $this->rule('1984-05-12');
        new GenerateReminderOccurrences(reminder: $reminder)->execute();

        $reminder->update(['schedule_revision' => 2, 'time_of_day' => '10:00:00']);

        $this->assertSame(2, new GenerateReminderOccurrences(reminder: $reminder->refresh())->execute());
        $this->assertDatabaseHas('reminder_occurrences', ['generation_key' => '2:2027-05-12:on::']);
        $this->assertSame(4, ReminderOccurrence::query()->where('reminder_id', $reminder->id)->count());
    }

    #[Test]
    public function it_never_puts_back_a_deadline_somebody_has_acted_on(): void
    {
        CarbonImmutable::setTestNow('2027-05-01 06:00:00');
        $reminder = $this->rule('1984-05-12');
        new GenerateReminderOccurrences(reminder: $reminder)->execute();

        $onTheDay = ReminderOccurrence::query()->where('generation_key', '1:2027-05-12:on::')->firstOrFail();
        $onTheDay->update([
            'status' => ReminderOccurrenceStatusEnum::Postponed,
            'scheduled_for' => $onTheDay->scheduled_for->addDays(3),
            'postponed_count' => 1,
        ]);

        $written = new GenerateReminderOccurrences(reminder: $reminder)->execute();

        $this->assertSame(0, $written);
        $this->assertSame(1, ReminderOccurrence::query()->where('generation_key', '1:2027-05-12:on::')->count());
        $this->assertSame(ReminderOccurrenceStatusEnum::Postponed, $onTheDay->refresh()->status);
    }

    #[Test]
    public function it_never_puts_back_a_deadline_somebody_dismissed(): void
    {
        CarbonImmutable::setTestNow('2027-05-01 06:00:00');
        $reminder = $this->rule('1984-05-12');
        new GenerateReminderOccurrences(reminder: $reminder)->execute();

        ReminderOccurrence::query()->where('generation_key', '1:2027-05-12:on::')
            ->update(['status' => ReminderOccurrenceStatusEnum::Dismissed, 'dismissed_at' => now()]);

        $this->assertSame(0, new GenerateReminderOccurrences(reminder: $reminder)->execute());
        $this->assertSame(2, ReminderOccurrence::query()->where('reminder_id', $reminder->id)->count());
    }

    private function rule(string $date, ReminderCadenceEnum $cadence = ReminderCadenceEnum::Yearly): Reminder
    {
        $value = FieldValue::factory()->recurringDate($date)->create();

        return Reminder::factory()->for($value, 'fieldValue')->create([
            'account_id' => $value->account_id,
            'vault_id' => $value->vault_id,
            'field_id' => $value->field_id,
            'remindable_id' => $value->valuable_id,
            'cadence' => $cadence,
        ]);
    }
}
