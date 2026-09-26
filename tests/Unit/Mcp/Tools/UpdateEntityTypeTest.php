<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\UpdateEntityType;
use App\Models\EntityType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateEntityTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_only_what_it_is_given(): void
    {
        $user = User::factory()->create();
        $entityType = EntityType::factory()->create([
            'account_id' => $user->account_id,
            'name_singular' => 'Paper ream',
            'name_plural' => 'Paper reams',
            'description' => 'What Stanley sells to Blue Cross.',
            'slug' => 'paper-ream',
        ]);

        EricaServer::actingAs($user)->tool(UpdateEntityType::class, [
            'entity_type' => $entityType->uuid,
            'colour' => '#9a3f2b',
        ])->assertOk()->assertHasNoErrors();

        $entityType->refresh();

        $this->assertSame('#9a3f2b', $entityType->colour);
        $this->assertSame('Paper ream', $entityType->name_singular);
        $this->assertSame('Paper reams', $entityType->name_plural);
        $this->assertSame('What Stanley sells to Blue Cross.', $entityType->description);
        $this->assertSame('paper-ream', $entityType->slug);
    }

    #[Test]
    public function it_clears_the_description_when_given_null(): void
    {
        $user = User::factory()->create();
        $entityType = EntityType::factory()->create(['account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(UpdateEntityType::class, [
            'entity_type' => $entityType->uuid,
            'description' => null,
        ])->assertOk()->assertHasNoErrors();

        $this->assertNull($entityType->refresh()->description);
    }

    #[Test]
    public function it_refuses_a_colour_outside_the_allowed_ones(): void
    {
        $user = User::factory()->create();
        $entityType = EntityType::factory()->create(['account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(UpdateEntityType::class, [
            'entity_type' => $entityType->uuid,
            'colour' => '#ffffff',
        ])->assertHasErrors();

        $this->assertSame(EntityType::DEFAULT_COLOUR, $entityType->refresh()->colour);
    }

    #[Test]
    public function it_refuses_an_empty_name(): void
    {
        $user = User::factory()->create();
        $entityType = EntityType::factory()->create(['account_id' => $user->account_id, 'name_singular' => 'Paper ream']);

        EricaServer::actingAs($user)->tool(UpdateEntityType::class, [
            'entity_type' => $entityType->uuid,
            'name_singular' => '',
        ])->assertHasErrors();

        $this->assertSame('Paper ream', $entityType->refresh()->name_singular);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.entityTypes.update', app(UpdateEntityType::class)->mirrors());
    }
}
