<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\RelationshipTypeEnum;
use App\Models\Contact;
use App\Models\Relationship;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\RelationshipEditViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RelationshipEditViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_offers_both_directions_of_a_directed_type_and_one_sentence_for_a_symmetric_one(): void
    {
        $user = User::factory()->create();
        $family = $this->categoryOf($user, 'Family', 1);
        $parentage = $this->typeOn($family, RelationshipTypeEnum::ParentChild, 1);
        $sibling = $this->typeOn($family, RelationshipTypeEnum::Sibling, 2);
        [$michael, $relationship] = $this->relationshipFrom($user, $parentage);

        $options = $this->viewModel($user, $michael, $relationship)->sentenceGroups()[0]['options'];

        $this->assertSame(["{$parentage->id}-a", "{$parentage->id}-b", "{$sibling->id}-a"], array_column($options, 'value'));
    }

    #[Test]
    public function it_chooses_the_sentence_the_relationship_reads_today_from_either_side(): void
    {
        $user = User::factory()->create();
        $parentage = $this->typeOn($this->categoryOf($user, 'Family', 1), RelationshipTypeEnum::ParentChild, 1);
        [$michael, $relationship] = $this->relationshipFrom($user, $parentage);
        $jan = $relationship->contactB;

        $this->assertSame("{$parentage->id}-a", $this->viewModel($user, $michael, $relationship)->values()['relationship']);
        $this->assertSame("{$parentage->id}-b", $this->viewModel($user, $jan, $relationship)->values()['relationship']);
    }

    #[Test]
    public function it_keeps_offering_a_withdrawn_type_the_relationship_carries(): void
    {
        $user = User::factory()->create();
        $parentage = $this->typeOn($this->categoryOf($user, 'Family', 1), RelationshipTypeEnum::ParentChild, 1);
        [$michael, $relationship] = $this->relationshipFrom($user, $parentage);
        $parentage->update(['is_active' => false]);

        $options = $this->viewModel($user, $michael, $relationship->refresh())->sentenceGroups()[0]['options'];

        $this->assertSame("{$parentage->id}-a", $options[0]['value']);
    }

    #[Test]
    public function it_tells_which_of_three_states_the_other_side_is_in(): void
    {
        $user = User::factory()->create();
        $parentage = $this->typeOn($this->categoryOf($user, 'Family', 1), RelationshipTypeEnum::ParentChild, 1);
        [$michael, $relationship] = $this->relationshipFrom($user, $parentage);
        $jan = $relationship->contactB;

        $this->assertSame(RelationshipEditViewModel::OTHER_HAS_A_RECORD, $this->viewModel($user, $michael, $relationship)->otherState());

        $jan->update(['is_partial' => true]);
        $this->assertSame(RelationshipEditViewModel::OTHER_IS_NAMED, $this->viewModel($user, $michael, $relationship->refresh())->otherState());

        $jan->displayNameRepresentation->delete();
        $this->assertSame(RelationshipEditViewModel::OTHER_IS_NAMELESS, $this->viewModel($user, $michael, $relationship->refresh())->otherState());
    }

    #[Test]
    public function it_finds_the_other_side_whichever_side_the_page_is_on(): void
    {
        $user = User::factory()->create();
        $parentage = $this->typeOn($this->categoryOf($user, 'Family', 1), RelationshipTypeEnum::ParentChild, 1);
        [$michael, $relationship] = $this->relationshipFrom($user, $parentage);
        $jan = $relationship->contactB;
        $michael->update(['is_partial' => true]);

        $fromJan = $this->viewModel($user, $jan, $relationship->refresh());

        $this->assertTrue($fromJan->otherSide()->is($michael));
        $this->assertSame(RelationshipEditViewModel::OTHER_IS_NAMED, $fromJan->otherState());
        $this->assertSame(RelationshipEditViewModel::OTHER_HAS_A_RECORD, $this->viewModel($user, $michael, $relationship)->otherState());
    }

    #[Test]
    public function it_fills_the_name_fields_and_the_ended_box_from_the_relationship(): void
    {
        $user = User::factory()->create();
        $parentage = $this->typeOn($this->categoryOf($user, 'Family', 1), RelationshipTypeEnum::ParentChild, 1);
        [$michael, $relationship] = $this->relationshipFrom($user, $parentage);
        $relationship->contactB->update(['is_partial' => true]);
        $relationship->update(['status' => 'ended']);

        $values = $this->viewModel($user, $michael, $relationship->refresh())->values();

        $this->assertSame('known', $values['mode']);
        $this->assertSame('Jan', $values['firstName']);
        $this->assertSame('Levinson', $values['lastName']);
        $this->assertTrue($values['hasEnded']);
    }

    /** @return array{0: Contact, 1: Relationship} */
    private function relationshipFrom(User $user, RelationshipType $type): array
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $michael = $this->contactOn($vault, 'Michael', 'Scott');
        $jan = $this->contactOn($vault, 'Jan', 'Levinson');

        $relationship = Relationship::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'relationship_type_id' => $type->id,
            'contact_a_id' => $michael->id,
            'contact_b_id' => $jan->id,
        ]);

        return [$michael, $relationship];
    }

    private function viewModel(User $user, Contact $contact, Relationship $relationship): RelationshipEditViewModel
    {
        return new RelationshipEditViewModel(
            user: $user,
            vault: $relationship->vault,
            contact: $contact,
            relationship: $relationship,
        );
    }

    private function contactOn(Vault $vault, string $firstName, string $lastName): Contact
    {
        return Contact::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
    }

    private function categoryOf(User $user, string $name, int $position): RelationshipCategory
    {
        return RelationshipCategory::factory()->create([
            'account_id' => $user->account_id,
            'name' => $name,
            'position' => $position,
        ]);
    }

    private function typeOn(RelationshipCategory $category, RelationshipTypeEnum $case, int $position): RelationshipType
    {
        return RelationshipType::factory()->system($case)->create([
            'account_id' => $category->account_id,
            'relationship_category_id' => $category->id,
            'position' => $position,
        ]);
    }
}
