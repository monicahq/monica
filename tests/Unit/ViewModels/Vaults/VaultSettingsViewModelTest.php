<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\TemplateAppliesToEnum;
use App\Enums\VaultColourEnum;
use App\Models\Template;
use App\Models\TemplateVersion;
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
    public function it_says_what_a_deletion_takes_with_it(): void
    {
        $user = User::factory()->create();
        $one = Vault::factory()->create(['account_id' => $user->account_id, 'contact_count' => 1]);
        $several = Vault::factory()->create(['account_id' => $user->account_id, 'contact_count' => 4]);

        $this->assertSame(
            'The one contact in it is deleted too, and everything written about them.',
            new VaultSettingsViewModel(user: $user, vault: $one)->deletionConsequence(),
        );

        $this->assertSame(
            'The 4 contacts in it are deleted too, and everything written about them.',
            new VaultSettingsViewModel(user: $user, vault: $several)->deletionConsequence(),
        );
    }

    #[Test]
    public function it_says_nothing_about_losing_contacts_when_the_vault_holds_none(): void
    {
        $user = User::factory()->create();
        $empty = Vault::factory()->create(['account_id' => $user->account_id, 'contact_count' => 0]);

        $this->assertSame('', new VaultSettingsViewModel(user: $user, vault: $empty)->deletionConsequence());
    }

    #[Test]
    public function it_starts_the_naming_form_on_what_the_vault_holds(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
            'description' => 'The Scranton branch',
            'colour' => '#4f8a49',
        ]);

        $values = new VaultSettingsViewModel(user: $user, vault: $vault)->values();

        $this->assertEquals(
            ['name' => 'Dunder Mifflin', 'description' => 'The Scranton branch', 'colour' => '#4f8a49'],
            $values,
        );
    }

    #[Test]
    public function it_has_an_empty_description_when_the_vault_has_none(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'description' => null]);

        $this->assertEquals('', new VaultSettingsViewModel(user: $user, vault: $vault)->values()['description']);
    }

    #[Test]
    public function it_offers_every_colour_a_vault_may_be_told_apart_by(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $colours = new VaultSettingsViewModel(user: $user, vault: $vault)->colours();

        $this->assertCount(count(VaultColourEnum::cases()), $colours);
        $this->assertEquals(['value' => '#3f5fd0', 'label' => 'Blue'], $colours[0]);
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
            'applies_to' => TemplateAppliesToEnum::Contact,
            'template_version_id' => $template->published_version_id,
        ]);

        $viewModel = new VaultSettingsViewModel(user: $user, vault: $vault);

        $this->assertEquals('Work contact', $viewModel->currentTemplateName());
        $this->assertEquals((string) $template->published_version_id, $viewModel->currentTemplateVersionId());
    }

    #[Test]
    public function it_has_nothing_to_name_when_the_vault_points_at_no_template(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $viewModel = new VaultSettingsViewModel(user: $user, vault: $vault);

        $this->assertNull($viewModel->currentTemplateName());
        $this->assertEquals('', $viewModel->currentTemplateVersionId());
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
        $this->assertEquals(
            [['id' => (int) $standard->published_version_id, 'label' => 'Version 1 (latest)']],
            $templates[0]['versions'],
        );
        $this->assertEquals('Work contact', $templates[1]['name']);
    }

    #[Test]
    public function it_offers_every_published_version_of_a_template_newest_first(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = $this->publishedContactTemplate($user, 'Standard contact');
        $first = $template->published_version_id;

        $newest = TemplateVersion::factory()->published()->create([
            'template_id' => $template->id,
            'version_number' => 2,
        ]);
        $template->update(['published_version_id' => $newest->id]);

        TemplateVersion::factory()->create(['template_id' => $template->id, 'version_number' => 3]);

        $versions = new VaultSettingsViewModel(user: $user, vault: $vault)->templates()[0]['versions'];

        $this->assertEquals([
            ['id' => $newest->id, 'label' => 'Version 2 (latest)'],
            ['id' => (int) $first, 'label' => 'Version 1'],
        ], $versions);
    }

    #[Test]
    public function it_offers_the_choice_even_when_there_is_only_one_version(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = $this->publishedContactTemplate($user, 'Standard contact');

        $templates = new VaultSettingsViewModel(user: $user, vault: $vault)->templates();

        $this->assertCount(1, $templates);
        $this->assertEquals((int) $template->published_version_id, $templates[0]['versions'][0]['id']);
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

        $version = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);

        $template->published_version_id = $version->id;
        $template->save();

        return $template;
    }
}
