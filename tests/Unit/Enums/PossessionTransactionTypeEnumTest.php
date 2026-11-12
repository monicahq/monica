<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\PossessionTransactionTypeEnum;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PossessionTransactionTypeEnumTest extends TestCase
{
    #[Test]
    public function only_four_types_bring_the_possession_in(): void
    {
        $acquisitions = array_filter(PossessionTransactionTypeEnum::cases(), fn (PossessionTransactionTypeEnum $type): bool => $type->isAcquisition());

        $this->assertEquals(
            [PossessionTransactionTypeEnum::Purchase, PossessionTransactionTypeEnum::Exchange, PossessionTransactionTypeEnum::GiftReceived, PossessionTransactionTypeEnum::Inheritance],
            array_values($acquisitions),
        );
    }

    #[Test]
    public function the_first_six_types_change_ownership_and_the_others_move_money_only(): void
    {
        $this->assertTrue(PossessionTransactionTypeEnum::Sale->changesOwnership());
        $this->assertTrue(PossessionTransactionTypeEnum::GiftGiven->changesOwnership());
        $this->assertFalse(PossessionTransactionTypeEnum::Refund->changesOwnership());
        $this->assertFalse(PossessionTransactionTypeEnum::Shipping->changesOwnership());
    }
}
