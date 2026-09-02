<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderMomentDirectionEnum;
use App\Enums\ReminderMomentUnitEnum;
use App\Helpers\ReminderSchedule;
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
    public function it_counts_every_moment_from_the_day_the_rule_falls_on(): void
    {
        $reminder = $this->ruleOn('1984-03-31', ReminderCadenceEnum::Yearly);
        $reminder->moments = [
            ['direction' => 'before', 'amount' => 7, 'unit' => 'days'],
            ['direction' => 'before', 'amount' => 1, 'unit' => 'months'],
            ['direction' => 'after', 'amount' => 2, 'unit' => 'weeks'],
            ['direction' => 'on', 'amount' => null, 'unit' => null],
        ];
        $reminder->save();

        $moments = ReminderSchedule::momentDates($reminder, CarbonImmutable::parse('2026-09-02', 'Europe/Paris'));

        $this->assertCount(4, $moments);
        $this->assertEquals('2027-03-24', $moments[0]['date']->format('Y-m-d'));
        $this->assertEquals('2027-02-28', $moments[1]['date']->format('Y-m-d'));
        $this->assertEquals('2027-04-14', $moments[2]['date']->format('Y-m-d'));
        $this->assertEquals('2027-03-31', $moments[3]['date']->format('Y-m-d'));
        $this->assertEquals(ReminderMomentDirectionEnum::Before, $moments[0]['direction']);
        $this->assertEquals(ReminderMomentUnitEnum::Days, $moments[0]['unit']);
        $this->assertNull($moments[3]['amount']);
        $this->assertNull($moments[3]['unit']);
    }

    #[Test]
    public function it_still_says_what_a_rule_with_nothing_ahead_of_it_asks_for(): void
    {
        $reminder = $this->ruleOn('2020-12-25', ReminderCadenceEnum::Once);

        $moments = ReminderSchedule::momentDates($reminder, CarbonImmutable::parse('2026-09-02', 'Europe/Paris'));

        $this->assertCount(2, $moments);
        $this->assertNull($moments[0]['date']);
        $this->assertEquals(ReminderMomentDirectionEnum::Before, $moments[0]['direction']);
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

    private function next(Reminder $reminder, string $from): string
    {
        return ReminderSchedule::nextDate($reminder, CarbonImmutable::parse($from, 'Europe/Paris'))->format('Y-m-d');
    }
}
