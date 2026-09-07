<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Settings\Account\RelationshipTypes;

use App\Enums\RelationshipTypeEnum;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\User;
use App\ViewModels\Settings\Account\RelationshipTypes\RelationshipTypesViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RelationshipTypesViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_groups_the_types_under_their_categories_in_order(): void
    {
        $user = User::factory()->create();
        $second = RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Professional', 'position' => 2]);
        $first = RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Family', 'position' => 1]);
        RelationshipType::factory()->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $first->id,
            'name' => 'Sibling',
            'position' => 1,
        ]);
        RelationshipType::factory()->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $second->id,
            'name' => 'Colleague',
            'position' => 1,
        ]);

        $categories = new RelationshipTypesViewModel(user: $user)->categories();

        $this->assertEquals(['Family', 'Professional'], array_column($categories, 'name'));
        $this->assertEquals('Sibling', $categories[0]['types'][0]['name']);
        $this->assertEquals('Colleague', $categories[1]['types'][0]['name']);
    }

    #[Test]
    public function it_orders_the_types_inside_a_category(): void
    {
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        RelationshipType::factory()->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $category->id,
            'name' => 'Cousin',
            'position' => 2,
        ]);
        RelationshipType::factory()->create([
            'account_id' => $user->account_id,
            'relationship_category_id' => $category->id,
            'name' => 'Sibling',
            'position' => 1,
        ]);

        $types = new RelationshipTypesViewModel(user: $user)->categories()[0]['types'];

        $this->assertEquals(['Sibling', 'Cousin'], array_column($types, 'name'));
    }

    #[Test]
    public function it_reads_a_directed_type_from_each_side(): void
    {
        $user = User::factory()->create();
        RelationshipType::factory()->directed()->create(['account_id' => $user->account_id]);

        $type = new RelationshipTypesViewModel(user: $user)->categories()[0]['types'][0];

        $this->assertEquals('Godparent', $type['roleA']);
        $this->assertEquals('Godchild', $type['roleB']);
        $this->assertFalse($type['isSymmetric']);
        $this->assertEquals('Different on each side', $type['directionLabel']);
    }

    #[Test]
    public function it_reads_a_symmetric_type_the_same_way_on_both_sides(): void
    {
        $user = User::factory()->create();
        RelationshipType::factory()->create(['account_id' => $user->account_id, 'role_a' => 'Friend', 'role_b' => 'Friend']);

        $type = new RelationshipTypesViewModel(user: $user)->categories()[0]['types'][0];

        $this->assertTrue($type['isSymmetric']);
        $this->assertEquals('The same from both sides', $type['directionLabel']);
    }

    #[Test]
    public function it_says_which_types_the_product_supplied(): void
    {
        $user = User::factory()->create();
        RelationshipType::factory()->system(RelationshipTypeEnum::Friend)->create(['account_id' => $user->account_id]);

        $type = new RelationshipTypesViewModel(user: $user)->categories()[0]['types'][0];

        $this->assertTrue($type['isSystem']);
    }

    #[Test]
    public function it_says_what_a_type_feeds(): void
    {
        $user = User::factory()->create();
        RelationshipType::factory()->system(RelationshipTypeEnum::ParentChild)->create(['account_id' => $user->account_id]);

        $type = new RelationshipTypesViewModel(user: $user)->categories()[0]['types'][0];

        $this->assertEquals('feeds the family tree', $type['note']);
    }

    #[Test]
    public function it_says_a_retired_type_still_reads_normally(): void
    {
        $user = User::factory()->create();
        RelationshipType::factory()->inactive()->create(['account_id' => $user->account_id]);

        $type = new RelationshipTypesViewModel(user: $user)->categories()[0]['types'][0];

        $this->assertFalse($type['isActive']);
        $this->assertEquals('existing relationships still read normally', $type['note']);
    }

    #[Test]
    public function it_says_when_a_category_holds_nothing(): void
    {
        $user = User::factory()->create();
        RelationshipCategory::factory()->create(['account_id' => $user->account_id]);

        $category = new RelationshipTypesViewModel(user: $user)->categories()[0];

        $this->assertTrue($category['isEmpty']);
        $this->assertTrue($category['isFirst']);
        $this->assertTrue($category['isLast']);
        $this->assertNull($category['previous']);
        $this->assertNull($category['next']);
    }

    #[Test]
    public function it_names_the_category_a_move_would_step_over(): void
    {
        $user = User::factory()->create();
        RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Family', 'position' => 1]);
        RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Romantic', 'position' => 2]);

        $categories = new RelationshipTypesViewModel(user: $user)->categories();

        $this->assertEquals('Romantic', $categories[0]['next']);
        $this->assertEquals('Family', $categories[1]['previous']);
    }

    #[Test]
    public function it_leaves_out_what_another_account_holds(): void
    {
        $michael = User::factory()->create();
        $dwight = User::factory()->create();
        RelationshipCategory::factory()->create(['account_id' => $dwight->account_id, 'name' => 'Beet farming']);

        $this->assertEmpty(new RelationshipTypesViewModel(user: $michael)->categories());
    }
}
