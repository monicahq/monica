<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\Relationship;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactEditViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactEditViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_names_the_template_the_contact_is_rendered_from_and_links_to_it(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin staff']);
        $version = StructureVersion::factory()->published()->create([
            'account_id' => $user->account_id,
            'structure_id' => $template->structure->id,
        ]);
        $michael = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $version->id,
        ]);

        $viewModel = new ContactEditViewModel(user: $user, vault: $vault, contact: $michael);

        $this->assertTrue($template->is($viewModel->template()));
        $this->assertSame('Dunder Mifflin staff', $viewModel->template()->name);
        $this->assertSame(route('settings.templateBuilder.index', $template), $viewModel->templateUrl());
    }

    #[Test]
    public function it_names_no_template_once_the_template_is_gone(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dwight = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $dwight->structureVersion->structure->owner->delete();
        $dwight->refresh();

        $viewModel = new ContactEditViewModel(user: $user, vault: $vault, contact: $dwight);

        $this->assertNull($viewModel->template());
        $this->assertNull($viewModel->templateUrl());
    }

    #[Test]
    public function it_offers_to_make_a_full_contact_partial(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jan = $this->contactIn($vault, ['first_name' => 'Jan', 'last_name' => 'Levinson']);

        $demotion = new ContactEditViewModel(user: $user, vault: $vault, contact: $jan)->demotion();

        $this->assertSame('Keep Jan Levinson as a partial contact', $demotion['title']);
        $this->assertSame(route('contacts.partial.update', ['vault' => $vault, 'contact' => $jan]), $demotion['url']);
        $this->assertNull($demotion['refusal']);
    }

    #[Test]
    public function it_offers_nothing_for_a_partial_contact(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jan = $this->contactIn($vault, ['is_partial' => true]);

        $this->assertNull(new ContactEditViewModel(user: $user, vault: $vault, contact: $jan)->demotion());
    }

    #[Test]
    public function it_carries_the_refusal_for_the_contact_representing_the_account_holder(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $me = $this->contactIn($vault, ['is_me' => true]);

        $demotion = new ContactEditViewModel(user: $user, vault: $vault, contact: $me)->demotion();

        $this->assertSame('This is the contact that represents you, and it stays a full contact.', $demotion['refusal']);
    }

    #[Test]
    public function it_warns_where_a_contact_without_a_relationship_will_be_found(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jan = $this->contactIn($vault, ['first_name' => 'Jan', 'last_name' => 'Levinson']);

        $demotion = new ContactEditViewModel(user: $user, vault: $vault, contact: $jan)->demotion();

        $this->assertSame('Jan Levinson has no relationship, so you will find them only under Partial contacts in the list of people, or in search.', $demotion['reachabilityLine']);
    }

    #[Test]
    public function it_gives_no_warning_for_a_contact_holding_a_relationship(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jan = $this->contactIn($vault);
        $michael = $this->contactIn($vault);
        Relationship::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'contact_a_id' => $michael->id,
            'contact_b_id' => $jan->id,
        ]);

        $demotion = new ContactEditViewModel(user: $user, vault: $vault, contact: $jan)->demotion();

        $this->assertNull($demotion['reachabilityLine']);
    }

    /** @param array<string, mixed> $attributes */
    private function contactIn(Vault $vault, array $attributes = []): Contact
    {
        return Contact::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'is_partial' => false,
            ...$attributes,
        ]);
    }
}
