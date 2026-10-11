<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Enums\FieldTypeEnum;
use App\Mcp\Servers\EricaServer;
use App\Mcp\Tools\GetLifeEvent;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\LifeEvent;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetLifeEventTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_one_life_event(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $lifeEvent = LifeEvent::factory()->create(['vault_id' => $vault->id, 'title' => 'Booze cruise']);

        EricaServer::actingAs($user)->tool(GetLifeEvent::class, [
            'vault' => $vault->uuid,
            'life_event' => $lifeEvent->uuid,
        ])->assertOk()->assertHasNoErrors()->assertSee('Booze cruise');
    }

    #[Test]
    public function it_never_returns_a_sensitive_value(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $lifeEvent = LifeEvent::factory()->create(['vault_id' => $vault->id, 'title' => 'Doctor visit']);
        $diagnosis = Field::factory()->create(['account_id' => $user->account_id, 'type' => FieldTypeEnum::Text, 'label' => 'Diagnosis', 'is_sensitive' => true]);
        FieldValue::factory()->create([
            'valuable_type' => LifeEvent::class,
            'valuable_id' => $lifeEvent->id,
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'field_id' => $diagnosis->id,
            'value_string' => 'Vasectomy',
        ]);

        EricaServer::actingAs($user)->tool(GetLifeEvent::class, [
            'vault' => $vault->uuid,
            'life_event' => $lifeEvent->uuid,
        ])->assertOk()->assertSee('Diagnosis')->assertDontSee('Vasectomy');
    }

    #[Test]
    public function it_refuses_a_life_event_of_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $nashua = Vault::factory()->create(['account_id' => $user->account_id]);
        $lifeEvent = LifeEvent::factory()->create(['vault_id' => $nashua->id]);

        EricaServer::actingAs($user)->tool(GetLifeEvent::class, [
            'vault' => $vault->uuid,
            'life_event' => $lifeEvent->uuid,
        ])->assertHasErrors(['That life event does not exist.']);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.lifeEvents.show', app(GetLifeEvent::class)->mirrors());
    }
}
