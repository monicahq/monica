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

class ContactNameControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, string|null> */
    private array $wholeName = [
        'prefix' => null,
        'first_name' => 'Pam',
        'middle_name' => null,
        'last_name' => 'Halpert',
        'suffix' => null,
        'maiden_name' => 'Beesly',
        'nickname' => 'Pammy',
        'name_order' => 'given_first',
    ];

    #[Test]
    public function it_replaces_every_part_of_a_name(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $user->account_id,
            'prefix' => 'Ms',
            'first_name' => 'Pam',
            'last_name' => 'Beesly',
            'nickname' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/name", $this->wholeName);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.name', 'Pam Halpert');
        $response->assertJsonPath('data.attributes.prefix', null);
        $response->assertJsonPath('data.attributes.maiden_name', 'Beesly');
        $response->assertJsonPath('data.attributes.nickname', 'Pammy');
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'prefix' => null,
            'last_name' => 'Halpert',
            'maiden_name' => 'Beesly',
        ]);
    }

    #[Test]
    public function it_rebuilds_what_the_lists_sort_and_search_on(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $user->account_id,
            'first_name' => 'Pam',
            'last_name' => 'Beesly',
        ]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/name", $this->wholeName)->assertOk();

        $this->assertEquals('halpert pam', $contact->refresh()->sort_name);
        $this->json('GET', "/api/v1/vaults/{$vault->uuid}/contacts?query=halpert")->assertJsonCount(1, 'data');
    }

    #[Test]
    public function it_refuses_a_body_that_leaves_a_part_of_the_name_out(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $user->account_id,
            'nickname' => 'Pammy',
        ]);

        Sanctum::actingAs($user);

        $body = $this->wholeName;
        unset($body['nickname']);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/name", $body);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['nickname']);
        $this->assertEquals('Pammy', $contact->refresh()->nickname);
    }

    #[Test]
    public function it_accepts_a_name_reduced_to_a_nickname(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

        Sanctum::actingAs($user);

        $response = $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/name", [
            'prefix' => null,
            'first_name' => null,
            'middle_name' => null,
            'last_name' => null,
            'suffix' => null,
            'maiden_name' => null,
            'nickname' => 'Prison Mike',
            'name_order' => null,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.attributes.name', 'Prison Mike');
        $response->assertJsonPath('data.attributes.first_name', null);
    }

    #[Test]
    public function it_returns_not_found_for_a_contact_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create();
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'last_name' => 'Vance',
        ]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$vault->uuid}/contacts/{$contact->uuid}/name", $this->wholeName)->assertNotFound();
        $this->assertEquals('Vance', $contact->refresh()->last_name);
    }

    #[Test]
    public function it_returns_not_found_for_a_contact_reached_through_the_wrong_vault_of_the_same_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $other = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $user->account_id,
            'last_name' => 'Vance',
        ]);

        Sanctum::actingAs($user);

        $this->json('PUT', "/api/v1/vaults/{$other->uuid}/contacts/{$contact->uuid}/name", $this->wholeName)->assertNotFound();
        $this->assertEquals('Vance', $contact->refresh()->last_name);
    }
}
