<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\ReminderCadenceEnum;
use App\ViewModels\Vaults\ReminderWords;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReminderWordsTest extends TestCase
{
    #[Test]
    public function it_says_a_rule_that_fires_once(): void
    {
        $this->assertSame('once and no more', ReminderWords::cadence(ReminderCadenceEnum::Once, 1));
    }

    #[Test]
    public function it_spells_a_rhythm_of_one_as_a_word(): void
    {
        $this->assertSame('every year', ReminderWords::cadence(ReminderCadenceEnum::Yearly, 1));
        $this->assertSame('every month', ReminderWords::cadence(ReminderCadenceEnum::Monthly, 1));
        $this->assertSame('every week', ReminderWords::cadence(ReminderCadenceEnum::Weekly, 1));
    }

    #[Test]
    public function it_counts_a_rhythm_somebody_spaced_out(): void
    {
        $this->assertSame('every 3 years', ReminderWords::cadence(ReminderCadenceEnum::Yearly, 3));
        $this->assertSame('every 2 months', ReminderWords::cadence(ReminderCadenceEnum::Monthly, 2));
    }

    #[Test]
    public function it_says_nothing_for_a_rhythm_of_none(): void
    {
        $this->assertSame('', ReminderWords::cadence(ReminderCadenceEnum::Yearly, 0));
    }
}
