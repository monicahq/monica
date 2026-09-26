<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\DeleteEntityType;
use App\Models\EntityType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteEntityTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_an_entity_type_holding_no_record(): void
    {
        $user = User::factory()->create();
        $entityType = EntityType::factory()->create(['account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(DeleteEntityType::class, ['entity_type' => $entityType->uuid])
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertSoftDeleted($entityType);
    }

    #[Test]
    public function it_refuses_an_entity_type_still_holding_records(): void
    {
        $user = User::factory()->create();
        $entityType = EntityType::factory()->used(3)->create(['account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(DeleteEntityType::class, ['entity_type' => $entityType->uuid])
            ->assertHasErrors(['The type still holds records and stays.']);

        $this->assertNotSoftDeleted($entityType);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.entityTypes.destroy', app(DeleteEntityType::class)->mirrors());
    }
}
