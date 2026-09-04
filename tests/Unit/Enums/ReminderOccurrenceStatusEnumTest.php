<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\ReminderOccurrenceStatusEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReminderOccurrenceStatusEnumTest extends TestCase
{
    #[Test]
    public function it_knows_which_states_can_still_be_acted_on(): void
    {
        $this->assertTrue(ReminderOccurrenceStatusEnum::Pending->isActionable());
        $this->assertTrue(ReminderOccurrenceStatusEnum::Notified->isActionable());
        $this->assertTrue(ReminderOccurrenceStatusEnum::Postponed->isActionable());
        $this->assertFalse(ReminderOccurrenceStatusEnum::Dismissed->isActionable());
        $this->assertFalse(ReminderOccurrenceStatusEnum::Cancelled->isActionable());
    }

    #[Test]
    public function it_treats_everything_that_cannot_be_acted_on_as_finished(): void
    {
        foreach (ReminderOccurrenceStatusEnum::cases() as $case) {
            $this->assertSame(! $case->isActionable(), $case->isTerminal());
        }
    }

    #[Test]
    public function it_still_delivers_a_deadline_somebody_moved(): void
    {
        $this->assertTrue(ReminderOccurrenceStatusEnum::Pending->awaitsDelivery());
        $this->assertTrue(ReminderOccurrenceStatusEnum::Postponed->awaitsDelivery());
    }

    #[Test]
    public function it_does_not_deliver_a_deadline_already_told_about_or_finished_with(): void
    {
        $this->assertFalse(ReminderOccurrenceStatusEnum::Notified->awaitsDelivery());
        $this->assertFalse(ReminderOccurrenceStatusEnum::Dismissed->awaitsDelivery());
        $this->assertFalse(ReminderOccurrenceStatusEnum::Cancelled->awaitsDelivery());
    }

    #[Test]
    public function it_lists_the_states_delivery_reads(): void
    {
        $this->assertEquals(['pending', 'postponed'], ReminderOccurrenceStatusEnum::awaitingDelivery());
    }

    #[Test]
    public function it_lists_its_values(): void
    {
        $this->assertEquals([
            'pending',
            'notified',
            'postponed',
            'dismissed',
            'cancelled',
        ], ReminderOccurrenceStatusEnum::values());
    }
}
