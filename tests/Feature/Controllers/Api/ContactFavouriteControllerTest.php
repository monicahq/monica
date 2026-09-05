<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Api;

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactFavouriteControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_marks_a_contact_a_favourite(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/favourite", ['favourite' => true]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.is_favourite', true);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'is_favourite' => true]);
    }

    #[Test]
    public function it_takes_the_mark_off_again(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->favourite()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/favourite", ['favourite' => false]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.is_favourite', false);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'is_favourite' => false]);
    }

    #[Test]
    public function it_confirms_the_state_rather_than_inverting_it_when_the_same_request_arrives_twice(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/favourite", ['favourite' => true])->assertOk();
        $second = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/favourite", ['favourite' => true]);

        $second->assertOk();
        $second->assertJsonPath('data.attributes.is_favourite', true);
    }

    #[Test]
    public function it_puts_a_marked_contact_in_the_favourites_grouping(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'last_name' => 'Halpert']);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/favourite", ['favourite' => true]);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts?filter=favourites");

        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.attributes.last_name', 'Halpert');
    }

    #[Test]
    public function it_refuses_a_request_that_does_not_say_which_state_it_wants(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/favourite", []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['favourite']);
    }

    #[Test]
    public function it_returns_not_found_for_a_contact_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create();
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/favourite", ['favourite' => true])->assertNotFound();
        $this->assertFalse($contact->refresh()->is_favourite);
    }

    #[Test]
    public function it_returns_not_found_for_a_contact_reached_through_the_wrong_vault_of_the_same_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $other = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$other->uuid}/contacts/{$contact->uuid}/favourite", ['favourite' => true])->assertNotFound();
        $this->assertFalse($contact->refresh()->is_favourite);
    }

    #[Test]
    public function it_refuses_a_caller_with_no_credential(): void
    {
        $vault = Vault::factory()->create();
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $vault->account_id]);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/favourite", ['favourite' => true])->assertUnauthorized();
    }
}
