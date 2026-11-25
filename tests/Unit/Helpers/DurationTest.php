<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\Duration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DurationTest extends TestCase
{
    /** @return array<string, array{int, string}> */
    public static function durations(): array
    {
        return [
            'a minute' => [1, '1 minute'],
            'an hour and a half' => [90, '1 hour 30 minutes'],
            'a week' => [10080, '1 week'],
            'about three months' => [131400, '13 weeks 6 hours'],
            'a year' => [Duration::LONGEST_IN_MINUTES, '52 weeks 1 day'],
        ];
    }

    #[Test]
    #[DataProvider('durations')]
    public function it_reads_a_duration_in_the_largest_units_that_fit(int $minutes, string $wording): void
    {
        $this->assertSame($wording, Duration::wording($minutes));
    }
}
