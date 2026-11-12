<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Enums\FieldTypeEnum;
use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\CreateLifeEvent;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateLifeEventTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_a_life_event_with_everybody_who_was_there(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $pam = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
        $meal = $this->lifeEventTypeAsking($user, 'Meal', ['Dish' => FieldTypeEnum::Text]);
        $dish = $meal->structure->publishedVersion->inputFields()->firstOrFail();

        MonicaServer::actingAs($user)->tool(CreateLifeEvent::class, [
            'vault' => $vault->uuid,
            'life_event_type' => $meal->uuid,
            'happened_on' => '2026-10-09',
            'participants' => [$pam->uuid, $jim->uuid],
            'title' => 'Lunch at Cugino’s',
            'fields' => [(string) $dish->id => 'Chicken parm'],
        ])->assertOk()->assertHasNoErrors()->assertSee('Lunch at Cugino’s');

        $this->assertDatabaseHas('life_events', ['vault_id' => $vault->id, 'title' => 'Lunch at Cugino’s', 'created_from' => 'mcp', 'participant_count' => 2]);
        $this->assertDatabaseHas('field_values', ['field_id' => $dish->id, 'value_string' => 'Chicken parm']);
    }

    #[Test]
    public function it_refuses_a_participant_of_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $karen = Contact::factory()->create(['account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(CreateLifeEvent::class, [
            'vault' => $vault->uuid,
            'life_event_type' => $this->lifeEventTypeAsking($user, 'Meeting')->uuid,
            'happened_on' => '2026-10-09',
            'participants' => [$karen->uuid],
        ])->assertHasErrors();
    }
}
