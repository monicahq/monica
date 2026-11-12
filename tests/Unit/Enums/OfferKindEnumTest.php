<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\OfferKindEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OfferKindEnumTest extends TestCase
{
    #[Test]
    public function it_orders_the_kinds_from_the_shortest_commitment_to_the_longest(): void
    {
        $this->assertTrue(OfferKindEnum::Yearly->isAbove(OfferKindEnum::Monthly));
        $this->assertTrue(OfferKindEnum::Lifetime->isAbove(OfferKindEnum::Yearly));
        $this->assertFalse(OfferKindEnum::Monthly->isAbove(OfferKindEnum::Yearly));
        $this->assertFalse(OfferKindEnum::Monthly->isAbove(OfferKindEnum::Monthly));
    }
}
