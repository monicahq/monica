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
    public function it_keeps_only_the_rules_still_firing(): void
    {
        $firing = Reminder::factory()->create();
        Reminder::factory()->inactive(ReminderInactiveReasonEnum::FieldRemoved)->create();

        $this->assertEquals([$firing->id], Reminder::query()->active()->pluck('id')->all());
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
