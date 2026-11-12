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
}
