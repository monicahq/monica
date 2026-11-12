<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\RelationshipCategoryEnum;
use App\Models\Account;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RelationshipCategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function an_account_has_many_of_them_in_order(): void
    {
        $account = Account::factory()->create();
        RelationshipCategory::factory()->create(['account_id' => $account->id, 'name' => 'Second', 'position' => 2]);
        RelationshipCategory::factory()->create(['account_id' => $account->id, 'name' => 'First', 'position' => 1]);

        $this->assertCount(2, $account->relationshipCategories);
        $this->assertEquals('First', $account->relationshipCategories->first()->name);
    }

    #[Test]
    public function it_has_many_types_in_order(): void
    {
        $category = RelationshipCategory::factory()->create();
        RelationshipType::factory()->create([
            'account_id' => $category->account_id,
            'relationship_category_id' => $category->id,
            'name' => 'Second',
            'position' => 2,
        ]);
        RelationshipType::factory()->create([
            'account_id' => $category->account_id,
            'relationship_category_id' => $category->id,
            'name' => 'First',
            'position' => 1,
        ]);

        $this->assertTrue($category->types()->exists());
        $this->assertEquals('First', $category->types->first()->name);
    }

    #[Test]
    public function a_supplied_category_reads_the_wording_the_product_gave_it(): void
    {
        $category = RelationshipCategory::factory()->system(RelationshipCategoryEnum::Romantic)->create();

        $this->assertTrue($category->is_system);
        $this->assertEquals('romantic', $category->key);
    }

    #[Test]
    public function a_name_the_user_wrote_wins_over_the_supplied_wording(): void
    {
        $category = RelationshipCategory::factory()->system()->create(['name' => 'Dunder Mifflin']);

        $this->assertEquals('Dunder Mifflin', $category->name);
    }
}
