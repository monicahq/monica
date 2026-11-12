<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\CreateEntityType;
use App\Models\EntityType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateEntityTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_an_entity_type(): void
    {
        $user = User::factory()->create();

        EricaServer::actingAs($user)->tool(CreateEntityType::class, [
            'name_singular' => 'Paper ream',
            'name_plural' => 'Paper reams',
            'icon' => 'pet',
            'colour' => '#4f8a49',
        ])->assertOk()->assertHasNoErrors()->assertSee('Paper reams');

        $this->assertDatabaseHas('entity_types', [
            'account_id' => $user->account_id,
            'name_singular' => 'Paper ream',
            'name_plural' => 'Paper reams',
            'slug' => 'paper-ream',
            'icon' => 'pet',
            'colour' => '#4f8a49',
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_refuses_a_slug_another_type_of_the_account_uses(): void
    {
        $user = User::factory()->create();
        EntityType::factory()->create(['account_id' => $user->account_id, 'slug' => 'dundie']);

        EricaServer::actingAs($user)->tool(CreateEntityType::class, [
            'name_singular' => 'Dundie',
            'name_plural' => 'Dundies',
            'slug' => 'dundie',
        ])->assertHasErrors();

        $this->assertSame(1, EntityType::query()->where('account_id', $user->account_id)->count());
    }

    #[Test]
    public function it_creates_an_entity_type_switched_off(): void
    {
        $user = User::factory()->create();

        EricaServer::actingAs($user)->tool(CreateEntityType::class, [
            'name_singular' => 'Dundie',
            'name_plural' => 'Dundies',
            'is_active' => '0',
        ])->assertOk()->assertHasNoErrors();

        $this->assertDatabaseHas('entity_types', [
            'account_id' => $user->account_id,
            'name_singular' => 'Dundie',
            'is_active' => false,
        ]);
    }
}
