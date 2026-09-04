<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\ReminderOccurrenceStatusEnum;
use App\Models\ReminderOccurrence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReminderOccurrenceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_account_a_vault_and_a_rule(): void
    {
        $occurrence = ReminderOccurrence::factory()->create();

        $this->assertTrue($occurrence->account()->exists());
        $this->assertTrue($occurrence->vault()->exists());
        $this->assertTrue($occurrence->reminder()->exists());
    }

    #[Test]
    public function it_is_reachable_by_its_rule(): void
    {
        $occurrence = ReminderOccurrence::factory()->create();

        $this->assertTrue($occurrence->reminder->occurrences()->exists());
    }

    #[Test]
    public function it_keeps_the_two_instants_apart(): void
    {
        $occurrence = ReminderOccurrence::factory()->create();

        $this->assertTrue($occurrence->scheduled_for->equalTo($occurrence->original_scheduled_for));
    }

    #[Test]
    public function it_finds_the_deadlines_whose_instant_has_arrived(): void
    {
        $due = ReminderOccurrence::factory()->past()->create();
        $ahead = ReminderOccurrence::factory()->create();

        $found = ReminderOccurrence::query()->due()->pluck('id');

        $this->assertTrue($found->contains($due->id));
        $this->assertFalse($found->contains($ahead->id));
    }

    #[Test]
    public function it_still_delivers_a_deadline_somebody_put_off(): void
    {
        $postponed = ReminderOccurrence::factory()->past()->create([
            'status' => ReminderOccurrenceStatusEnum::Postponed,
        ]);

        $this->assertTrue(ReminderOccurrence::query()->due()->pluck('id')->contains($postponed->id));
    }

    #[Test]
    public function it_does_not_deliver_a_deadline_already_told_about(): void
    {
        $notified = ReminderOccurrence::factory()->past()->notified()->create();

        $this->assertFalse(ReminderOccurrence::query()->due()->pluck('id')->contains($notified->id));
    }

    #[Test]
    public function it_finds_only_the_deadlines_that_have_not_happened_yet(): void
    {
        $ahead = ReminderOccurrence::factory()->create();
        $behind = ReminderOccurrence::factory()->past()->create();

        $found = ReminderOccurrence::query()->stillAhead()->pluck('id');

        $this->assertTrue($found->contains($ahead->id));
        $this->assertFalse($found->contains($behind->id));
    }

    #[Test]
    public function it_says_whether_somebody_may_still_act_on_it(): void
    {
        $pending = ReminderOccurrence::factory()->create();
        $cancelled = ReminderOccurrence::factory()->cancelled()->create();

        $this->assertTrue($pending->isActionable());
        $this->assertFalse($cancelled->isActionable());
    }

    #[Test]
    public function it_refuses_two_deadlines_with_the_same_identity_under_one_rule(): void
    {
        $occurrence = ReminderOccurrence::factory()->create();

        $written = ReminderOccurrence::query()->insertOrIgnore([
            'uuid' => (string) Str::uuid(),
            'account_id' => $occurrence->account_id,
            'vault_id' => $occurrence->vault_id,
            'reminder_id' => $occurrence->reminder_id,
            'source_local_date' => $occurrence->source_local_date->toDateString(),
            'moment_direction' => $occurrence->moment_direction->value,
            'generation_key' => $occurrence->generation_key,
            'scheduled_for' => $occurrence->scheduled_for,
            'original_scheduled_for' => $occurrence->original_scheduled_for,
            'local_date' => $occurrence->local_date->toDateString(),
            'status' => ReminderOccurrenceStatusEnum::Pending->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(0, $written);
        $this->assertSame(1, ReminderOccurrence::query()->where('reminder_id', $occurrence->reminder_id)->count());
    }
}
