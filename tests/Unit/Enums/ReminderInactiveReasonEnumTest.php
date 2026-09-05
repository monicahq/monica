<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\ReminderInactiveReasonEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReminderInactiveReasonEnumTest extends TestCase
{
    #[Test]
    public function it_knows_the_one_reason_somebody_may_lift(): void
    {
        $this->assertTrue(ReminderInactiveReasonEnum::PausedByUser->isLiftedByUser());
    }

    #[Test]
    public function it_knows_the_reasons_nobody_lifts_from_the_reminder(): void
    {
        $this->assertFalse(ReminderInactiveReasonEnum::ValueCleared->isLiftedByUser());
        $this->assertFalse(ReminderInactiveReasonEnum::RecordArchived->isLiftedByUser());
        $this->assertFalse(ReminderInactiveReasonEnum::RecordDeleted->isLiftedByUser());
        $this->assertFalse(ReminderInactiveReasonEnum::FieldRemoved->isLiftedByUser());
    }

    #[Test]
    public function it_lists_its_values(): void
    {
        $this->assertEquals([
            'value_cleared',
            'record_archived',
            'record_deleted',
            'field_removed',
            'paused_by_user',
        ], ReminderInactiveReasonEnum::values());
    }
}
