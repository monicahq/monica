<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\RelationshipTypeEnum;
use App\Models\Contact;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\RelationshipFormViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RelationshipFormViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_names_a_directed_type_by_both_of_its_roles(): void
    {
        $user = User::factory()->create();
        $this->typeOn($this->categoryOf($user, 'Family', 1), RelationshipTypeEnum::ParentChild);

        $groups = $this->viewModelFor($user)->typeGroups();

        $this->assertFalse($groups[0]['types'][0]['isSymmetric']);
    }

    #[Test]
    public function it_names_a_symmetric_type_by_its_one_role(): void
    {
        $user = User::factory()->create();
        $this->typeOn($this->categoryOf($user, 'Family', 1), RelationshipTypeEnum::Sibling);

        $groups = $this->viewModelFor($user)->typeGroups();

        $this->assertTrue($groups[0]['types'][0]['isSymmetric']);
    }

    #[Test]
    public function it_leaves_out_a_deactivated_category_and_one_holding_nothing(): void
    {
        $user = User::factory()->create();
        $this->typeOn($this->categoryOf($user, 'Family', 1), RelationshipTypeEnum::Sibling);
        $this->typeOn($this->categoryOf($user, 'Hidden', 2, false), RelationshipTypeEnum::Colleague);
        $this->categoryOf($user, 'Empty', 3);

        $groups = $this->viewModelFor($user)->typeGroups();

        $this->assertCount(1, $groups);
        $this->assertEquals('Family', $groups[0]['label']);
    }

    #[Test]
    public function it_offers_no_type_belonging_to_another_account(): void
    {
        $user = User::factory()->create();
        $this->typeOn($this->categoryOf(User::factory()->create(), 'Family', 1), RelationshipTypeEnum::Sibling);

        $this->assertEquals([], $this->viewModelFor($user)->typeGroups());
        $this->assertFalse($this->viewModelFor($user)->hasTypes());
    }

    #[Test]
    public function it_keys_the_types_by_id_so_the_form_can_read_their_roles(): void
    {
        $user = User::factory()->create();
        $type = $this->typeOn($this->categoryOf($user, 'Family', 1), RelationshipTypeEnum::ParentChild);

        $map = $this->viewModelFor($user)->typeMap();

        $this->assertFalse($map[$type->id]['isSymmetric']);
    }

    #[Test]
    public function it_offers_the_other_people_of_the_vault_and_never_the_one_on_screen(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $michael = $this->contactOn($vault, 'Michael', 'Scott');
        $this->contactOn($vault, 'Dwight', 'Schrute');

        $names = collect(new RelationshipFormViewModel(user: $user, vault: $vault, contact: $michael)->candidates())
            ->pluck('name');

        $this->assertEquals(['Dwight Schrute'], $names->all());
    }

    #[Test]
    public function it_never_reaches_a_contact_of_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);
        $michael = $this->contactOn($vault, 'Michael', 'Scott');
        $this->contactOn($elsewhere, 'Dwight', 'Schrute');

        $candidates = new RelationshipFormViewModel(user: $user, vault: $vault, contact: $michael)->candidates();

        $this->assertEquals([], $candidates);
    }

    #[Test]
    public function it_narrows_the_people_to_what_was_searched_for(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $michael = $this->contactOn($vault, 'Michael', 'Scott');
        $this->contactOn($vault, 'Dwight', 'Schrute');
        $this->contactOn($vault, 'Angela', 'Martin');

        $names = collect(new RelationshipFormViewModel(user: $user, vault: $vault, contact: $michael, query: 'schrute')->candidates())
            ->pluck('name');

        $this->assertEquals(['Dwight Schrute'], $names->all());
    }

    #[Test]
    public function it_stops_short_of_loading_the_whole_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $michael = $this->contactOn($vault, 'Michael', 'Scott');

        foreach (range(1, RelationshipFormViewModel::CANDIDATE_LIMIT + 5) as $number) {
            $this->contactOn($vault, 'Dwight', "Schrute {$number}");
        }

        $candidates = new RelationshipFormViewModel(user: $user, vault: $vault, contact: $michael)->candidates();

        $this->assertCount(RelationshipFormViewModel::CANDIDATE_LIMIT, $candidates);
    }

    #[Test]
    public function it_chooses_the_type_it_was_opened_with(): void
    {
        $user = User::factory()->create();
        $family = $this->categoryOf($user, 'Family', 1);
        $this->typeOn($family, RelationshipTypeEnum::ParentChild);
        $sibling = $this->typeOn($family, RelationshipTypeEnum::Sibling);

        $viewModel = $this->viewModelFor($user, (string) $sibling->id);

        $this->assertSame((string) $sibling->id, $viewModel->values()['typeId']);
        $this->assertSame((string) $sibling->id, $viewModel->projection()['typeId']);
    }

    #[Test]
    public function it_opens_with_the_roles_swapped_when_asked(): void
    {
        $user = User::factory()->create();
        $parentage = $this->typeOn($this->categoryOf($user, 'Family', 1), RelationshipTypeEnum::ParentChild);

        $viewModel = $this->viewModelFor($user, (string) $parentage->id, opensSwapped: true);

        $this->assertTrue($viewModel->values()['swapped']);
        $this->assertTrue($viewModel->projection()['swapped']);
    }

    #[Test]
    public function it_opens_unswapped_when_the_type_has_the_same_role_on_both_sides(): void
    {
        $user = User::factory()->create();
        $sibling = $this->typeOn($this->categoryOf($user, 'Family', 1), RelationshipTypeEnum::Sibling);

        $viewModel = $this->viewModelFor($user, (string) $sibling->id, opensSwapped: true);

        $this->assertFalse($viewModel->values()['swapped']);
    }

    #[Test]
    public function it_opens_unswapped_without_a_type(): void
    {
        $user = User::factory()->create();
        $this->typeOn($this->categoryOf($user, 'Family', 1), RelationshipTypeEnum::ParentChild);

        $viewModel = $this->viewModelFor($user, opensSwapped: true);

        $this->assertFalse($viewModel->values()['swapped']);
    }

    #[Test]
    public function it_ignores_a_type_it_does_not_offer(): void
    {
        $user = User::factory()->create();
        $family = $this->categoryOf($user, 'Family', 1);
        $closed = $this->categoryOf($user, 'Scranton branch', 2, isActive: false);
        $withdrawn = $this->typeOn($family, RelationshipTypeEnum::Sibling);
        $withdrawn->update(['is_active' => false]);
        $hidden = $this->typeOn($closed, RelationshipTypeEnum::Colleague);
        $theirs = RelationshipType::factory()->create();

        foreach ([(string) $withdrawn->id, (string) $hidden->id, (string) $theirs->id, 'abc'] as $preselected) {
            $this->assertSame('', $this->viewModelFor($user, $preselected)->values()['typeId']);
        }
    }

    #[Test]
    public function it_keeps_what_was_submitted_over_the_type_it_was_opened_with(): void
    {
        $user = User::factory()->create();
        $family = $this->categoryOf($user, 'Family', 1);
        $parent = $this->typeOn($family, RelationshipTypeEnum::ParentChild);
        $sibling = $this->typeOn($family, RelationshipTypeEnum::Sibling);

        request()->setLaravelSession(session()->driver());
        session()->flashInput(['relationship_type_id' => (string) $parent->id]);

        $this->assertSame((string) $parent->id, $this->viewModelFor($user, (string) $sibling->id)->values()['typeId']);
    }

    #[Test]
    public function it_offers_the_people_in_the_order_of_the_language_of_the_account(): void
    {
        $user = User::factory()->create(['locale' => 'fr_FR']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $michael = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Michael', 'middle_name' => null, 'last_name' => 'Scott']);
        Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Dwight', 'middle_name' => null, 'last_name' => 'Schrute']);
        Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Erin', 'middle_name' => null, 'last_name' => 'Élan']);

        $candidates = new RelationshipFormViewModel(user: $user, vault: $vault, contact: $michael)->candidates();

        $this->assertSame(['Erin Élan', 'Dwight Schrute'], array_column($candidates, 'name'));
    }

    private function viewModelFor(User $user, string $preselectedTypeId = '', bool $opensSwapped = false): RelationshipFormViewModel
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return new RelationshipFormViewModel(
            user: $user,
            vault: $vault,
            contact: $this->contactOn($vault, 'Michael', 'Scott'),
            preselectedTypeId: $preselectedTypeId,
            opensSwapped: $opensSwapped,
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

    private function categoryOf(User $user, string $name, int $position, bool $isActive = true): RelationshipCategory
    {
        return RelationshipCategory::factory()->create([
            'account_id' => $user->account_id,
            'name' => $name,
            'position' => $position,
            'is_active' => $isActive,
        ]);
    }

    private function typeOn(RelationshipCategory $category, RelationshipTypeEnum $case): RelationshipType
    {
        return RelationshipType::factory()->system($case)->create([
            'account_id' => $category->account_id,
            'relationship_category_id' => $category->id,
            'position' => 1,
        ]);
    }
}
