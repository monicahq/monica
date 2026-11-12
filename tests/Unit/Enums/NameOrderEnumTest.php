<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\NameOrderEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NameOrderEnumTest extends TestCase
{
    #[Test]
    public function it_writes_a_name_given_name_first(): void
    {
        $this->assertEquals('Michael Scott', NameOrderEnum::GivenFirst->format('Michael', 'Scott'));
    }

    #[Test]
    public function it_writes_a_name_family_name_first(): void
    {
        $this->assertEquals('Scott Michael', NameOrderEnum::FamilyFirst->format('Michael', 'Scott'));
    }

    #[Test]
    public function it_leaves_no_trailing_space_when_a_part_of_the_name_is_missing(): void
    {
        $this->assertEquals('Dwight', NameOrderEnum::GivenFirst->format('Dwight', ''));
        $this->assertEquals('Dwight', NameOrderEnum::FamilyFirst->format('Dwight', ''));
    }
}
