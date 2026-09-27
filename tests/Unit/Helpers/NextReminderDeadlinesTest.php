<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Actions\GenerateReminderOccurrences;
use App\Enums\ReminderCadenceEnum;
use App\Helpers\NextReminderDeadlines;
use App\Models\Contact;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NextReminderDeadlinesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_one_date_per_reminder_the_soonest_first(): void
    {
        $this->travelTo('2026-09-26 08:00:00');
        $contact = Contact::factory()->create();
        $tulsa = $this->manual($contact, 'Ask about Tulsa', '2026-11-02', ReminderCadenceEnum::Once);
        $flat = $this->manual($contact, 'Call about the flat on Bedford Street', '2026-10-01', ReminderCadenceEnum::Monthly);

        $next = NextReminderDeadlines::for(Reminder::query()->get(), CarbonImmutable::parse('2026-09-26'));

        $this->assertSame([$flat->id, $tulsa->id], $next->pluck('reminder_id')->all());
        $this->assertSame(['2026-10-01', '2026-11-02'], $next->map(fn (ReminderOccurrence $date): string => $date->local_date->toDateString())->all());
    }

    #[Test]
    public function it_takes_the_generated_deadline_when_there_is_one(): void
    {
        $this->travelTo('2026-09-26 08:00:00');
        $flat = $this->manual(Contact::factory()->create(), 'Call about the flat on Bedford Street', '2026-10-01', ReminderCadenceEnum::Monthly);
        $generated = ReminderOccurrence::query()->where('reminder_id', $flat->id)->orderBy('local_date')->firstOrFail();

        $next = NextReminderDeadlines::for(Reminder::query()->get(), CarbonImmutable::parse('2026-09-26'));

        $this->assertTrue($next->first()->exists);
        $this->assertSame($generated->id, $next->first()->id);
    }

    #[Test]
    public function it_builds_an_unsaved_deadline_for_a_date_beyond_the_window(): void
    {
        $this->travelTo('2026-09-26 08:00:00');
        $birthday = $this->manual(Contact::factory()->create(), 'Chandler’s birthday', '2027-04-08', ReminderCadenceEnum::Yearly);

        $next = NextReminderDeadlines::for(Reminder::query()->get(), CarbonImmutable::parse('2026-09-26'));

        $this->assertCount(1, $next);
        $this->assertFalse($next->first()->exists);
        $this->assertSame('2027-04-08', $next->first()->local_date->toDateString());
        $this->assertSame($birthday->id, $next->first()->reminder->id);
    }

    #[Test]
    public function it_gives_nothing_for_a_reminder_that_happened_once_and_is_behind(): void
    {
        $this->travelTo('2026-09-26 08:00:00');
        $contact = Contact::factory()->create();
        $past = Reminder::factory()->manual($contact, '2026-09-20')->once()->create();

        $next = NextReminderDeadlines::for(Reminder::query()->whereKey($past->id)->get(), CarbonImmutable::parse('2026-09-26'));

        $this->assertCount(0, $next);
    }

    #[Test]
    public function it_skips_a_generated_deadline_somebody_already_answered(): void
    {
        $this->travelTo('2026-09-26 08:00:00');
        $flat = $this->manual(Contact::factory()->create(), 'Call about the flat on Bedford Street', '2026-10-01', ReminderCadenceEnum::Monthly);
        ReminderOccurrence::query()->where('reminder_id', $flat->id)->whereDate('local_date', '2026-10-01')->update(['answer' => 'remembered', 'answered_at' => now()]);

        $next = NextReminderDeadlines::for(Reminder::query()->get(), CarbonImmutable::parse('2026-09-26'));

        $this->assertSame('2026-11-01', $next->first()->local_date->toDateString());
    }

    private function manual(Contact $contact, string $name, string $startsOn, ReminderCadenceEnum $cadence): Reminder
    {
        $reminder = Reminder::factory()->manual($contact, $startsOn)->create([
            'name' => $name,
            'cadence' => $cadence,
            'timezone' => 'UTC',
        ]);

        new GenerateReminderOccurrences(reminder: $reminder)->execute();

        return $reminder;
    }
}
