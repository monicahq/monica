<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\DateFormatEnum;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DateFormatEnumTest extends TestCase
{
    #[Test]
    public function it_writes_a_date_in_every_order(): void
    {
        $date = Carbon::create(2026, 3, 21);

        $this->assertEquals('21/03/2026', DateFormatEnum::DayFirst->format($date));
        $this->assertEquals('03/21/2026', DateFormatEnum::MonthFirst->format($date));
        $this->assertEquals('2026-03-21', DateFormatEnum::YearFirst->format($date));
        $this->assertEquals('21 March 2026', DateFormatEnum::MonthSpelled->format($date));
    }

    #[Test]
    public function it_spells_the_month_in_the_language_being_read(): void
    {
        app()->setLocale('fr_FR');

        $date = Carbon::create(2026, 3, 21);

        $this->assertEquals('21 mars 2026', DateFormatEnum::MonthSpelled->format($date));
    }

    #[Test]
    public function it_writes_a_date_in_a_format_nobody_chose(): void
    {
        $date = Carbon::create(2026, 3, 21);

        $this->assertEquals('21.03.2026', DateFormatEnum::write($date, 'd.m.Y'));
    }
}
