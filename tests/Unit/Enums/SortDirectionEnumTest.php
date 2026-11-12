<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\SortDirectionEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SortDirectionEnumTest extends TestCase
{
    #[Test]
    public function it_reads_a_direction_somebody_asked_for(): void
    {
        $this->assertEquals(SortDirectionEnum::Descending, SortDirectionEnum::fromValueOrAscending('desc'));
        $this->assertEquals(SortDirectionEnum::Ascending, SortDirectionEnum::fromValueOrAscending('asc'));
    }

    #[Test]
    public function it_runs_upwards_when_nobody_asked_for_a_direction(): void
    {
        $this->assertEquals(SortDirectionEnum::Ascending, SortDirectionEnum::fromValueOrAscending(null));
        $this->assertEquals(SortDirectionEnum::Ascending, SortDirectionEnum::fromValueOrAscending(''));
        $this->assertEquals(SortDirectionEnum::Ascending, SortDirectionEnum::fromValueOrAscending('sideways'));
    }
}
