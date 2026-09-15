<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\VaultFormViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultFormViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_starts_the_form_empty_and_on_the_colour_a_vault_defaults_to(): void
    {
        $user = User::factory()->create();

        $values = new VaultFormViewModel(user: $user)->values();

        $this->assertEquals('', $values['name']);
        $this->assertEquals('', $values['description']);
        $this->assertEquals(Vault::DEFAULT_COLOUR, $values['colour']);
    }

    #[Test]
    public function it_offers_the_colours_a_vault_may_be_told_apart_by(): void
    {
        $user = User::factory()->create();

        $colours = new VaultFormViewModel(user: $user)->colours();

        $this->assertCount(8, $colours);
        $this->assertEquals(['value' => Vault::DEFAULT_COLOUR, 'label' => 'Blue'], $colours[0]);
    }

    #[Test]
    public function it_ticks_the_default_box_for_the_first_vault_of_an_account(): void
    {
        $user = User::factory()->create();

        $viewModel = new VaultFormViewModel(user: $user);

        $this->assertTrue($viewModel->isFirstVault());
        $this->assertTrue($viewModel->values()['isDefault']);
        $this->assertEquals(
            'This is your first vault, so it is the default until you make another one.',
            $viewModel->defaultHintWhenTicked(),
        );
    }

    #[Test]
    public function it_leaves_the_default_box_alone_once_the_account_holds_a_vault(): void
    {
        $user = User::factory()->create();
        Vault::factory()->default()->create(['account_id' => $user->account_id, 'name' => 'Personal']);

        $viewModel = new VaultFormViewModel(user: $user);

        $this->assertFalse($viewModel->isFirstVault());
        $this->assertFalse($viewModel->values()['isDefault']);
        $this->assertEquals('Personal', $viewModel->currentDefaultName());
        $this->assertEquals(
            'Personal stops being the default when this vault is created.',
            $viewModel->defaultHintWhenTicked(),
        );
        $this->assertEquals('Personal stays the default.', $viewModel->defaultHintWhenLeft());
    }

    #[Test]
    public function it_offers_the_contact_templates_the_account_can_start_from(): void
    {
        $user = User::factory()->create();
        $standard = $this->publishedContactTemplate($user, 'Standard contact', position: 1);
        $work = $this->publishedContactTemplate($user, 'Work contact', position: 2);

        $viewModel = new VaultFormViewModel(user: $user);

        $this->assertTrue($viewModel->showsTemplates());
        $this->assertEquals(
            [
                ['id' => $standard->structure->published_version_id, 'name' => 'Standard contact'],
                ['id' => $work->structure->published_version_id, 'name' => 'Work contact'],
            ],
            $viewModel->templates(),
        );
    }

    #[Test]
    public function it_leaves_out_a_template_nothing_can_start_from(): void
    {
        $user = User::factory()->create();
        $this->publishedContactTemplate($user, 'Standard contact');
        Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Still a draft']);
        Template::factory()->inactive()->create(['account_id' => $user->account_id, 'name' => 'No longer offered']);
        Template::factory()->forEntity()->create(['account_id' => $user->account_id, 'name' => 'Animal']);
        $this->publishedContactTemplate(User::factory()->create(), 'Beet varieties');

        $names = array_column(new VaultFormViewModel(user: $user)->templates(), 'name');

        $this->assertEquals(['Standard contact'], $names);
    }

    #[Test]
    public function it_asks_nothing_about_templates_while_there_is_only_one(): void
    {
        $user = User::factory()->create();
        $this->publishedContactTemplate($user, 'Standard contact');

        $this->assertFalse(new VaultFormViewModel(user: $user)->showsTemplates());
    }

    #[Test]
    public function it_starts_on_the_template_the_product_supplied(): void
    {
        $user = User::factory()->create();
        $this->publishedContactTemplate($user, 'Work contact', position: 1);
        $supplied = $this->publishedContactTemplate($user, 'Basic contact', position: 2, system: true);

        $this->assertEquals(
            (string) $supplied->structure->published_version_id,
            new VaultFormViewModel(user: $user)->values()['structureVersionId'],
        );
    }

    #[Test]
    public function it_says_nothing_about_a_ceiling_below_it(): void
    {
        config(['monica.free_vault_limit' => 2]);

        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => $user->account_id]);

        $this->assertNull(new VaultFormViewModel(user: $user)->ceilingMessage());
    }

    #[Test]
    public function it_names_the_ceiling_once_the_account_is_at_it(): void
    {
        config(['monica.free_vault_limit' => 1]);

        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => $user->account_id]);

        $this->assertSame(
            'The free offer holds 1 vault, and this account already has it. Full access lifts the ceiling.',
            new VaultFormViewModel(user: $user)->ceilingMessage(),
        );
    }

    #[Test]
    public function it_says_nothing_about_a_ceiling_there_is_none_of(): void
    {
        config(['monica.free_vault_limit' => 1]);

        $user = User::factory()->create();
        $user->account->update(['access_granted_at' => now()]);
        Vault::factory()->count(3)->create(['account_id' => $user->account_id]);

        $this->assertNull(new VaultFormViewModel(user: $user->fresh())->ceilingMessage());
    }

    private function publishedContactTemplate(User $user, string $name, int $position = 1, bool $system = false): Template
    {
        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'name' => $name,
            'position' => $position,
            'is_system' => $system,
        ]);

        $version = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);

        $template->structure->update(['published_version_id' => $version->id]);

        return $template;
    }
}
