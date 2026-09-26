<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\DeleteLifeEventType;
use App\Models\LifeEvent;
use App\Models\LifeEventCategory;
use App\Models\LifeEventType;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteLifeEventTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_life_event_type_nobody_used(): void
    {
        $user = User::factory()->create();
        $type = $this->type($user);

        EricaServer::actingAs($user)->tool(DeleteLifeEventType::class, ['life_event_type' => $type->uuid])
            ->assertOk()
            ->assertHasNoErrors();

        $this->assertSoftDeleted($type);
    }

    #[Test]
    public function it_refuses_a_life_event_type_that_came_with_the_account(): void
    {
        $user = User::factory()->create();
        $type = LifeEventType::factory()->supplied()->create([
            'account_id' => $user->account_id,
            'life_event_category_id' => LifeEventCategory::factory()->create(['account_id' => $user->account_id])->id,
        ]);

        EricaServer::actingAs($user)->tool(DeleteLifeEventType::class, ['life_event_type' => $type->uuid])
            ->assertHasErrors(['The type came with your account and stays.']);

        $this->assertNotSoftDeleted($type);
    }

    #[Test]
    public function it_refuses_a_life_event_type_life_events_were_recorded_with(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $type = $this->type($user);
        LifeEvent::factory()->create(['vault_id' => $vault->id, 'life_event_type_id' => $type->id]);

        EricaServer::actingAs($user)->tool(DeleteLifeEventType::class, ['life_event_type' => $type->uuid])
            ->assertHasErrors(['Life events were recorded with this type, so it stays.']);

        $this->assertNotSoftDeleted($type);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.lifeEventTypes.destroy', app(DeleteLifeEventType::class)->mirrors());
    }

    private function type(User $user): LifeEventType
    {
        return LifeEventType::factory()->create([
            'account_id' => $user->account_id,
            'life_event_category_id' => LifeEventCategory::factory()->create(['account_id' => $user->account_id])->id,
        ]);
    }
}
