<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Enums\LogSourceEnum;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class McpChangelogTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_a_created_contact_as_having_arrived_over_mcp(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $key = $user->createToken('Dundie awards bot');

        $this->invoke($key->plainTextToken, 'create-contact', [
            'vault' => $vault->uuid,
            'first_name' => 'Michael',
        ])->assertOk();

        $this->assertDatabaseHas('logs', [
            'account_id' => $user->account_id,
            'source' => LogSourceEnum::Mcp->value,
            'source_detail' => 'Dundie awards bot',
        ]);
    }

    #[Test]
    public function a_change_made_in_a_browser_is_still_recorded_as_the_web(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->actingAs($user)->post(route('contacts.create', $vault->slug), ['first_name' => 'Creed']);

        $this->assertDatabaseMissing('logs', ['source' => LogSourceEnum::Mcp->value]);
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

    /** @param  array<string, mixed>  $arguments */
    private function invoke(string $token, string $tool, array $arguments): TestResponse
    {
        return $this->withToken($token)->postJson('/mcp', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => ['name' => $tool, 'arguments' => $arguments],
        ]);
    }
}
