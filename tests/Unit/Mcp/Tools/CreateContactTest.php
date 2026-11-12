<?php

declare(strict_types=1);

namespace Tests\Unit\Mcp\Tools;

use App\Mcp\Servers\MonicaServer;
use App\Mcp\Tools\CreateContact;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateContactTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_the_contact_in_the_vault_it_was_given(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        MonicaServer::actingAs($user)->tool(CreateContact::class, [
            'vault' => $vault->uuid,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ])->assertOk()->assertHasNoErrors();

        $this->assertDatabaseHas('contacts', [
            'vault_id' => $vault->id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);
    }

    #[Test]
    public function it_relays_the_refusal_when_the_vault_has_no_template_to_start_from(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        MonicaServer::actingAs($user)->tool(CreateContact::class, [
            'vault' => $vault->uuid,
            'first_name' => 'Michael',
        ])->assertHasErrors();

        $this->assertDatabaseCount('contacts', 0);
    }

    private function vaultReadyForContacts(User $user): Vault
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);
        $template->structure->update(['published_version_id' => $version->id]);

        VaultDefaultTemplate::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $version->id,
        ]);

        return $vault;
    }
}
