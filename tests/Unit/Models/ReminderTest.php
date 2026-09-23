<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\ReminderInactiveReasonEnum;
use App\Models\Reminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReminderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_separates_the_rules_still_firing_from_the_ones_that_have_gone_quiet(): void
    {
        $firing = Reminder::factory()->create();
        $quiet = Reminder::factory()->inactive(ReminderInactiveReasonEnum::FieldRemoved)->create();

        $this->assertEquals([$firing->id], Reminder::query()->active()->pluck('id')->all());
        $this->assertEquals([$quiet->id], Reminder::query()->inactive()->pluck('id')->all());
        $this->assertEquals(ReminderInactiveReasonEnum::FieldRemoved, $quiet->inactive_reason);
    }

    #[Test]
    public function it_is_only_ever_removed_softly(): void
    {
        $reminder = Reminder::factory()->create();

        $reminder->delete();

        $this->assertSoftDeleted($reminder);
        $this->assertEquals(0, Reminder::query()->count());
        $this->assertEquals(1, Reminder::query()->withTrashed()->count());
    }

    #[Test]
    public function it_carries_a_public_identifier_that_is_never_its_key(): void
    {
        $reminder = Reminder::factory()->create();

        $this->assertEquals('uuid', $reminder->getRouteKeyName());
        $this->assertNotEquals((string) $reminder->id, $reminder->getRouteKey());
    }
}
