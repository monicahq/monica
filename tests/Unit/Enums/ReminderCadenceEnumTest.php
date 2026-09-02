<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\ReminderCadenceEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReminderCadenceEnumTest extends TestCase
{
    #[Test]
    public function it_knows_which_cadences_come_round_again(): void
    {
        $this->assertFalse(ReminderCadenceEnum::Once->recurs());
        $this->assertTrue(ReminderCadenceEnum::Weekly->recurs());
        $this->assertTrue(ReminderCadenceEnum::Monthly->recurs());
        $this->assertTrue(ReminderCadenceEnum::Yearly->recurs());
    }

    #[Test]
    public function it_offers_only_the_recurring_cadences_once_somebody_says_it_comes_back(): void
    {
        $this->assertEquals([
            ReminderCadenceEnum::Weekly,
            ReminderCadenceEnum::Monthly,
            ReminderCadenceEnum::Yearly,
        ], ReminderCadenceEnum::recurring());
    }

    #[Test]
    public function it_lists_its_values(): void
    {
        $this->assertEquals(['once', 'weekly', 'monthly', 'yearly'], ReminderCadenceEnum::values());
    }

    #[Test]
    public function it_carries_a_translatable_label_for_every_case(): void
    {
        foreach (ReminderCadenceEnum::cases() as $case) {
            $this->assertEquals([$case->label()], $case->translationKeys());
            $this->assertNotEmpty($case->label());
        }
    }
}
