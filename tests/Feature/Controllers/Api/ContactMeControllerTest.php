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

class ContactMeControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_resolves_the_me_contact_of_a_vault(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $me = Contact::factory()->me()->create([
            'vault_id' => $vault->id,
            'account_id' => $user->account_id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);
        Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

        $response = $this->json('GET', "/api/v1/vaults/{$vault->slug}/contacts/me");

        $response->assertOk()
            ->assertJsonPath('data.id', $me->uuid)
            ->assertJsonPath('data.attributes.is_me', true)
            ->assertJsonPath('data.attributes.first_name', 'Michael');
    }

    #[Test]
    public function it_answers_not_found_when_the_vault_holds_no_me_contact(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id]);

        $this->json('GET', "/api/v1/vaults/{$vault->slug}/contacts/me")->assertNotFound();
    }

    #[Test]
    public function it_answers_not_found_for_a_vault_of_another_account(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $other = Vault::factory()->create();
        Contact::factory()->me()->create(['vault_id' => $other->id, 'account_id' => $other->account_id]);

        $this->json('GET', "/api/v1/vaults/{$other->slug}/contacts/me")->assertNotFound();
    }

    #[Test]
    public function it_resolves_the_me_contact_of_the_vault_named_and_no_other(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $asked = Vault::factory()->create(['account_id' => $user->account_id]);
        $other = Vault::factory()->create(['account_id' => $user->account_id]);
        $me = Contact::factory()->me()->create(['vault_id' => $asked->id, 'account_id' => $user->account_id]);
        Contact::factory()->me()->create(['vault_id' => $other->id, 'account_id' => $user->account_id]);

        $this->json('GET', "/api/v1/vaults/{$asked->slug}/contacts/me")
            ->assertOk()
            ->assertJsonPath('data.id', $me->uuid);
    }
}
