<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\RelationshipTypes;

use App\Enums\RelationshipTypeEnum;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\User;
use App\ViewModels\Settings\Account\RelationshipTypes\RelationshipTypeFormViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RelationshipTypeFormViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_opens_empty_when_a_type_is_being_added(): void
    {
        $user = User::factory()->create();
        RelationshipCategory::factory()->create(['account_id' => $user->account_id]);

        $viewModel = new RelationshipTypeFormViewModel(user: $user);

        $this->assertFalse($viewModel->isEdit());
        $this->assertEquals('post', $viewModel->method());
        $this->assertEquals('', $viewModel->values()['name']);
        $this->assertTrue($viewModel->values()['isSymmetric']);
    }

    #[Test]
    public function it_starts_in_the_category_the_user_came_from(): void
    {
        $user = User::factory()->create();
        RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'position' => 1]);
        $wanted = RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Professional', 'position' => 2]);

        $viewModel = new RelationshipTypeFormViewModel(user: $user, wantedCategory: $wanted);

        $this->assertEquals($wanted->id, $viewModel->values()['categoryId']);
    }

    #[Test]
    public function it_reads_the_type_it_is_editing(): void
    {
        $user = User::factory()->create();
        $type = RelationshipType::factory()->directed()->create(['account_id' => $user->account_id]);

        $viewModel = new RelationshipTypeFormViewModel(user: $user, relationshipType: $type);

        $this->assertTrue($viewModel->isEdit());
        $this->assertEquals('put', $viewModel->method());
        $this->assertEquals('Godparent', $viewModel->values()['roleA']);
        $this->assertEquals('Godchild', $viewModel->values()['roleB']);
        $this->assertFalse($viewModel->values()['isSymmetric']);
    }

    #[Test]
    public function a_supplied_type_keeps_its_direction(): void
    {
        $user = User::factory()->create();
        $type = RelationshipType::factory()->system(RelationshipTypeEnum::ParentChild)->create(['account_id' => $user->account_id]);

        $viewModel = new RelationshipTypeFormViewModel(user: $user, relationshipType: $type);

        $this->assertTrue($viewModel->isSystem());
        $this->assertFalse($viewModel->directionIsEditable());
    }

    #[Test]
    public function it_says_what_the_product_does_with_a_supplied_type(): void
    {
        $user = User::factory()->create();
        RelationshipType::factory()->system(RelationshipTypeEnum::ParentChild)->create(['account_id' => $user->account_id]);
        RelationshipType::factory()->system(RelationshipTypeEnum::Spouse)->create(['account_id' => $user->account_id]);
        $ordinary = RelationshipType::factory()->create(['account_id' => $user->account_id]);

        $this->assertNull(new RelationshipTypeFormViewModel(user: $user, relationshipType: $ordinary)->behaviourTitle());
    }

    #[Test]
    public function a_supplied_type_cannot_be_deleted_and_says_so(): void
    {
        $user = User::factory()->create();
        $type = RelationshipType::factory()->system(RelationshipTypeEnum::Friend)->create(['account_id' => $user->account_id]);

        $viewModel = new RelationshipTypeFormViewModel(user: $user, relationshipType: $type);

        $this->assertFalse($viewModel->canBeDeleted());
    }

    #[Test]
    public function it_offers_the_categories_of_the_account_and_marks_the_one_in_use(): void
    {
        $user = User::factory()->create();
        $dwight = User::factory()->create();
        $family = RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Family', 'position' => 1]);
        RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Romantic', 'position' => 2]);
        RelationshipCategory::factory()->create(['account_id' => $dwight->account_id, 'name' => 'Beet farming']);
        $type = RelationshipType::factory()->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $family->id,
        ]);

        $options = new RelationshipTypeFormViewModel(user: $user, relationshipType: $type)->categoryOptions();

        $this->assertEquals(['Family', 'Romantic'], array_column($options, 'label'));
        $this->assertTrue($options[0]['selected']);
        $this->assertFalse($options[1]['selected']);
    }
}
