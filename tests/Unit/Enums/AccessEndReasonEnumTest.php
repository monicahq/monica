<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\AccessEndReasonEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccessEndReasonEnumTest extends TestCase
{
    #[Test]
    public function it_knows_the_four_ways_full_access_ends(): void
    {
        $this->assertSame(
            ['refunded', 'disputed', 'cancelled', 'payment_failed'],
            array_column(AccessEndReasonEnum::cases(), 'value'),
        );
    }
}
