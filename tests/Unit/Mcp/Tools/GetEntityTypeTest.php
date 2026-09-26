<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\GetEntityType;
use App\Models\EntityType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetEntityTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_an_entity_type_of_the_account(): void
    {
        $user = User::factory()->create();
        $entityType = EntityType::factory()->create([
            'account_id' => $user->account_id,
            'name_singular' => 'Paper ream',
            'name_plural' => 'Paper reams',
        ]);

        EricaServer::actingAs($user)->tool(GetEntityType::class, ['entity_type' => $entityType->uuid])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertSee('Paper reams');
    }

    #[Test]
    public function it_reports_an_entity_type_of_another_account_as_not_existing(): void
    {
        $user = User::factory()->create();
        $other = EntityType::factory()->create();

        EricaServer::actingAs($user)->tool(GetEntityType::class, ['entity_type' => $other->uuid])
            ->assertHasErrors(['That entity type does not exist.']);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.entityTypes.show', app(GetEntityType::class)->mirrors());
    }
}
