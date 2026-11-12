<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\GetRelationshipCategory;
use App\Models\RelationshipCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetRelationshipCategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_a_relationship_category(): void
    {
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create(['account_id' => $user->account_id, 'name' => 'Scranton branch']);

        EricaServer::actingAs($user)->tool(GetRelationshipCategory::class, ['relationship_category' => $category->uuid])
            ->assertOk()
            ->assertSee('Scranton branch');
    }

    #[Test]
    public function it_reports_a_relationship_category_of_another_account_as_not_existing(): void
    {
        $user = User::factory()->create();
        $category = RelationshipCategory::factory()->create();

        EricaServer::actingAs($user)->tool(GetRelationshipCategory::class, ['relationship_category' => $category->uuid])
            ->assertHasErrors(['That relationship category does not exist.']);
    }
}
