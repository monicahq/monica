<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderMomentDirectionEnum;
use App\Enums\ReminderMomentUnitEnum;
use App\ViewModels\Vaults\ReminderWords;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReminderWordsTest extends TestCase
{
    #[Test]
    public function it_says_the_day_itself(): void
    {
        $this->assertSame(
            'on the day',
            ReminderWords::moment(ReminderMomentDirectionEnum::On, null, null),
        );
    }

    #[Test]
    public function it_says_a_span_before_the_date(): void
    {
        $this->assertSame(
            '7 days before',
            ReminderWords::moment(ReminderMomentDirectionEnum::Before, 7, ReminderMomentUnitEnum::Days),
        );
    }

    #[Test]
    public function it_says_a_span_after_the_date(): void
    {
        $this->assertSame(
            '2 weeks after',
            ReminderWords::moment(ReminderMomentDirectionEnum::After, 2, ReminderMomentUnitEnum::Weeks),
        );
    }

    #[Test]
    public function it_says_a_single_unit_without_reading_as_a_plural(): void
    {
        $this->assertSame(
            '1 month before',
            ReminderWords::moment(ReminderMomentDirectionEnum::Before, 1, ReminderMomentUnitEnum::Months),
        );
    }

    #[Test]
    public function it_falls_back_to_the_day_itself_when_the_span_is_missing(): void
    {
        $this->assertSame(
            'on the day',
            ReminderWords::moment(ReminderMomentDirectionEnum::Before, null, null),
        );
    }

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
