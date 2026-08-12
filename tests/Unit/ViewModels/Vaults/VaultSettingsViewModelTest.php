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
        $this->assertEquals(['id' => (int) $standard->published_version_id, 'name' => 'Standard contact'], $templates[0]);
        $this->assertEquals('Work contact', $templates[1]['name']);
    }

    #[Test]
    public function it_only_calls_it_a_choice_once_the_account_has_two_templates(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $this->publishedContactTemplate($user, 'Standard contact');

        $this->assertFalse(new VaultSettingsViewModel(user: $user, vault: $vault)->hasTemplateChoice());

        $this->publishedContactTemplate($user, 'Work contact');

        $this->assertTrue(new VaultSettingsViewModel(user: $user, vault: $vault)->hasTemplateChoice());
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
