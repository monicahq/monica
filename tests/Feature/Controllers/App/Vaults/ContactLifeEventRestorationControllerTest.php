<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Actions\CreateLifeEvent;
use App\Actions\DestroyLifeEvent;
use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactLifeEventRestorationControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_brings_a_deleted_life_event_back(): void
    {
        Queue::fake();

        [$user, $vault, $jim, $lifeEvent] = $this->deleted();

        $this->actingAs($user)
            ->put(route('contacts.lifeEventRestoration.update', ['vault' => $vault, 'contact' => $jim, 'lifeEventUuid' => $lifeEvent->uuid]))
            ->assertRedirect(route('contacts.show', ['vault' => $vault, 'contact' => $jim]))
            ->assertSessionHas('status', 'Call is back on the timeline.');

        $this->assertNotSoftDeleted($lifeEvent);
    }

    #[Test]
    public function it_does_not_find_a_deleted_life_event_through_another_vault(): void
    {
        Queue::fake();

        [$user, $vault, $jim, $lifeEvent] = $this->deleted();
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);
        $karen = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $elsewhere->id]);

        $this->actingAs($user)
            ->put(route('contacts.lifeEventRestoration.update', ['vault' => $elsewhere, 'contact' => $karen, 'lifeEventUuid' => $lifeEvent->uuid]))
            ->assertNotFound();

        $this->assertSoftDeleted($lifeEvent);
    }

    /** @return array{0: User, 1: Vault, 2: Contact, 3: LifeEvent} */
    private function deleted(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Jim']);

        $lifeEvent = new CreateLifeEvent(
            user: $user,
            vault: $vault,
            contact: $jim,
            lifeEventType: $this->lifeEventTypeAsking($user, 'Call'),
            happenedOn: CarbonImmutable::parse('2026-06-01'),
        )->execute();

        new DestroyLifeEvent(user: $user, vault: $vault, contact: $jim, lifeEvent: $lifeEvent)->execute();

        return [$user, $vault, $jim, $lifeEvent->refresh()];
    }
}
