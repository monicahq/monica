<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\RelationshipBehaviorEnum;
use App\Enums\RelationshipTypeEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RelationshipTypeEnumTest extends TestCase
{
    #[Test]
    public function only_parent_child_feeds_the_family_tree(): void
    {
        $feeding = array_filter(
            RelationshipTypeEnum::cases(),
            fn (RelationshipTypeEnum $case): bool => $case->behavior() === RelationshipBehaviorEnum::GenealogyParentage,
        );

        $this->assertEquals([RelationshipTypeEnum::ParentChild], array_values($feeding));
    }

    #[Test]
    public function three_types_feed_romantic_history(): void
    {
        $feeding = array_filter(
            RelationshipTypeEnum::cases(),
            fn (RelationshipTypeEnum $case): bool => $case->behavior() === RelationshipBehaviorEnum::Romantic,
        );

        $this->assertEquals(
            [RelationshipTypeEnum::Partner, RelationshipTypeEnum::Fiance, RelationshipTypeEnum::Spouse],
            array_values($feeding),
        );
    }

    #[Test]
    public function a_symmetric_type_offers_its_role_for_translation_once(): void
    {
        $this->assertCount(1, RelationshipTypeEnum::Sibling->translationKeys());
        $this->assertCount(3, RelationshipTypeEnum::ParentChild->translationKeys());
    }
}
