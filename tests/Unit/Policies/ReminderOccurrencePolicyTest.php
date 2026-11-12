<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\ReminderOccurrence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReminderOccurrencePolicyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_allows_remember_from_the_day_itself(): void
    {
        $this->travelTo('2026-09-26 18:00:00');
        $user = User::factory()->create(['timezone' => 'UTC']);

        $this->assertTrue(Gate::forUser($user)->allows('remember', $this->dueOn('2026-09-26')));
        $this->assertTrue(Gate::forUser($user)->allows('remember', $this->dueOn('2026-09-20')));
    }

    #[Test]
    public function it_refuses_remember_before_the_day(): void
    {
        $this->travelTo('2026-09-26 18:00:00');
        $user = User::factory()->create(['timezone' => 'UTC']);

        $response = Gate::forUser($user)->inspect('remember', $this->dueOn('2026-09-27'));

        $this->assertTrue($response->denied());
    }

    #[Test]
    public function it_allows_forget_from_the_day_after(): void
    {
        $this->travelTo('2026-09-26 00:30:00');
        $user = User::factory()->create(['timezone' => 'UTC']);

        $this->assertTrue(Gate::forUser($user)->allows('forget', $this->dueOn('2026-09-25')));
    }

    #[Test]
    public function it_refuses_forget_on_the_day_itself(): void
    {
        $this->travelTo('2026-09-26 23:30:00');
        $user = User::factory()->create(['timezone' => 'UTC']);

        $response = Gate::forUser($user)->inspect('forget', $this->dueOn('2026-09-26'));

        $this->assertTrue($response->denied());
    }

    #[Test]
    public function it_reads_the_day_in_the_time_zone_of_the_user(): void
    {
        $this->travelTo('2026-09-27 02:00:00');
        $inParis = User::factory()->create(['timezone' => 'Europe/Paris']);
        $inNewYork = User::factory()->create(['timezone' => 'America/New_York']);
        $occurrence = $this->dueOn('2026-09-26');

        $this->assertTrue(Gate::forUser($inParis)->allows('forget', $occurrence));
        $this->assertFalse(Gate::forUser($inNewYork)->allows('forget', $occurrence));
        $this->assertTrue(Gate::forUser($inNewYork)->allows('remember', $occurrence));
    }

    #[Test]
    public function it_refuses_both_answers_when_nobody_is_signed_in(): void
    {
        $this->travelTo('2026-09-26 12:00:00');
        $occurrence = $this->dueOn('2026-09-20');

        $this->assertFalse(Gate::forUser(null)->allows('remember', $occurrence));
        $this->assertFalse(Gate::forUser(null)->allows('forget', $occurrence));
    }

    #[Test]
    public function it_refuses_both_answers_on_a_date_not_generated_yet(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $unsaved = new ReminderOccurrence(['local_date' => now()->subDay()->toDateString()]);

        $this->assertFalse($user->can('remember', $unsaved));
        $this->assertFalse($user->can('forget', $unsaved));
    }

    /** @param  array<string, mixed>  $attributes */
    private function dueOn(string $day, array $attributes = []): ReminderOccurrence
    {
        return ReminderOccurrence::factory()->create([
            'local_date' => $day,
            'generation_key' => '1:'.$day,
            'scheduled_for' => $day.' 09:00:00',
            ...$attributes,
        ]);
    }
}
