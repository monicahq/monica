<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\RelationshipCategoryEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RelationshipCategoryEnumTest extends TestCase
{
    #[Test]
    public function it_supplies_five_categories(): void
    {
        $this->assertEquals(
            ['family', 'romantic', 'friendship', 'professional', 'other'],
            array_column(RelationshipCategoryEnum::cases(), 'value'),
        );
    }

    #[Test]
    public function every_category_offers_its_name_for_translation(): void
    {
        foreach (RelationshipCategoryEnum::cases() as $case) {
            $this->assertEquals([$case->label()], $case->translationKeys());
            $this->assertNotEmpty($case->label());
        }
    }
}
