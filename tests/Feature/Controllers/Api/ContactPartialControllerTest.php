<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactPartialControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_makes_a_full_contact_partial(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'is_partial' => false]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/partial", ['partial' => true]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.is_partial', true);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'is_partial' => true]);
    }

    #[Test]
    public function it_makes_a_partial_contact_a_full_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'is_partial' => true]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/partial", ['partial' => false]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.is_partial', false);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'is_partial' => false]);
    }

    #[Test]
    public function it_refuses_the_contact_representing_the_account_holder(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $me = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'is_me' => true]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$me->uuid}/partial", ['partial' => true]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['partial']);
        $this->assertFalse($me->refresh()->is_partial);
    }

    #[Test]
    public function it_refuses_a_request_that_does_not_say_which_state_it_wants(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/partial", []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['partial']);
    }

    #[Test]
    public function it_returns_not_found_for_a_contact_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create();
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id, 'is_partial' => false]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/partial", ['partial' => true])->assertNotFound();
        $this->assertFalse($contact->refresh()->is_partial);
    }

    #[Test]
    public function it_returns_not_found_for_a_contact_reached_through_the_wrong_vault_of_the_same_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $otherVault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'is_partial' => false]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$otherVault->uuid}/contacts/{$contact->uuid}/partial", ['partial' => true])->assertNotFound();
        $this->assertFalse($contact->refresh()->is_partial);
    }
}
