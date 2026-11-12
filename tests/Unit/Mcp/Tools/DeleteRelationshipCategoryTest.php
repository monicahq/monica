<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\DeleteRelationshipCategory;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteRelationshipCategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_an_empty_relationship_category(): void
    {
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(DeleteRelationshipCategory::class, ['relationship_category' => $category->uuid])
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertSoftDeleted($category);
    }

    #[Test]
    public function it_refuses_a_category_still_holding_types(): void
    {
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id]);
        RelationshipType::factory()->create(['account_id' => $user->account_id, 'relationship_category_id' => $category->id]);

        EricaServer::actingAs($user)->tool(DeleteRelationshipCategory::class, ['relationship_category' => $category->uuid])
            ->assertHasErrors(['The category still holds types and stays.']);

        $this->assertNotSoftDeleted($category);
    }
}
