<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\RelationshipBehaviorEnum;
use App\Enums\RelationshipCategoryEnum;
use App\Enums\RelationshipTypeEnum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RelationshipTypeEnumTest extends TestCase
{
    #[Test]
    public function it_supplies_ten_types(): void
    {
        $this->assertCount(10, RelationshipTypeEnum::cases());
    }

    #[Test]
    public function a_directed_type_reads_differently_from_each_side(): void
    {
        $case = RelationshipTypeEnum::ParentChild;

        $this->assertEquals('Parent / Child', $case->label());
        $this->assertEquals('Parent', $case->roleA());
        $this->assertEquals('Child', $case->roleB());
        $this->assertFalse($case->isSymmetric());
    }

    #[Test]
    public function a_symmetric_type_reads_the_same_from_both_sides(): void
    {
        $case = RelationshipTypeEnum::Cousin;

        $this->assertEquals('Cousin', $case->roleA());
        $this->assertEquals('Cousin', $case->roleB());
        $this->assertTrue($case->isSymmetric());
    }

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
    public function every_type_names_the_category_it_is_filed_under(): void
    {
        $this->assertEquals(RelationshipCategoryEnum::Family, RelationshipTypeEnum::Sibling->category());
        $this->assertEquals(RelationshipCategoryEnum::Romantic, RelationshipTypeEnum::Partner->category());
        $this->assertEquals(RelationshipCategoryEnum::Friendship, RelationshipTypeEnum::Friend->category());
        $this->assertEquals(RelationshipCategoryEnum::Professional, RelationshipTypeEnum::Colleague->category());
    }

    #[Test]
    public function nothing_is_filed_under_other_to_begin_with(): void
    {
        $filed = array_filter(
            RelationshipTypeEnum::cases(),
            fn (RelationshipTypeEnum $case): bool => $case->category() === RelationshipCategoryEnum::Other,
        );

        $this->assertEmpty($filed);
    }

    #[Test]
    public function a_symmetric_type_offers_its_role_for_translation_once(): void
    {
        $this->assertEquals(['Sibling'], RelationshipTypeEnum::Sibling->translationKeys());
        $this->assertEquals(
            ['Parent / Child', 'Parent', 'Child'],
            RelationshipTypeEnum::ParentChild->translationKeys(),
        );
    }
}
