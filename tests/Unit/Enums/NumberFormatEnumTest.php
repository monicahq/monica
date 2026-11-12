<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\NumberFormatEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NumberFormatEnumTest extends TestCase
{
    #[Test]
    public function it_writes_a_number_with_every_set_of_separators(): void
    {
        $this->assertEquals('1,234.56', NumberFormatEnum::CommaAndDot->format(1234.56));
        $this->assertEquals('1.234,56', NumberFormatEnum::DotAndComma->format(1234.56));
        $this->assertEquals("1\u{00A0}234,56", NumberFormatEnum::SpaceAndComma->format(1234.56));
        $this->assertEquals('1234.56', NumberFormatEnum::NoSeparator->format(1234.56));
    }

    #[Test]
    public function it_writes_a_number_without_decimals_when_asked(): void
    {
        $this->assertEquals('1,235', NumberFormatEnum::CommaAndDot->format(1234.56, decimals: 0));
        $this->assertEquals('42', NumberFormatEnum::NoSeparator->format(42, decimals: 0));
    }

    #[Test]
    public function it_never_lets_a_number_break_across_two_lines(): void
    {
        $this->assertStringNotContainsString(' ', NumberFormatEnum::SpaceAndComma->format(1234.56));
    }
}
