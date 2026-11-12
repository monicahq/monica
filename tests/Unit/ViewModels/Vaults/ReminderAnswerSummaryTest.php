<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\ReminderAnswerEnum;
use App\Enums\ReminderOccurrenceCancelledReasonEnum;
use App\Models\Contact;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\ViewModels\Vaults\ReminderAnswerSummary;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReminderAnswerSummaryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Contact $dwight;

    private Reminder $rule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-09-26 12:00:00', 'UTC'));

        $this->user = User::factory()->create(['timezone' => 'UTC']);
        $this->dwight = Contact::factory()->create(['account_id' => $this->user->account_id, 'first_name' => 'Dwight']);
        $this->rule = Reminder::factory()->manual($this->dwight, '2025-10-01')->create(['timezone' => 'UTC']);
    }

    #[Test]
    public function it_gives_the_share_remembered_forgotten_and_still_waiting(): void
    {
        $this->date('2026-01-10', ReminderAnswerEnum::Remembered);
        $this->date('2026-03-10', ReminderAnswerEnum::Remembered);
        $this->date('2026-05-10', ReminderAnswerEnum::Forgotten);
        $this->date('2026-09-20');

        $summary = $this->summary();

        $this->assertSame(4, $summary->total());
        $this->assertSame(50, $summary->rememberedPercentage());
        $this->assertSame(25, $summary->forgottenPercentage());
        $this->assertSame(25, $summary->waitingPercentage());
    }

    #[Test]
    public function it_never_reads_a_negative_share_when_rounding_goes_over(): void
    {
        $this->date('2026-01-10', ReminderAnswerEnum::Remembered);

        foreach (['2026-02-10', '2026-03-10', '2026-04-10', '2026-05-10', '2026-06-10', '2026-07-10', '2026-08-10'] as $day) {
            $this->date($day, ReminderAnswerEnum::Forgotten);
        }

        $this->assertSame(13, $this->summary()->rememberedPercentage());
        $this->assertSame(88, $this->summary()->forgottenPercentage());
        $this->assertSame(0, $this->summary()->waitingPercentage());
    }

    #[Test]
    public function a_cancelled_date_counts_for_nothing(): void
    {
        $this->date('2026-05-10', ReminderAnswerEnum::Remembered);
        $this->date('2026-06-10')->update(['cancelled_at' => now(), 'cancelled_reason' => ReminderOccurrenceCancelledReasonEnum::RecordArchived]);

        $this->assertSame(1, $this->summary()->total());
        $this->assertSame(100, $this->summary()->rememberedPercentage());
    }

    #[Test]
    public function it_reads_only_the_last_twelve_months_up_to_today(): void
    {
        $this->date('2025-09-20', ReminderAnswerEnum::Forgotten);
        $this->date('2026-09-26', ReminderAnswerEnum::Remembered);
        $this->date('2026-10-05');

        $this->assertSame(1, $this->summary()->total());
    }

    #[Test]
    public function a_contact_with_no_dates_has_nothing_to_summarise(): void
    {
        $summary = $this->summary();

        $this->assertFalse($summary->hasAny());
        $this->assertSame(0, $summary->rememberedPercentage());
        $this->assertSame(0, $summary->waitingPercentage());
    }

    private function summary(): ReminderAnswerSummary
    {
        return new ReminderAnswerSummary(user: $this->user, contact: $this->dwight);
    }

    private function date(string $day, ?ReminderAnswerEnum $answer = null): ReminderOccurrence
    {
        return ReminderOccurrence::factory()->for($this->rule)->create([
            'generation_key' => '1:'.$day,
            'scheduled_for' => CarbonImmutable::parse("{$day} 09:00:00", 'UTC'),
            'local_date' => $day,
            'answer' => $answer,
            'answered_at' => $answer === null ? null : now(),
        ]);
    }
}
