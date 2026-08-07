<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\TimeFormatEnum;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TimeFormatEnumTest extends TestCase
{
    #[Test]
    public function it_names_every_format(): void
    {
        foreach (TimeFormatEnum::cases() as $format) {
            $this->assertNotEmpty($format->label());
        }
    }

    #[Test]
    public function it_writes_a_time_on_a_twenty_four_hour_clock(): void
    {
        $time = Carbon::create(2026, 7, 31, 14, 30);

        $this->assertEquals('14:30', TimeFormatEnum::TwentyFourHour->format($time));
    }

    #[Test]
    public function it_writes_a_time_on_a_twelve_hour_clock(): void
    {
        $time = Carbon::create(2026, 7, 31, 14, 30);

        $this->assertEquals('2:30 PM', TimeFormatEnum::TwelveHour->format($time));
    }

    #[Test]
    public function it_shows_the_same_afternoon_hour_written_both_ways(): void
    {
        $this->assertEquals('14:00', TimeFormatEnum::TwentyFourHour->example());
        $this->assertEquals('2:00 PM', TimeFormatEnum::TwelveHour->example());
    }
}
