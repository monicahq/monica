<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Enums\ReminderInactiveReasonEnum;
use App\Jobs\GenerateDueReminderOccurrences;
use App\Models\FieldValue;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GenerateDueReminderOccurrencesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_expands_every_live_rule_it_finds(): void
    {
        CarbonImmutable::setTestNow('2027-05-01 06:00:00');
        $first = $this->rule('1984-05-12');
        $second = $this->rule('1984-05-20');

        new GenerateDueReminderOccurrences()->handle();

        $this->assertSame(2, ReminderOccurrence::query()->where('reminder_id', $first->id)->count());
        $this->assertSame(2, ReminderOccurrence::query()->where('reminder_id', $second->id)->count());
    }

    #[Test]
    public function it_leaves_a_rule_that_has_gone_quiet_alone(): void
    {
        CarbonImmutable::setTestNow('2027-05-01 06:00:00');
        $reminder = $this->rule('1984-05-12');
        $reminder->update([
            'is_active' => false,
            'inactive_reason' => ReminderInactiveReasonEnum::RecordArchived,
        ]);

        new GenerateDueReminderOccurrences()->handle();

        $this->assertDatabaseCount('reminder_occurrences', 0);
    }

    #[Test]
    public function it_writes_nothing_new_when_it_runs_again(): void
    {
        CarbonImmutable::setTestNow('2027-05-01 06:00:00');
        $this->rule('1984-05-12');

        new GenerateDueReminderOccurrences()->handle();
        new GenerateDueReminderOccurrences()->handle();

        $this->assertDatabaseCount('reminder_occurrences', 2);
    }

    private function rule(string $date): Reminder
    {
        $value = FieldValue::factory()->recurringDate($date)->create();

        return Reminder::factory()->for($value, 'fieldValue')->create([
            'account_id' => $value->account_id,
            'vault_id' => $value->vault_id,
            'field_id' => $value->field_id,
            'remindable_id' => $value->valuable_id,
        ]);
    }
}
