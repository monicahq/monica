<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\GetLifeEventType;
use App\Models\LifeEventType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetLifeEventTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_a_life_event_type(): void
    {
        $user = User::factory()->create();
        $type = LifeEventType::factory()->create(['account_id' => $user->account_id, 'name' => 'Won a Dundie']);

        EricaServer::actingAs($user)->tool(GetLifeEventType::class, ['life_event_type' => $type->uuid])
            ->assertOk()
            ->assertSee(['Won a Dundie', $type->lifeEventCategory->uuid]);
    }

    #[Test]
    public function it_reports_a_life_event_type_of_another_account_as_not_existing(): void
    {
        $user = User::factory()->create();
        $type = LifeEventType::factory()->create();

        EricaServer::actingAs($user)->tool(GetLifeEventType::class, ['life_event_type' => $type->uuid])
            ->assertHasErrors(['That life event type does not exist.']);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.lifeEventTypes.show', app(GetLifeEventType::class)->mirrors());
    }
}
