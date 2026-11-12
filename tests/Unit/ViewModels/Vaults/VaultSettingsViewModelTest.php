<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use App\ViewModels\Vaults\VaultSettingsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultSettingsViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_says_nothing_about_losing_contacts_when_the_vault_holds_none(): void
    {
        $user = User::factory()->create();
        $empty = Vault::factory()->create(['account_id' => $user->account_id, 'contact_count' => 0]);

        $this->assertSame('', new VaultSettingsViewModel(user: $user, vault: $empty)->deletionConsequence());
    }

    #[Test]
    public function it_names_what_the_vault_starts_its_contacts_on(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = $this->publishedContactTemplate($user, 'Work contact');

        VaultDefaultTemplate::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $template->structure->published_version_id,
        ]);

        $viewModel = new VaultSettingsViewModel(user: $user, vault: $vault);

        $this->assertEquals('Work contact', $viewModel->currentTemplateName());
        $this->assertEquals((string) $template->structure->published_version_id, $viewModel->currentStructureVersionId());
    }

    #[Test]
    public function it_has_nothing_to_name_when_the_vault_points_at_no_template(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $viewModel = new VaultSettingsViewModel(user: $user, vault: $vault);

        $this->assertNull($viewModel->currentTemplateName());
        $this->assertEquals('', $viewModel->currentStructureVersionId());
    }

    #[Test]
    public function it_offers_the_published_contact_templates_of_the_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $standard = $this->publishedContactTemplate($user, 'Standard contact');
        $this->publishedContactTemplate($user, 'Work contact', position: 2);
        Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Never published']);
        $this->publishedContactTemplate(User::factory()->create(), 'Beet varieties');

        $templates = new VaultSettingsViewModel(user: $user, vault: $vault)->templates();

        $this->assertCount(2, $templates);
        $this->assertEquals('Standard contact', $templates[0]['name']);
        $this->assertEquals([(int) $standard->structure->published_version_id], array_column($templates[0]['versions'], 'id'));
        $this->assertEquals('Work contact', $templates[1]['name']);
    }

    #[Test]
    public function it_offers_every_published_version_of_a_template_newest_first(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = $this->publishedContactTemplate($user, 'Standard contact');
        $first = $template->structure->published_version_id;

        $newest = StructureVersion::factory()->published()->create([
            'structure_id' => $template->structure->id,
            'version_number' => 2,
        ]);
        $template->structure->update(['published_version_id' => $newest->id]);

        StructureVersion::factory()->create(['structure_id' => $template->structure->id, 'version_number' => 3]);

        $versions = new VaultSettingsViewModel(user: $user, vault: $vault)->templates()[0]['versions'];

        $this->assertEquals([$newest->id, (int) $first], array_column($versions, 'id'));
    }

    #[Test]
    public function it_offers_the_choice_even_when_there_is_only_one_version(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = $this->publishedContactTemplate($user, 'Standard contact');

        $templates = new VaultSettingsViewModel(user: $user, vault: $vault)->templates();

        $this->assertCount(1, $templates);
        $this->assertEquals((int) $template->structure->published_version_id, $templates[0]['versions'][0]['id']);
    }

    #[Test]
    public function it_knows_when_the_vault_is_the_only_one_the_account_has(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->assertTrue(new VaultSettingsViewModel(user: $user, vault: $vault)->isTheLastVault());

        Vault::factory()->create(['account_id' => $user->account_id]);

        $this->assertFalse(new VaultSettingsViewModel(user: $user, vault: $vault)->isTheLastVault());
    }

    private function publishedContactTemplate(User $user, string $name, int $position = 1): Template
    {
        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'name' => $name,
            'position' => $position,
        ]);

        $version = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);

        $template->structure->update(['published_version_id' => $version->id]);

        return $template;
    }
}
