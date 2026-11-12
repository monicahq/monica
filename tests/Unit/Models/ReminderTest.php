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
}
