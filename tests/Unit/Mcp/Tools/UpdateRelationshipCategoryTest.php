<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\UpdateRelationshipCategory;
use App\Models\RelationshipCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateRelationshipCategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renames_a_relationship_category(): void
    {
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(UpdateRelationshipCategory::class, [
            'relationship_category' => $category->uuid,
            'name' => 'Stamford branch',
        ])->assertOk()->assertHasNoErrors();

        $this->assertSame('Stamford branch', $category->refresh()->name);
    }

    #[Test]
    public function it_refuses_renaming_a_category_that_came_with_the_account(): void
    {
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->system()->create(['account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(UpdateRelationshipCategory::class, [
            'relationship_category' => $category->uuid,
            'name' => 'Stamford branch',
        ])->assertHasErrors();

        $this->assertNull($category->refresh()->getRawOriginal('name'));
    }
}
