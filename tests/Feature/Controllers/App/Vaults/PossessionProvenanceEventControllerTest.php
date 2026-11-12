<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\Possession;
use App\Models\PossessionProvenanceEvent;
use App\Models\PossessionTransaction;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PossessionProvenanceEventControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_provenance_of_a_possession(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $press = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Beet press']);
        PossessionProvenanceEvent::factory()->create(['possession_id' => $press->id, 'title' => 'Built by Grandfather Schrute']);

        $this->actingAs($user)->get(route('possessions.provenance.index', [$vault, $press]))
            ->assertOk()
            ->assertSee('Built by Grandfather Schrute')
            ->assertSee('Add an event')
            ->assertSee('aria-current="page"', escape: false)
            ->assertDontSee('@js(', escape: false);
    }

    #[Test]
    public function it_records_an_event_linked_to_a_transaction(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $press = Possession::factory()->create(['vault_id' => $vault->id]);
        $purchase = PossessionTransaction::factory()->create(['possession_id' => $press->id]);

        $this->actingAs($user)->post(route('possessions.provenance.create', [$vault, $press]), [
            'type' => 'acquisition',
            'date_precision' => 'month',
            'occurred_on' => '1921-06',
            'to' => 'Dwight Schrute Sr.',
            'transaction' => $purchase->uuid,
            'is_verified' => '1',
        ])
            ->assertRedirect(route('possessions.provenance.index', [$vault, $press]))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('possession_provenance_events', [
            'possession_id' => $press->id,
            'possession_transaction_id' => $purchase->id,
            'type' => 'acquisition',
            'date_precision' => 'month',
            'to' => 'Dwight Schrute Sr.',
            'is_verified' => true,
        ]);
    }

    #[Test]
    public function it_refuses_a_transaction_of_another_possession(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $press = Possession::factory()->create(['vault_id' => $vault->id]);
        $teapotPurchase = PossessionTransaction::factory()->create([
            'possession_id' => Possession::factory()->create(['vault_id' => $vault->id])->id,
        ]);

        $this->actingAs($user)->post(route('possessions.provenance.create', [$vault, $press]), [
            'type' => 'acquisition',
            'date_precision' => 'day',
            'transaction' => $teapotPurchase->uuid,
        ])->assertSessionHasErrors('transaction');

        $this->assertDatabaseCount('possession_provenance_events', 0);
    }

    #[Test]
    public function it_returns_the_form_when_the_date_does_not_match_its_precision(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $press = Possession::factory()->create(['vault_id' => $vault->id]);

        $this->actingAs($user)->post(route('possessions.provenance.create', [$vault, $press]), [
            'type' => 'origin',
            'date_precision' => 'year',
            'occurred_on' => '1921-06',
        ])->assertSessionHasErrors('occurred_on');
    }

    #[Test]
    public function it_shows_an_event_in_the_form_for_editing(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $press = Possession::factory()->create(['vault_id' => $vault->id]);
        $event = PossessionProvenanceEvent::factory()->create(['possession_id' => $press->id, 'title' => 'Built by Grandfather Schrute']);

        $this->actingAs($user)->get(route('possessions.provenance.edit', [$vault, $press, $event]))
            ->assertOk()
            ->assertSee('Edit provenance event')
            ->assertSee('value="Built by Grandfather Schrute"', escape: false)
            ->assertSee(route('possessions.provenance.update', [$vault, $press, $event]));
    }

    #[Test]
    public function it_changes_an_event(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $press = Possession::factory()->create(['vault_id' => $vault->id]);
        $event = PossessionProvenanceEvent::factory()->create(['possession_id' => $press->id]);

        $this->actingAs($user)->put(route('possessions.provenance.update', [$vault, $press, $event]), [
            'type' => 'origin',
            'date_precision' => 'circa',
            'occurred_on' => '1921',
            'location' => 'Honesdale',
        ])
            ->assertRedirect(route('possessions.provenance.index', [$vault, $press]))
            ->assertSessionHas('status');

        $event->refresh();
        $this->assertEquals('origin', $event->type->value);
        $this->assertEquals('Honesdale', $event->location);
        $this->assertFalse($event->is_verified);
    }

    #[Test]
    public function it_deletes_an_event(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $press = Possession::factory()->create(['vault_id' => $vault->id]);
        $event = PossessionProvenanceEvent::factory()->create(['possession_id' => $press->id]);

        $this->actingAs($user)->delete(route('possessions.provenance.destroy', [$vault, $press, $event]))
            ->assertRedirect(route('possessions.provenance.index', [$vault, $press]))
            ->assertSessionHas('status');

        $this->assertModelMissing($event);
    }

    #[Test]
    public function it_answers_not_found_for_an_event_of_another_possession(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $press = Possession::factory()->create(['vault_id' => $vault->id]);
        $event = PossessionProvenanceEvent::factory()->create([
            'possession_id' => Possession::factory()->create(['vault_id' => $vault->id])->id,
        ]);

        $this->actingAs($user)->get(route('possessions.provenance.edit', [$vault, $press, $event]))
            ->assertNotFound();
        $this->actingAs($user)->delete(route('possessions.provenance.destroy', [$vault, $press, $event]))
            ->assertNotFound();

        $this->assertModelExists($event);
    }
}
