<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Enums\ReminderCadenceEnum;
use App\Helpers\ReminderSchedule;
use App\Models\Contact;
use App\Models\FieldValue;
use App\Models\Reminder;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReminderScheduleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_finds_the_next_anniversary_of_a_yearly_rule(): void
    {
        $reminder = $this->ruleOn('1984-02-03', ReminderCadenceEnum::Yearly);

        $this->assertEquals('2027-02-03', $this->next($reminder, '2026-09-02'));
        $this->assertEquals('2026-02-03', $this->next($reminder, '2026-01-01'));
    }

    #[Test]
    public function it_keeps_the_day_a_yearly_rule_falls_on_when_it_is_the_day_itself(): void
    {
        $reminder = $this->ruleOn('1984-02-03', ReminderCadenceEnum::Yearly);

        $this->assertEquals('2026-02-03', $this->next($reminder, '2026-02-03'));
    }

    #[Test]
    public function it_counts_a_rhythm_from_the_year_the_date_names(): void
    {
        $reminder = $this->ruleOn('2020-06-15', ReminderCadenceEnum::Yearly, interval: 3);

        $this->assertEquals('2026-06-15', $this->next($reminder, '2026-01-01'));
        $this->assertEquals('2029-06-15', $this->next($reminder, '2026-07-01'));
    }

    #[Test]
    public function it_lands_on_a_real_day_when_a_yearly_rule_is_anchored_on_the_twenty_ninth_of_february(): void
    {
        $reminder = $this->ruleOn('2024-02-29', ReminderCadenceEnum::Yearly);

        $this->assertEquals('2027-02-28', $this->next($reminder, '2026-03-01'));
        $this->assertEquals('2028-02-29', $this->next($reminder, '2027-03-01'));
    }

    #[Test]
    public function it_lands_on_a_real_day_when_a_monthly_rule_is_anchored_on_the_thirty_first(): void
    {
        $reminder = $this->ruleOn('2026-01-31', ReminderCadenceEnum::Monthly);

        $this->assertEquals('2026-02-28', $this->next($reminder, '2026-02-01'));
        $this->assertEquals('2026-03-31', $this->next($reminder, '2026-03-01'));
    }

    #[Test]
    public function it_finds_the_next_week_of_a_weekly_rule(): void
    {
        $reminder = $this->ruleOn('2026-08-31', ReminderCadenceEnum::Weekly);

        $this->assertEquals('2026-09-07', $this->next($reminder, '2026-09-02'));
    }

    #[Test]
    public function it_spaces_a_weekly_rule_out_by_its_interval(): void
    {
        $reminder = $this->ruleOn('2026-08-31', ReminderCadenceEnum::Weekly, interval: 2);

        $this->assertEquals('2026-09-14', $this->next($reminder, '2026-09-02'));
    }

    #[Test]
    public function it_reads_a_date_whose_year_nobody_knows_as_the_day_and_the_month_alone(): void
    {
        $value = FieldValue::factory()->recurringDateWithoutAYear()->create();
        $reminder = Reminder::factory()->for($value, 'fieldValue')->create([
            'account_id' => $value->account_id,
            'vault_id' => $value->vault_id,
            'field_id' => $value->field_id,
            'remindable_id' => $value->valuable_id,
            'cadence' => ReminderCadenceEnum::Yearly,
        ]);

        $this->assertEquals('2027-03-15', $this->next($reminder, '2026-09-02'));
        $this->assertEquals('2026-03-15', $this->next($reminder, '2026-01-01'));
    }

    #[Test]
    public function it_keeps_the_twenty_ninth_of_february_in_a_leap_year_when_nobody_knows_the_year(): void
    {
        $value = FieldValue::factory()->recurringDateWithoutAYear()->create([
            'value_date' => FieldValue::PLACEHOLDER_YEAR.'-02-29',
        ]);
        $reminder = Reminder::factory()->for($value, 'fieldValue')->create([
            'account_id' => $value->account_id,
            'vault_id' => $value->vault_id,
            'field_id' => $value->field_id,
            'remindable_id' => $value->valuable_id,
            'cadence' => ReminderCadenceEnum::Yearly,
        ]);

        $this->assertEquals('2027-02-28', $this->next($reminder, '2026-03-01'));
        $this->assertEquals('2028-02-29', $this->next($reminder, '2027-03-01'));
        $this->assertEquals('2028-02-29', $this->next($reminder, '2028-01-01'));
        $this->assertEquals(['2028-02-29 08:00'], $this->between($reminder, '2027-03-01', '2028-12-31'));
    }

    #[Test]
    public function it_fires_once_on_the_twenty_ninth_of_february_or_the_twenty_eighth_when_nobody_knows_the_year(): void
    {
        $commonYear = $this->ruleWithoutAYear('02-29', ReminderCadenceEnum::Once, scheduleChangedAt: '2027-01-01');
        $leapYear = $this->ruleWithoutAYear('02-29', ReminderCadenceEnum::Once, scheduleChangedAt: '2028-01-01');

        $this->assertEquals('2027-02-28', $this->next($commonYear, '2027-01-01'));
        $this->assertEquals('2028-02-29', $this->next($leapYear, '2028-01-01'));
    }

    #[Test]
    public function it_fires_once_and_never_again_when_nobody_knows_the_year(): void
    {
        $reminder = $this->ruleWithoutAYear('12-20', ReminderCadenceEnum::Once, scheduleChangedAt: '2026-11-12');

        $this->assertSame(['2026-12-20 08:00'], $this->between($reminder, '2026-11-12', '2026-12-31'));
        $this->assertSame([], $this->between($reminder, '2027-01-01', '2027-12-31'));
    }

    #[Test]
    public function it_fires_once_next_year_when_the_day_has_gone_by_the_time_the_rule_is_set(): void
    {
        $reminder = $this->ruleWithoutAYear('12-20', ReminderCadenceEnum::Once, scheduleChangedAt: '2026-12-25');

        $this->assertEquals('2027-12-20', $this->next($reminder, '2026-12-25'));
        $this->assertEquals('2027-12-20', $this->next($reminder, '2027-06-01'));
    }

    #[Test]
    public function it_counts_a_date_without_a_year_from_the_last_change_of_the_schedule_rather_than_from_the_rule(): void
    {
        $reminder = $this->ruleWithoutAYear('03-15', ReminderCadenceEnum::Once, scheduleChangedAt: '2026-06-01');
        $reminder->forceFill(['created_at' => '2025-01-10 10:00:00'])->save();

        $this->assertEquals('2027-03-15', $this->next($reminder->refresh(), '2026-06-01'));
    }

    #[Test]
    public function it_keeps_a_monthly_rhythm_across_the_new_year_when_nobody_knows_the_year(): void
    {
        $reminder = $this->ruleWithoutAYear('12-20', ReminderCadenceEnum::Monthly, scheduleChangedAt: '2026-11-12');

        $this->assertSame(
            ['2027-02-20 08:00', '2027-03-20 08:00', '2027-04-20 07:00'],
            $this->between($reminder, '2027-02-01', '2027-04-30'),
        );
    }

    #[Test]
    public function it_keeps_a_weekly_rhythm_and_its_weekday_across_the_new_year_when_nobody_knows_the_year(): void
    {
        $reminder = $this->ruleWithoutAYear('12-20', ReminderCadenceEnum::Weekly, scheduleChangedAt: '2026-11-12');

        $this->assertSame(
            ['2027-02-14 08:00', '2027-02-21 08:00', '2027-02-28 08:00'],
            $this->between($reminder, '2027-02-10', '2027-03-01'),
        );
    }

    #[Test]
    public function it_says_a_rule_that_fires_once_has_a_day_ahead_of_it_until_that_day_has_gone(): void
    {
        $reminder = $this->ruleOn('2026-12-25', ReminderCadenceEnum::Once);

        $this->assertEquals('2026-12-25', $this->next($reminder, '2026-09-02'));
        $this->assertEquals('2026-12-25', $this->next($reminder, '2026-12-25'));
        $this->assertNull(ReminderSchedule::nextDate($reminder, CarbonImmutable::parse('2026-12-26', 'Europe/Paris')));
    }

    #[Test]
    public function it_has_nothing_ahead_of_it_once_the_value_it_reads_is_gone(): void
    {
        $reminder = $this->ruleOn('1984-02-03', ReminderCadenceEnum::Yearly);

        $reminder->fieldValue->delete();

        $this->assertNull(ReminderSchedule::nextDate($reminder->fresh(), CarbonImmutable::parse('2026-09-02', 'Europe/Paris')));
    }

    #[Test]
    public function it_enumerates_one_deadline_per_cycle_on_the_day_itself(): void
    {
        $reminder = $this->ruleOn('1984-05-12', ReminderCadenceEnum::Yearly);

        $found = $this->between($reminder, '2027-01-01', '2027-12-31');

        $this->assertEquals(['2027-05-12 07:00'], $found);
    }

    #[Test]
    public function it_anchors_a_manual_rule_on_the_date_it_carries(): void
    {
        $contact = Contact::factory()->create();
        $reminder = Reminder::factory()->manual($contact, '2026-11-02')->create([
            'cadence' => ReminderCadenceEnum::Monthly,
            'frequency_interval' => 3,
        ]);

        $this->assertEquals('2026-11-02', $this->next($reminder, '2026-09-26'));
        $this->assertEquals('2027-02-02', $this->next($reminder, '2026-11-03'));
        $this->assertEquals(['2026-11-02 08:00', '2027-02-02 08:00'], $this->between($reminder, '2026-09-26', '2027-03-31'));
    }

    #[Test]
    public function it_enumerates_nothing_for_a_manual_rule_without_a_date(): void
    {
        $reminder = Reminder::factory()->manual(Contact::factory()->create(), '2026-11-02')->create();
        $reminder->starts_on = null;

        $this->assertEquals([], $this->between($reminder, '2026-09-26', '2027-03-31'));
    }

    #[Test]
    public function it_reads_the_hour_in_the_zone_frozen_on_the_rule_and_not_the_reader_one(): void
    {
        $reminder = $this->ruleOn('1984-05-12', ReminderCadenceEnum::Yearly);
        $reminder->update(['timezone' => 'America/New_York']);

        $this->assertEquals(['2027-05-12 13:00'], $this->between($reminder, '2027-01-01', '2027-12-31'));
    }

    #[Test]
    public function it_moves_a_local_time_the_clocks_went_forward_through_past_the_gap(): void
    {
        $reminder = $this->ruleOn('2027-03-28', ReminderCadenceEnum::Yearly);
        $reminder->update(['time_of_day' => '02:30:00']);

        $this->assertEquals(['2027-03-28 01:30'], $this->between($reminder, '2027-03-01', '2027-03-31'));
    }

    #[Test]
    public function it_resolves_a_local_time_that_happens_twice_to_the_later_of_them(): void
    {
        $reminder = $this->ruleOn('2027-10-31', ReminderCadenceEnum::Yearly);
        $reminder->update(['time_of_day' => '02:30:00']);

        $this->assertEquals(['2027-10-31 01:30'], $this->between($reminder, '2027-10-01', '2027-10-31'));
    }

    #[Test]
    public function it_enumerates_every_cycle_a_weekly_rule_falls_on_inside_the_window(): void
    {
        $reminder = $this->ruleOn('2027-01-04', ReminderCadenceEnum::Weekly);

        $this->assertEquals([
            '2027-01-04 08:00',
            '2027-01-11 08:00',
            '2027-01-18 08:00',
        ], $this->between($reminder, '2027-01-01', '2027-01-20'));
    }

    #[Test]
    public function it_never_drifts_off_the_twenty_ninth_of_february_while_enumerating(): void
    {
        $reminder = $this->ruleOn('2024-02-29', ReminderCadenceEnum::Yearly);

        $this->assertEquals(['2028-02-29 08:00'], $this->between($reminder, '2028-01-01', '2028-12-31'));
    }

    #[Test]
    public function it_clamps_a_monthly_rule_anchored_on_the_thirty_first_onto_a_shorter_month(): void
    {
        $reminder = $this->ruleOn('2027-01-31', ReminderCadenceEnum::Monthly);

        $this->assertEquals(['2027-02-28 08:00'], $this->between($reminder, '2027-02-01', '2027-02-28'));
    }

    #[Test]
    public function it_enumerates_a_rule_that_fires_once_exactly_once(): void
    {
        $reminder = $this->ruleOn('2027-05-12', ReminderCadenceEnum::Once);

        $this->assertEquals(['2027-05-12 07:00'], $this->between($reminder, '2027-01-01', '2030-12-31'));
        $this->assertEquals([], $this->between($reminder, '2028-01-01', '2030-12-31'));
    }

    #[Test]
    public function it_enumerates_nothing_for_a_rule_whose_value_has_been_cleared(): void
    {
        $reminder = $this->ruleOn('1984-05-12', ReminderCadenceEnum::Yearly);
        $reminder->fieldValue->delete();
        $reminder->refresh()->load('fieldValue');

        $this->assertEquals([], $this->between($reminder, '2027-01-01', '2027-12-31'));
    }

    #[Test]
    public function it_enumerates_nothing_when_the_window_ends_before_it_starts(): void
    {
        $reminder = $this->ruleOn('1984-05-12', ReminderCadenceEnum::Yearly);

        $this->assertEquals([], $this->between($reminder, '2027-12-31', '2027-01-01'));
    }

    private function ruleOn(string $date, ReminderCadenceEnum $cadence, int $interval = 1): Reminder
    {
        $value = FieldValue::factory()->recurringDate($date)->create();

        return Reminder::factory()->for($value, 'fieldValue')->create([
            'account_id' => $value->account_id,
            'vault_id' => $value->vault_id,
            'field_id' => $value->field_id,
            'remindable_id' => $value->valuable_id,
            'cadence' => $cadence,
            'frequency_interval' => $interval,
        ]);
    }

    private function ruleWithoutAYear(string $monthAndDay, ReminderCadenceEnum $cadence, string $scheduleChangedAt): Reminder
    {
        $value = FieldValue::factory()->recurringDateWithoutAYear()->create([
            'value_date' => FieldValue::PLACEHOLDER_YEAR."-{$monthAndDay}",
        ]);

        return Reminder::factory()->for($value, 'fieldValue')->create([
            'account_id' => $value->account_id,
            'vault_id' => $value->vault_id,
            'field_id' => $value->field_id,
            'remindable_id' => $value->valuable_id,
            'cadence' => $cadence,
            'schedule_changed_at' => CarbonImmutable::parse("{$scheduleChangedAt} 10:00", 'Europe/Paris'),
        ]);
    }

    private function next(Reminder $reminder, string $from): string
    {
        return ReminderSchedule::nextDate($reminder, CarbonImmutable::parse($from, 'Europe/Paris'))->format('Y-m-d');
    }

    /** @return list<string> */
    private function between(Reminder $reminder, string $from, string $to): array
    {
        $occurrences = ReminderSchedule::occurrencesBetween(
            reminder: $reminder,
            from: CarbonImmutable::parse($from, 'UTC')->startOfDay(),
            to: CarbonImmutable::parse($to, 'UTC')->endOfDay(),
        );

        return array_map(
            fn (array $occurrence): string => $occurrence['scheduled_for']->format('Y-m-d H:i'),
            $occurrences,
        );
    }
}
