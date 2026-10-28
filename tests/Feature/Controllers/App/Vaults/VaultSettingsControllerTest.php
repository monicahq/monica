<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\AccountExportStatusEnum;
use App\Models\AccountExport;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_settings_of_a_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
        ]);

        $this->actingAs($user)->get(route('vaults.settings.index', $vault))
            ->assertStatus(200)
            ->assertSee('Vault settings')
            ->assertSee('What this vault is called')
            ->assertSee('What a new contact starts from')
            ->assertSee('Delete this vault');
    }

    #[Test]
    public function it_offers_to_export_the_vault_behind_a_password(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('vaults.settings.index', $vault))
            ->assertStatus(200)
            ->assertSee('Export this vault')
            ->assertSee('sensitive fields included')
            ->assertSee('action="'.route('settings.accountExports.create').'"', false)
            ->assertSee('name="vault" value="'.$vault->id.'"', false)
            ->assertSee('Confirm it is you');
    }

    #[Test]
    public function it_says_why_the_vault_cannot_be_exported_while_another_export_is_being_prepared(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        AccountExport::factory()->create(['user_id' => $user->id, 'status' => AccountExportStatusEnum::Preparing]);

        $this->actingAs($user)->get(route('vaults.settings.index', $vault))
            ->assertStatus(200)
            ->assertSee('aria-describedby="vault-export-refusal"', false)
            ->assertSee('An export is already being prepared.');
    }

    #[Test]
    public function it_navigates_the_screen_with_the_vault_column(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
        ]);

        $response = $this->actingAs($user)->get(route('vaults.settings.index', $vault))
            ->assertStatus(200)
            ->assertSee('aria-label="This vault"', false)
            ->assertSee(route('contacts.index', $vault), false);

        $this->assertStringContainsString('id="main-nav"', $response->getContent());
        $this->assertStringContainsString('Dunder Mifflin', $response->getContent());
    }

    #[Test]
    public function it_says_what_deleting_the_last_vault_leaves_behind(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->default()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('vaults.settings.index', $vault))
            ->assertStatus(200)
            ->assertSee('Delete the vault')
            ->assertSee('Your account will hold nothing', false);
    }

    #[Test]
    public function it_says_another_vault_takes_the_default_over_when_one_is_left(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->default()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin']);
        Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Schrute Farms']);

        $this->actingAs($user)->get(route('vaults.settings.index', $vault))
            ->assertStatus(200)
            ->assertSee('Delete the vault')
            ->assertSee('another one becomes the vault new records go to', false)
            ->assertDontSee('Your account will hold nothing', false);
    }
}
