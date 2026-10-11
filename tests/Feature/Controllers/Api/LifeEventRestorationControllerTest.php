<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Actions\CreateLifeEvent;
use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LifeEventRestorationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_restores_a_deleted_life_event(): void
    {
        Queue::fake();
        [$user, $vault, $lifeEvent] = $this->booze();
        $lifeEvent->delete();

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/life-events/{$lifeEvent->uuid}/restoration");

        $response->assertOk();
        $response->assertJsonPath('data.id', $lifeEvent->uuid);
        $response->assertJsonPath('data.attributes.deleted_at', null);
        $this->assertNotSoftDeleted($lifeEvent);
    }

    #[Test]
    public function it_does_not_find_a_life_event_that_is_not_deleted(): void
    {
        Queue::fake();
        [$user, $vault, $lifeEvent] = $this->booze();

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/life-events/{$lifeEvent->uuid}/restoration")->assertNotFound();
    }

    /** @return array{0: User, 1: Vault, 2: LifeEvent} */
    private function booze(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Jim']);

        $lifeEvent = new CreateLifeEvent(
            user: $user,
            vault: $vault,
            contact: $jim,
            lifeEventType: $this->lifeEventTypeAsking($user, 'Booze cruise'),
            happenedOn: CarbonImmutable::parse('2026-01-15'),
        )->execute();

        return [$user, $vault, $lifeEvent];
    }
}
