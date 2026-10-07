<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\DefaultTemplateEnum;
use App\Enums\StructureAppliesToEnum;
use App\Jobs\CreateDefaultTemplate;
use App\Models\Contact;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use App\ViewModels\Vaults\ContactFormViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactFormViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_starts_the_form_empty_and_on_the_template_the_vault_uses(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $version = $this->contactDefaultOf($vault);

        $values = new ContactFormViewModel(user: $user, vault: $vault)->values();

        $this->assertEquals('', $values['nickname']);
        $this->assertSame([], new ContactFormViewModel(user: $user, vault: $vault)->nameEditor()->rows());
        $this->assertEquals((string) $version, $values['structureVersionId']);
        $this->assertFalse($values['isFavourite']);
        $this->assertFalse($values['isPartial']);
        $this->assertFalse($values['keepGoing']);
    }

    #[Test]
    public function it_offers_the_template_only_when_the_account_has_more_than_one(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $this->assertFalse(new ContactFormViewModel(user: $user, vault: $vault)->showsTemplates());

        $this->publishedContactTemplate($user, 'Work contact');

        $viewModel = new ContactFormViewModel(user: $user, vault: $vault);

        $this->assertTrue($viewModel->showsTemplates());
        $this->assertCount(2, $viewModel->templates());
    }

    #[Test]
    public function it_does_not_offer_the_animal_template_to_a_contact(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        new CreateDefaultTemplate(user: $user, supplied: DefaultTemplateEnum::BasicAnimal)->handle();

        $viewModel = new ContactFormViewModel(user: $user, vault: $vault);

        $this->assertFalse($viewModel->showsTemplates());
        $this->assertNotContains('Basic animal', array_column($viewModel->templates(), 'name'));
    }

    #[Test]
    public function it_leaves_the_templates_of_other_accounts_out(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $this->publishedContactTemplate(User::factory()->create(), 'Beet varieties');

        $names = array_column(new ContactFormViewModel(user: $user, vault: $vault)->templates(), 'name');

        $this->assertNotContains('Beet varieties', $names);
    }

    #[Test]
    public function it_offers_the_two_flags_and_not_the_contact_representing_you(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $flags = new ContactFormViewModel(user: $user, vault: $vault)->flags();

        $this->assertEquals(['is_favourite', 'is_partial'], array_column($flags, 'id'));
        $this->assertNotContains('is_me', array_column($flags, 'id'));
    }

    #[Test]
    public function it_stays_quiet_while_the_form_can_do_its_job(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $viewModel = new ContactFormViewModel(user: $user, vault: $vault);

        $this->assertTrue($viewModel->canCreate());
        $this->assertNull($viewModel->blocker());
    }

    #[Test]
    public function it_says_so_when_the_vault_has_nothing_to_start_a_contact_from(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $viewModel = new ContactFormViewModel(user: $user, vault: $vault);

        $this->assertFalse($viewModel->canCreate());
        $this->assertStringContainsString('no template for new contacts', (string) $viewModel->blocker());
    }

    #[Test]
    public function it_says_where_the_form_leads_out_of(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $viewModel = new ContactFormViewModel(user: $user, vault: $vault);

        $this->assertEquals(route('contacts.index', $vault), $viewModel->contactsUrl());
        $this->assertEquals(route('contacts.create', $vault), $viewModel->action());
    }

    #[Test]
    public function it_asks_the_database_once_however_often_the_form_reads_its_starting_template(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);

        $viewModel = new ContactFormViewModel(user: $user, vault: $vault);
        $viewModel->values();
        $viewModel->canCreate();

        DB::enableQueryLog();
        $viewModel->values();
        $viewModel->canCreate();
        $viewModel->blocker();
        $again = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(0, $again);
        $this->assertTrue($viewModel->canCreate());
    }

    private function vaultReadyForContacts(User $user, string $name = 'Basic contact'): Vault
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = $this->publishedContactTemplate($user, $name);

        VaultDefaultTemplate::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $template->structure->published_version_id,
        ]);

        return $vault;
    }

    private function contactDefaultOf(Vault $vault): int
    {
        return (int) VaultDefaultTemplate::query()
            ->where('vault_id', $vault->id)
            ->where('applies_to', StructureAppliesToEnum::Contact)
            ->value('structure_version_id');
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

    #[Test]
    public function it_names_the_contact_ceiling_once_the_account_is_at_it(): void
    {
        config(['monica.free_contact_limit' => 1]);

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);

        $this->assertSame(
            'The free plan holds 1 contact across every vault, and this account already has it. Full access lifts the ceiling.',
            new ContactFormViewModel(user: $user, vault: $vault)->ceilingMessage(),
        );
    }

    #[Test]
    public function it_says_nothing_about_the_contact_ceiling_below_it(): void
    {
        config(['monica.free_contact_limit' => 30]);

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->assertNull(new ContactFormViewModel(user: $user, vault: $vault)->ceilingMessage());
    }

    #[Test]
    public function it_says_nothing_about_a_contact_ceiling_there_is_none_of(): void
    {
        config(['monica.free_contact_limit' => 1]);

        $user = User::factory()->create();
        $this->subscribeAccount($user->account);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Contact::factory()->count(3)->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);

        $this->assertNull(new ContactFormViewModel(user: $user->fresh(), vault: $vault)->ceilingMessage());
    }
}
