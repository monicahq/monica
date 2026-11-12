<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\PossessionProvenanceDatePrecisionEnum;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PossessionProvenanceDatePrecisionEnumTest extends TestCase
{
    /** @return array<string, array{PossessionProvenanceDatePrecisionEnum, string, string}> */
    public static function writtenDates(): array
    {
        return [
            'a day' => [PossessionProvenanceDatePrecisionEnum::Day, '2005-03-24', '2005-03-24'],
            'a month' => [PossessionProvenanceDatePrecisionEnum::Month, '2005-03', '2005-03-01'],
            'a year' => [PossessionProvenanceDatePrecisionEnum::Year, '1921', '1921-01-01'],
            'around a year' => [PossessionProvenanceDatePrecisionEnum::Circa, ' 1975 ', '1975-01-01'],
        ];
    }

    /** @return array<string, array{PossessionProvenanceDatePrecisionEnum, string}> */
    public static function mismatchedDates(): array
    {
        return [
            'a month for a day' => [PossessionProvenanceDatePrecisionEnum::Day, '2005-03'],
            'an impossible day' => [PossessionProvenanceDatePrecisionEnum::Day, '2005-02-30'],
            'a day for a month' => [PossessionProvenanceDatePrecisionEnum::Month, '2005-03-24'],
            'an impossible month' => [PossessionProvenanceDatePrecisionEnum::Month, '2005-13'],
            'words for a year' => [PossessionProvenanceDatePrecisionEnum::Year, 'the twenties'],
            'a day for around a year' => [PossessionProvenanceDatePrecisionEnum::Circa, '1975-01-01'],
        ];
    }

    #[Test]
    #[DataProvider('writtenDates')]
    public function it_reads_a_date_in_the_shape_of_its_precision_and_writes_it_back(PossessionProvenanceDatePrecisionEnum $precision, string $written, string $stored): void
    {
        $date = $precision->parse($written);

        $this->assertNotFalse($date);
        $this->assertEquals($stored, $date?->toDateString());
        $this->assertEquals(mb_trim($written), $precision->format($date));
    }

    #[Test]
    #[DataProvider('mismatchedDates')]
    public function it_refuses_a_date_that_does_not_match_its_precision(PossessionProvenanceDatePrecisionEnum $precision, string $written): void
    {
        $this->assertFalse($precision->parse($written));
    }

    #[Test]
    public function it_reads_a_year_before_1000_written_without_leading_zeros(): void
    {
        $year = PossessionProvenanceDatePrecisionEnum::Year->parse('975');
        $circa = PossessionProvenanceDatePrecisionEnum::Circa->parse('975');
        $month = PossessionProvenanceDatePrecisionEnum::Month->parse('975-03');

        $this->assertNotFalse($year);
        $this->assertNotFalse($circa);
        $this->assertNotFalse($month);
        $this->assertEquals('0975-01-01', $year?->toDateString());
        $this->assertEquals('0975-01-01', $circa?->toDateString());
        $this->assertEquals('0975-03-01', $month?->toDateString());
        $this->assertEquals('0975', PossessionProvenanceDatePrecisionEnum::Year->format($year));
    }

    #[Test]
    public function an_empty_date_and_an_unknown_one_are_null(): void
    {
        $this->assertNull(PossessionProvenanceDatePrecisionEnum::Day->parse('  '));
        $this->assertNull(PossessionProvenanceDatePrecisionEnum::Unknown->parse('1962'));
        $this->assertNull(PossessionProvenanceDatePrecisionEnum::Unknown->format(null));
    }
}
