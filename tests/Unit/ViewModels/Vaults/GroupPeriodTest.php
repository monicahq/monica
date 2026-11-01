<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\DatePrecisionEnum;
use App\Models\User;
use App\ViewModels\Vaults\GroupPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GroupPeriodTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_words_each_date_as_precisely_as_it_is_known(): void
    {
        $period = new GroupPeriod(User::factory()->make());

        $this->assertSame('2007', $period->date(CarbonImmutable::parse('2007-03-01'), DatePrecisionEnum::Year));
        $this->assertSame('May 1996', $period->date(CarbonImmutable::parse('1996-05-01'), DatePrecisionEnum::Month));
        $this->assertSame('1960–1969', $period->date(CarbonImmutable::parse('1965-01-01'), DatePrecisionEnum::Decade));
        $this->assertNull($period->date(null, DatePrecisionEnum::Unknown));
    }

    #[Test]
    public function it_words_a_span_known_less_precisely_than_a_day_on_its_own(): void
    {
        $period = new GroupPeriod(User::factory()->make());

        $this->assertSame('Between 1995 and May 1996', $period->between(
            CarbonImmutable::parse('1995-01-01'),
            DatePrecisionEnum::Year,
            CarbonImmutable::parse('1996-05-01'),
            DatePrecisionEnum::Month,
        ));
        $this->assertSame('Since 1994', $period->between(CarbonImmutable::parse('1994-01-01'), DatePrecisionEnum::Year, null, DatePrecisionEnum::Unknown));
        $this->assertNull($period->between(null, DatePrecisionEnum::Unknown, null, DatePrecisionEnum::Unknown));
    }

    #[Test]
    public function it_keeps_the_article_a_day_takes_in_french(): void
    {
        app()->setLocale('fr_FR');
        $period = new GroupPeriod(User::factory()->make());

        $this->assertSame('Depuis 1994', $period->between(CarbonImmutable::parse('1994-01-01'), DatePrecisionEnum::Year, null, DatePrecisionEnum::Unknown));
        $this->assertStringStartsWith('Depuis le ', (string) $period->between(CarbonImmutable::parse('1994-09-22'), DatePrecisionEnum::Day, null, DatePrecisionEnum::Unknown));
    }
}
