<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\VersionStatusEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\Relationship;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
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
    public function it_names_the_template_the_contact_uses_and_its_version_when_there_are_several(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $staff = $this->templateOf($user, 'Dunder Mifflin staff');
        $michael = $this->contactIn($vault, ['structure_version_id' => $staff->structure->published_version_id]);

        $viewModel = new ContactEditViewModel(user: $user, vault: $vault, contact: $michael);

        $this->assertSame('Dunder Mifflin staff', $viewModel->currentTemplateName());
        $this->assertNull($viewModel->currentVersionLabel());

        $this->publish($staff, 2);

        $this->assertSame('Version 1', new ContactEditViewModel(user: $user, vault: $vault, contact: $michael)->currentVersionLabel());
    }

    #[Test]
    public function it_still_names_a_template_that_is_gone(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dwight = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $dwight->structureVersion->structure->owner->delete();
        $dwight->refresh();

        $viewModel = new ContactEditViewModel(user: $user, vault: $vault, contact: $dwight);

        $this->assertSame('A template that was deleted', $viewModel->currentTemplateName());
    }

    #[Test]
    public function it_lists_the_offered_templates_with_their_published_versions_newest_first(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $staff = $this->templateOf($user, 'Dunder Mifflin staff', position: 1);
        $first = $staff->structure->publishedVersion;
        $second = $this->publish($staff, 2);
        StructureVersion::factory()->create(['structure_id' => $staff->structure->id, 'version_number' => 3]);
        $client = $this->templateOf($user, 'Client', position: 2);
        $this->templateOf($user, 'Retired', position: 3)->update(['is_active' => false]);
        $michael = $this->contactIn($vault, ['structure_version_id' => $first->id]);

        $choices = new ContactEditViewModel(user: $user, vault: $vault, contact: $michael)->templateChoices();

        $this->assertSame(['Dunder Mifflin staff', 'Client'], array_column($choices, 'name'));
        $this->assertSame('2 versions', $choices[0]['countLabel']);
        $this->assertSame(['Version 2', 'Version 1'], array_column($choices[0]['versions'], 'label'));
        $this->assertSame('Latest', $choices[0]['versions'][0]['hint']);
        $this->assertSame([false, true], array_column($choices[0]['versions'], 'isCurrent'));
        $this->assertSame(route('contacts.edit', ['vault' => $vault, 'contact' => $michael, 'template' => $staff->uuid, 'version' => $second->id]), $choices[0]['versions'][0]['url']);
        $this->assertNull($choices[1]['countLabel']);
        $this->assertSame([['label' => 'Client', 'hint' => '', 'url' => route('contacts.edit', ['vault' => $vault, 'contact' => $michael, 'template' => $client->uuid, 'version' => $client->structure->published_version_id]), 'isCurrent' => false]], $choices[1]['versions']);
    }

    #[Test]
    public function it_shows_no_template_panel_when_nothing_else_is_offered(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $staff = $this->templateOf($user, 'Dunder Mifflin staff');
        $michael = $this->contactIn($vault, ['structure_version_id' => $staff->structure->published_version_id]);

        $this->assertFalse(new ContactEditViewModel(user: $user, vault: $vault, contact: $michael)->showsTemplatePanel());

        $this->templateOf($user, 'Client');

        $this->assertTrue(new ContactEditViewModel(user: $user, vault: $vault, contact: $michael)->showsTemplatePanel());
    }

    #[Test]
    public function it_prepares_a_change_only_for_another_version_than_the_current_one(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $staff = $this->templateOf($user, 'Dunder Mifflin staff');
        $client = $this->templateOf($user, 'Client');
        $michael = $this->contactIn($vault, ['structure_version_id' => $staff->structure->published_version_id, 'first_name' => 'Michael']);

        $this->assertNull(new ContactEditViewModel(user: $user, vault: $vault, contact: $michael)->pendingChange());
        $this->assertNull(new ContactEditViewModel(user: $user, vault: $vault, contact: $michael, askedTemplate: $staff->uuid)->pendingChange());

        $change = new ContactEditViewModel(user: $user, vault: $vault, contact: $michael, askedTemplate: $client->uuid)->pendingChange();

        $this->assertSame('Switch Michael to the Client template?', $change['title']);
        $this->assertSame($client->structure->published_version_id, $change['versionId']);
        $this->assertSame(route('contacts.template.update', ['vault' => $vault, 'contact' => $michael]), $change['action']);
        $this->assertNull($change['refusal']);

        $this->publish($client, 2);

        $this->assertSame('Switch Michael to the Client template (version 2)?', new ContactEditViewModel(user: $user, vault: $vault, contact: $michael, askedTemplate: $client->uuid)->pendingChange()['title']);
    }

    #[Test]
    public function it_previews_the_answers_a_change_sets_aside(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $staff = $this->templateOf($user, 'Dunder Mifflin staff');
        $client = $this->templateOf($user, 'Client');
        $michael = $this->contactIn($vault, ['structure_version_id' => $staff->structure->published_version_id, 'first_name' => 'Michael']);
        $desk = $this->fieldOn($staff, 'Desk');
        FieldValue::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'valuable_type' => Contact::class,
            'valuable_id' => $michael->id,
            'field_id' => $desk->id,
            'value_string' => 'Corner office',
        ]);

        $preview = new ContactEditViewModel(user: $user, vault: $vault, contact: $michael, askedTemplate: $client->uuid)->preview();

        $this->assertCount(1, $preview);
        $this->assertSame('Information moving to Unused answers', $preview[0]['title']);
        $this->assertSame([['text' => 'Desk', 'section' => $desk->sectionVersion->section->name, 'count' => null]], $preview[0]['lines']);
        $this->assertNotNull($preview[0]['note']);
        $this->assertNull(new ContactEditViewModel(user: $user, vault: $vault, contact: $michael)->preview());
    }

    #[Test]
    public function it_carries_the_refusal_for_a_template_that_is_turned_off(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $staff = $this->templateOf($user, 'Dunder Mifflin staff');
        $retired = $this->templateOf($user, 'Retired');
        $retired->update(['is_active' => false]);
        $michael = $this->contactIn($vault, ['structure_version_id' => $staff->structure->published_version_id]);

        $viewModel = new ContactEditViewModel(user: $user, vault: $vault, contact: $michael, askedTemplate: $retired->uuid);

        $this->assertNotNull($viewModel->pendingChange()['refusal']);
        $this->assertNull($viewModel->preview());
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

    private function templateOf(User $user, string $name, int $position = 1): Template
    {
        return Template::factory()->published()->create(['account_id' => $user->account_id, 'name' => $name, 'position' => $position])->refresh();
    }

    private function publish(Template $template, int $number): StructureVersion
    {
        $version = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id, 'version_number' => $number]);
        $template->structure->update(['published_version_id' => $version->id]);

        return $version;
    }

    private function fieldOn(Template $template, string $label): Field
    {
        $version = $template->structure->publishedVersion;
        $version->update(['status' => VersionStatusEnum::Draft]);
        $sectionVersion = SectionVersion::factory()->create(['account_id' => $template->account_id]);
        $field = Field::factory()->create(['account_id' => $template->account_id, 'section_version_id' => $sectionVersion->id, 'label' => $label]);

        StructureVersionSection::factory()->create([
            'account_id' => $template->account_id,
            'structure_version_id' => $version->id,
            'structure_version_page_id' => $version->pages->first()->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $sectionVersion->update(['status' => VersionStatusEnum::Published]);
        $version->update(['status' => VersionStatusEnum::Published]);

        return $field;
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
