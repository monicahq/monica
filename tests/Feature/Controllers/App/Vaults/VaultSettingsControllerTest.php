<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\StructureAppliesToEnum;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_says_nothing_about_losing_contacts_when_the_vault_holds_none(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
            'contact_count' => 0,
        ]);

        $this->actingAs($user)->get(route('vaults.settings.index', $vault))
            ->assertStatus(200)
            ->assertSee('Delete this vault')
            ->assertDontSee('in it goes too')
            ->assertDontSee('in it go too');
    }

    #[Test]
    public function it_counts_the_contacts_a_deletion_would_take(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
            'contact_count' => 1,
        ]);

        $this->actingAs($user)->get(route('vaults.settings.index', $vault))
            ->assertStatus(200)
            ->assertSee('The one contact in it is deleted too, and everything written about them.');
    }

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
    public function it_asks_which_template_and_which_version_of_it(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $standard = $this->publishedContactTemplate($user, 'Standard contact');
        $this->pointVaultAt($vault, $standard);

        $this->actingAs($user)->get(route('vaults.settings.index', $vault))
            ->assertStatus(200)
            ->assertSee('Default template')
            ->assertSee('<optgroup label="Standard contact">', false)
            ->assertSee('Version 1 (latest)');

        $this->publishedContactTemplate($user, 'Work contact');

        $this->actingAs($user)->get(route('vaults.settings.index', $vault))
            ->assertStatus(200)
            ->assertSee('<optgroup label="Work contact">', false);
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

    #[Test]
    public function it_does_not_find_the_settings_of_a_vault_of_another_account(): void
    {
        $user = User::factory()->create();
        $elsewhere = Vault::factory()->create(['name' => 'Vance Refrigeration']);

        $this->actingAs($user)->get(route('vaults.settings.index', $elsewhere))->assertNotFound();
    }

    #[Test]
    public function it_keeps_somebody_who_is_not_signed_in_out(): void
    {
        $vault = Vault::factory()->create();

        $this->get(route('vaults.settings.index', $vault))->assertRedirect(route('auth.login.new'));
    }

    private function publishedContactTemplate(User $user, string $name): Template
    {
        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'name' => $name,
        ]);

        $version = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);

        $template->structure->update(['published_version_id' => $version->id]);

        return $template;
    }

    private function pointVaultAt(Vault $vault, Template $template): void
    {
        VaultDefaultTemplate::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $template->structure->published_version_id,
        ]);
    }
}
