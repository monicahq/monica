<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\CreateRelationshipCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateRelationshipCategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_relationship_category(): void
    {
        $user = User::factory()->create();

        EricaServer::actingAs($user)->tool(CreateRelationshipCategory::class, ['name' => 'Scranton branch'])
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertDatabaseHas('relationship_categories', [
            'account_id' => $user->account_id,
            'name' => 'Scranton branch',
            'is_system' => false,
        ]);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.relationshipCategories.create', app(CreateRelationshipCategory::class)->mirrors());
    }
}
