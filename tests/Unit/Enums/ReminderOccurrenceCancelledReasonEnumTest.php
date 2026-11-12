<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\ReminderOccurrenceCancelledReasonEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReminderOccurrenceCancelledReasonEnumTest extends TestCase
{
    #[Test]
    public function it_knows_which_cancellations_come_back(): void
    {
        $this->assertTrue(ReminderOccurrenceCancelledReasonEnum::RuleDeleted->isReversible());
        $this->assertTrue(ReminderOccurrenceCancelledReasonEnum::RecordArchived->isReversible());
        $this->assertTrue(ReminderOccurrenceCancelledReasonEnum::RecordDeleted->isReversible());
        $this->assertTrue(ReminderOccurrenceCancelledReasonEnum::FieldRemoved->isReversible());
    }

    #[Test]
    public function it_knows_which_cancellations_are_replaced_rather_than_reversed(): void
    {
        $this->assertFalse(ReminderOccurrenceCancelledReasonEnum::ValueCleared->isReversible());
        $this->assertFalse(ReminderOccurrenceCancelledReasonEnum::SourceChanged->isReversible());
        $this->assertFalse(ReminderOccurrenceCancelledReasonEnum::RuleChanged->isReversible());
    }

    #[Test]
    public function it_matches_every_reason_a_whole_rule_can_go_quiet_for(): void
    {
        $this->assertSame(
            ReminderOccurrenceCancelledReasonEnum::ValueCleared,
            ReminderOccurrenceCancelledReasonEnum::forInactiveReason(ReminderInactiveReasonEnum::ValueCleared),
        );
        $this->assertSame(
            ReminderOccurrenceCancelledReasonEnum::RecordArchived,
            ReminderOccurrenceCancelledReasonEnum::forInactiveReason(ReminderInactiveReasonEnum::RecordArchived),
        );
        $this->assertSame(
            ReminderOccurrenceCancelledReasonEnum::RecordDeleted,
            ReminderOccurrenceCancelledReasonEnum::forInactiveReason(ReminderInactiveReasonEnum::RecordDeleted),
        );
        $this->assertSame(
            ReminderOccurrenceCancelledReasonEnum::FieldRemoved,
            ReminderOccurrenceCancelledReasonEnum::forInactiveReason(ReminderInactiveReasonEnum::FieldRemoved),
        );
    }
}
