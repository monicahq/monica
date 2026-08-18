<?php

namespace Tests\Feature\Controllers\Vault;

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultControllerTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function it_shows_the_vault_dashboard(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
        ]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $user->vaults()->attach($vault->id, [
            'permission' => Vault::PERMISSION_MANAGE,
            'contact_id' => $contact->id,
        ]);

        $response = $this->actingAs($user)->get(route('vault.show', [
            'vault' => $vault,
        ]));

        $response->assertOk();
    }

    #[Test]
    public function it_does_not_crash_when_the_users_contact_in_the_vault_has_been_deleted(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
        ]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $user->vaults()->attach($vault->id, [
            'permission' => Vault::PERMISSION_MANAGE,
            'contact_id' => $contact->id,
        ]);

        // simulate the contact that represents the user in this vault having
        // been deleted (see issue #7522), leaving a stale contact_id on the
        // pivot row.
        $contact->forceDelete();

        $response = $this->actingAs($user)->get(route('vault.show', [
            'vault' => $vault,
        ]));

        $response->assertOk();
    }
}
