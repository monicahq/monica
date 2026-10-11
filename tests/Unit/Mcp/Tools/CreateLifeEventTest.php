<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Enums\FieldTypeEnum;
use App\Mcp\Servers\EricaServer;
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

        EricaServer::actingAs($user)->tool(CreateLifeEvent::class, [
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
    public function it_refuses_a_type_that_can_no_longer_be_chosen(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $michael = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);
        $funRun = $this->lifeEventTypeAsking($user, 'Fun run');
        $funRun->update(['is_active' => false]);

        EricaServer::actingAs($user)->tool(CreateLifeEvent::class, [
            'vault' => $vault->uuid,
            'life_event_type' => $funRun->uuid,
            'happened_on' => '2026-10-09',
            'participants' => [$michael->uuid],
        ])->assertHasErrors(['That life event type can no longer be chosen.']);

        $this->assertDatabaseMissing('life_events', ['vault_id' => $vault->id]);
    }

    #[Test]
    public function it_refuses_a_participant_of_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $karen = Contact::factory()->create(['account_id' => $user->account_id]);

        EricaServer::actingAs($user)->tool(CreateLifeEvent::class, [
            'vault' => $vault->uuid,
            'life_event_type' => $this->lifeEventTypeAsking($user, 'Meeting')->uuid,
            'happened_on' => '2026-10-09',
            'participants' => [$karen->uuid],
        ])->assertHasErrors(['That contact does not exist.']);
    }

    #[Test]
    public function it_names_the_api_capability_it_mirrors(): void
    {
        $this->assertSame('api.v1.lifeEvents.create', app(CreateLifeEvent::class)->mirrors());
    }
}
