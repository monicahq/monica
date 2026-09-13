<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\RelationshipTypeEnum;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\User;
use App\ViewModels\Vaults\RelationshipTypeChoices;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RelationshipTypeChoicesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_groups_the_types_under_their_categories_in_the_order_the_account_set(): void
    {
        $user = User::factory()->create();
        $work = $this->categoryOf($user, 'Professional', 2);
        $family = $this->categoryOf($user, 'Family', 1);

        $this->typeOf($user, RelationshipTypeEnum::Colleague, $work);
        $this->typeOf($user, RelationshipTypeEnum::ParentChild, $family);

        $groups = new RelationshipTypeChoices(accountId: $user->account_id)->groups();

        $this->assertCount(2, $groups);
        $this->assertSame('Family', $groups[0]['label']);
        $this->assertSame('Professional', $groups[1]['label']);
    }

    #[Test]
    public function it_drops_a_category_holding_no_types(): void
    {
        $user = User::factory()->create();
        $family = $this->categoryOf($user, 'Family', 1);
        $this->categoryOf($user, 'Professional', 2);
        $this->typeOf($user, RelationshipTypeEnum::ParentChild, $family);

        $groups = new RelationshipTypeChoices(accountId: $user->account_id)->groups();

        $this->assertCount(1, $groups);
        $this->assertSame('Family', $groups[0]['label']);
    }

    #[Test]
    public function it_does_not_offer_a_withdrawn_type(): void
    {
        $user = User::factory()->create();
        $family = $this->categoryOf($user, 'Family', 1);
        $this->typeOf($user, RelationshipTypeEnum::ParentChild, $family);
        $sibling = $this->typeOf($user, RelationshipTypeEnum::Sibling, $family);
        $sibling->update(['is_active' => false]);

        $choices = new RelationshipTypeChoices(accountId: $user->account_id);

        $this->assertArrayNotHasKey($sibling->id, $choices->map());
    }

    #[Test]
    public function it_keeps_a_withdrawn_type_when_it_is_the_one_a_link_already_carries(): void
    {
        $user = User::factory()->create();
        $family = $this->categoryOf($user, 'Family', 1);
        $sibling = $this->typeOf($user, RelationshipTypeEnum::Sibling, $family);
        $sibling->update(['is_active' => false]);

        $choices = new RelationshipTypeChoices(accountId: $user->account_id, keeping: $sibling->refresh());

        $this->assertArrayHasKey($sibling->id, $choices->map());
    }

    #[Test]
    public function it_finds_nothing_for_an_account_with_no_types(): void
    {
        $user = User::factory()->create();

        $this->assertFalse(new RelationshipTypeChoices(accountId: $user->account_id)->any());
    }

    private function categoryOf(User $user, string $name, int $position): RelationshipCategory
    {
        return RelationshipCategory::factory()->create([
            'account_id' => $user->account_id,
            'name' => $name,
            'position' => $position,
        ]);
    }

    private function typeOf(User $user, RelationshipTypeEnum $case, RelationshipCategory $category): RelationshipType
    {
        return RelationshipType::factory()->system($case)->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $category->id,
        ]);
    }
}
