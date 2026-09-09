<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\OfferKindEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OfferKindEnumTest extends TestCase
{
    #[Test]
    public function it_names_describes_and_sells_every_kind(): void
    {
        foreach (OfferKindEnum::cases() as $kind) {
            $this->assertNotEmpty($kind->label());
            $this->assertNotEmpty($kind->description());
            $this->assertNotEmpty($kind->callToAction());
            $this->assertStringContainsString(':price', $kind->priceFormat());
        }
    }

    #[Test]
    public function it_renews_unless_it_is_bought_once(): void
    {
        $this->assertTrue(OfferKindEnum::Monthly->renews());
        $this->assertTrue(OfferKindEnum::Yearly->renews());
        $this->assertFalse(OfferKindEnum::Lifetime->renews());
    }

    #[Test]
    public function it_orders_the_kinds_from_the_shortest_commitment_to_the_longest(): void
    {
        $this->assertTrue(OfferKindEnum::Yearly->isAbove(OfferKindEnum::Monthly));
        $this->assertTrue(OfferKindEnum::Lifetime->isAbove(OfferKindEnum::Yearly));
        $this->assertFalse(OfferKindEnum::Monthly->isAbove(OfferKindEnum::Yearly));
        $this->assertFalse(OfferKindEnum::Monthly->isAbove(OfferKindEnum::Monthly));
    }

    #[Test]
    public function it_knows_how_the_provider_has_to_bill_each_kind(): void
    {
        $this->assertSame('month', OfferKindEnum::Monthly->billingInterval());
        $this->assertSame('year', OfferKindEnum::Yearly->billingInterval());
        $this->assertNull(OfferKindEnum::Lifetime->billingInterval());
    }
}
