<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\Birthday;
use App\Models\FieldValue;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BirthdayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-11-20');
    }

    #[Test]
    public function it_counts_the_age_the_day_before_on_and_after_the_birthday(): void
    {
        $this->assertSame(47, $this->birthday('1978-11-21')->age());
        $this->assertSame(48, $this->birthday('1978-11-20')->age());
        $this->assertSame(48, $this->birthday('1978-11-19')->age());
    }

    #[Test]
    public function it_gives_no_age_when_the_year_or_the_month_is_unknown_or_the_date_is_in_the_future(): void
    {
        $this->assertNull($this->birthday('1978-03-10', yearIsUnknown: true)->age());
        $this->assertNull($this->birthday('1978-03-10', monthIsUnknown: true)->age());
        $this->assertNull($this->birthday('2027-03-10')->age());
    }

    #[Test]
    public function it_finds_the_next_birthday_this_year_next_year_or_today(): void
    {
        $this->assertSame('2026-12-25', $this->birthday('1978-12-25')->next()?->toDateString());
        $this->assertSame('2027-03-10', $this->birthday('1978-03-10')->next()?->toDateString());
        $this->assertSame('2026-11-20', $this->birthday('1978-11-20')->next()?->toDateString());
    }

    #[Test]
    public function it_moves_the_twenty_ninth_of_february_to_the_twenty_eighth_in_a_common_year(): void
    {
        $this->assertSame('2027-02-28', $this->birthday('1980-02-29')->next()?->toDateString());
    }

    #[Test]
    public function it_counts_the_days_until_the_next_birthday(): void
    {
        $this->assertSame(0, $this->birthday('1978-11-20')->daysUntilNext());
        $this->assertSame(1, $this->birthday('1978-11-21')->daysUntilNext());
        $this->assertSame(6, $this->birthday('1978-11-26')->daysUntilNext());
    }

    #[Test]
    public function it_announces_a_birthday_on_the_last_day_of_the_window_and_not_the_day_after(): void
    {
        $this->assertTrue($this->birthday('1978-11-27')->isUpcoming());
        $this->assertFalse($this->birthday('1978-11-28')->isUpcoming());
    }

    #[Test]
    public function it_gives_no_next_birthday_when_the_month_is_unknown(): void
    {
        $birthday = $this->birthday('1978-11-26', monthIsUnknown: true);

        $this->assertNull($birthday->next());
        $this->assertNull($birthday->daysUntilNext());
        $this->assertFalse($birthday->isUpcoming());
    }

    private function birthday(string $date, bool $yearIsUnknown = false, bool $monthIsUnknown = false): Birthday
    {
        $value = new FieldValue;
        $value->forceFill([
            'value_date' => $date,
            'year_is_unknown' => $yearIsUnknown,
            'month_is_unknown' => $monthIsUnknown,
        ]);

        return new Birthday(value: $value, today: CarbonImmutable::now()->startOfDay());
    }
}
