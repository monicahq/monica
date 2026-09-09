<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\AccessKindEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccessKindEnumTest extends TestCase
{
    #[Test]
    public function it_names_every_way_full_access_is_held(): void
    {
        foreach (AccessKindEnum::cases() as $kind) {
            $this->assertNotEmpty($kind->label());
        }

        $this->assertSame(['lifetime', 'subscription', 'granted'], array_column(AccessKindEnum::cases(), 'value'));
    }
}
