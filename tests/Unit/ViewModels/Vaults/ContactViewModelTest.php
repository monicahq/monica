<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\FieldTypeEnum;
use App\Enums\ReminderCadenceEnum;
use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\VaultColourEnum;
use App\Enums\VersionStatusEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\File;
use App\Models\RecordSnapshot;
use App\Models\Relationship;
use App\Models\Reminder;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactViewModel;
use App\ViewModels\Vaults\FamilyTreeBlock;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_tabs_are_the_overview_and_then_the_pages_of_the_version(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee', 'Sleep']]]);
        $contact = $this->contact($user, $version);

        $pages = (new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact))->pages();

        $this->assertSame(ContactViewModel::PAGE_OVERVIEW, $pages[0]['key']);
        $this->assertNull($pages[0]['count']);
        $this->assertTrue($pages[0]['isSelected']);

        $this->assertSame('health', $pages[1]['key']);
        $this->assertSame('Health', $pages[1]['label']);
        $this->assertSame(2, $pages[1]['count']);
        $this->assertFalse($pages[1]['isSelected']);
    }

    #[Test]
    public function the_page_the_key_names_is_the_one_that_is_open(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee']]]);
        $contact = $this->contact($user, $version);

        $viewModel = new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact, selectedPageKey: 'health');

        $this->assertFalse($viewModel->onOverview());
        $this->assertSame('Health', $viewModel->selectedPage()?->label);
    }

    #[Test]
    public function a_key_the_version_does_not_have_falls_back_to_the_overview(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee']]]);
        $contact = $this->contact($user, $version);

        foreach ([null, 'overview', 'schrute-farms'] as $key) {
            $viewModel = new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact, selectedPageKey: $key);

            $this->assertTrue($viewModel->onOverview());
            $this->assertSame([], $viewModel->sections());
        }
    }

    #[Test]
    public function the_open_page_carries_its_sections_and_the_fields_inside_them(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee'], 'Practitioners' => ['Doctor']]]);
        $contact = $this->contact($user, $version);

        $sections = (new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact, selectedPageKey: 'health'))->sections();

        $this->assertCount(2, $sections);
        $this->assertSame('Ongoing', $sections[0]['name']);
        $this->assertSame('Knee', $sections[0]['fields'][0]['label']);
        $this->assertSame('Practitioners', $sections[1]['name']);
    }

    #[Test]
    public function a_block_wears_the_mark_of_the_section_it_draws(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee']]]);
        $contact = $this->contact($user, $version);
        $version->assembly->first()->sectionVersion->section->update(['icon' => 'health', 'colour' => '#4f8a49']);

        $sections = (new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact, selectedPageKey: 'health'))->sections();

        $this->assertSame('health', $sections[0]['icon']);
        $this->assertSame(VaultColourEnum::Green->tint(), $sections[0]['tint']);
    }

    #[Test]
    public function a_block_on_an_unmarked_section_falls_back_to_the_blank_page_and_the_neutral_pair(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee']]]);
        $contact = $this->contact($user, $version);

        $sections = (new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact, selectedPageKey: 'health'))->sections();

        $this->assertSame(Section::DEFAULT_ICON, $sections[0]['icon']);
        $this->assertSame(VaultColourEnum::neutralTint(), $sections[0]['tint']);
    }

    #[Test]
    public function a_block_falls_back_when_the_section_holds_a_glyph_it_may_not_use(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee']]]);
        $contact = $this->contact($user, $version);
        $version->assembly->first()->sectionVersion->section->update(['icon' => 'chevron-down', 'colour' => '#ff0000']);

        $sections = (new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact, selectedPageKey: 'health'))->sections();

        $this->assertSame(Section::DEFAULT_ICON, $sections[0]['icon']);
        $this->assertSame(VaultColourEnum::neutralTint(), $sections[0]['tint']);
    }

    #[Test]
    public function a_block_says_where_it_is_opened_and_where_it_is_written(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee']]]);
        $contact = $this->contact($user, $version);
        $row = $version->assembly->first();

        $sections = (new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact, selectedPageKey: 'health'))->sections();

        $this->assertSame("section-{$row->id}", $sections[0]['regionId']);
        $this->assertFalse($sections[0]['isEditing']);
        $this->assertTrue($sections[0]['isEditable']);
        $this->assertSame(
            route('contacts.sections.edit', ['vault' => $contact->vault, 'contact' => $contact, 'structureVersionSection' => $row]),
            $sections[0]['editUrl'],
        );
        $this->assertSame(
            route('contacts.sections.update', ['vault' => $contact->vault, 'contact' => $contact, 'structureVersionSection' => $row]),
            $sections[0]['saveUrl'],
        );
    }

    #[Test]
    public function only_the_block_that_was_asked_for_is_the_one_being_edited(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee'], 'Practitioners' => ['Doctor']]]);
        $contact = $this->contact($user, $version);

        $sections = (new ContactViewModel(
            user: $user,
            vault: $contact->vault,
            contact: $contact,
            selectedPageKey: 'health',
            editingSection: $version->assembly->first(),
        ))->sections();

        $this->assertTrue($sections[0]['isEditing']);
        $this->assertFalse($sections[1]['isEditing']);
    }

    #[Test]
    public function a_block_with_nothing_fillable_in_it_is_not_offered_an_edit(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Doctor']]]);
        $contact = $this->contact($user, $version);

        $field = Field::query()->where('label', 'Doctor')->firstOrFail();
        $field->sectionVersion->update(['status' => VersionStatusEnum::Draft]);
        $field->update(['type' => FieldTypeEnum::Relation]);

        $sections = (new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact, selectedPageKey: 'health'))->sections();

        $this->assertFalse($sections[0]['isEditable']);
    }

    #[Test]
    public function it_says_where_the_picture_of_the_contact_is_read_from(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee']]]);
        $contact = $this->contact($user, $version);

        $this->assertNull(
            new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact)->avatarUrl(),
        );

        $file = File::factory()->image()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
        ]);

        $contact->avatar_file_id = $file->id;
        $contact->save();

        $this->assertSame(
            route('files.show', ['vault' => $contact->vault, 'file' => $file]),
            new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact->refresh())->avatarUrl(),
        );
    }

    #[Test]
    public function it_says_where_the_pages_of_the_record_are_decided(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee']]], 'Someone in the family');
        $contact = $this->contact($user, $version);

        $viewModel = new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact);

        $this->assertSame(
            route('settings.templateBuilder.index', $version->structure->owner),
            $viewModel->templateUrl(),
        );
    }

    #[Test]
    public function a_version_with_no_page_still_draws_the_overview(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);
        $version->pages()->delete();
        $contact = $this->contact($user, $version->refresh());

        $viewModel = new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact);

        $tabs = $viewModel->pages();

        $this->assertCount(2, $tabs);
        $this->assertSame(ContactViewModel::PAGE_OVERVIEW, $tabs[0]['key']);
        $this->assertSame(ContactViewModel::PAGE_RELATIONSHIPS, $tabs[1]['key']);
        $this->assertTrue($viewModel->onOverview());
    }

    #[Test]
    public function the_last_thing_beside_the_photograph_is_how_long_it_has_been(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee']]]);
        $contact = $this->contact($user, $version, ['last_interaction_at' => now()->subDays(4)]);

        $facts = (new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact))->headerFacts();

        $this->assertStringContainsString('days ago', end($facts)['value']);
    }

    #[Test]
    public function somebody_nobody_has_spoken_to_says_so(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee']]]);
        $contact = $this->contact($user, $version);

        $facts = (new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact))->headerFacts();

        $this->assertSame('never', end($facts)['value']);
    }

    #[Test]
    public function it_reads_back_what_was_written_in_a_field(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee']]]);
        $contact = $this->contact($user, $version);
        $this->write($contact, $this->fieldCalled($version, 'Knee'), 'Twisted it skiing');

        $viewModel = new ContactViewModel(
            user: $user,
            vault: $contact->vault,
            contact: $contact,
            selectedPageKey: 'health',
        );

        $this->assertSame('Twisted it skiing', $viewModel->sections()[0]['fields'][0]['value']);
    }

    #[Test]
    public function a_field_nobody_has_answered_reads_as_nothing(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee']]]);
        $contact = $this->contact($user, $version);

        $viewModel = new ContactViewModel(
            user: $user,
            vault: $contact->vault,
            contact: $contact,
            selectedPageKey: 'health',
        );

        $this->assertNull($viewModel->sections()[0]['fields'][0]['value']);
    }

    #[Test]
    public function a_value_set_aside_is_not_on_the_page_at_all(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee']]]);
        $contact = $this->contact($user, $version);
        $value = $this->write($contact, $this->fieldCalled($version, 'Knee'), 'Twisted it skiing');
        $value->update(['is_detached' => true, 'detached_at' => now()]);

        $viewModel = new ContactViewModel(
            user: $user,
            vault: $contact->vault,
            contact: $contact,
            selectedPageKey: 'health',
        );

        $this->assertNull($viewModel->sections()[0]['fields'][0]['value']);
    }

    #[Test]
    public function a_highlighted_field_reaches_the_header_from_a_page_that_is_not_open(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, [
            'Health' => ['Ongoing' => ['Knee']],
            'Work' => ['Job' => ['Employer']],
        ]);
        $employer = $this->fieldCalled($version, 'Employer');
        $employer->update(['is_highlighted' => true]);
        $contact = $this->contact($user, $version->refresh());
        $this->write($contact, $employer, 'Dunder Mifflin');

        $facts = new ContactViewModel(
            user: $user,
            vault: $contact->vault,
            contact: $contact,
            selectedPageKey: 'health',
        )->headerFacts();

        $this->assertSame('Employer', $facts[0]['label']);
        $this->assertSame('Dunder Mifflin', $facts[0]['value']);
    }

    #[Test]
    public function the_facts_panel_asks_what_the_template_asks_and_nothing_else(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee']]]);
        $contact = $this->contact($user, $version, ['nickname' => 'Prison Mike']);

        $facts = new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact)->facts();

        $this->assertCount(1, $facts);
        $this->assertSame('Prison Mike', $facts[0]['value']);
    }

    #[Test]
    public function it_offers_the_newer_version_of_the_template(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee']]], 'Friend');
        $contact = $this->contact($user, $version);
        $this->publish($version, 2, 'Ways to connect is its own section now.');

        $update = new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact)->templateUpdate();

        $this->assertSame('Version 2 of the Friend template is ready', $update['title']);
        $this->assertStringStartsWith('Ways to connect is its own section now.', $update['body']);
        $this->assertStringContainsString('Michael', $update['body']);
        $this->assertSame('Apply version 2', $update['label']);
        $this->assertSame(
            route('contacts.templateVersion.update', ['vault' => $contact->vault, 'contact' => $contact]),
            $update['applyUrl'],
        );
    }

    #[Test]
    public function nothing_is_offered_when_the_contact_is_on_the_published_version(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee']]]);
        $contact = $this->contact($user, $version);
        $version->structure->update(['published_version_id' => $version->id]);

        $this->assertNull(new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact)->templateUpdate());
    }

    #[Test]
    public function nothing_is_offered_when_the_published_version_is_not_newer(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $current = StructureVersion::factory()->published()->create([
            'structure_id' => $template->structure->id,
            'version_number' => 2,
        ]);
        $contact = $this->contact($user, $current);

        $this->assertNull(new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact)->templateUpdate());

        $older = StructureVersion::factory()->published()->create([
            'structure_id' => $template->structure->id,
            'version_number' => 1,
        ]);
        $template->structure->update(['published_version_id' => $older->id]);

        $this->assertNull(new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact->refresh())->templateUpdate());
    }

    #[Test]
    public function the_history_of_a_contact_nobody_has_moved_is_one_line(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee']]], 'Friend');
        $contact = $this->contact($user, $version);

        $history = new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact)->versionHistory();

        $this->assertCount(1, $history);
        $this->assertSame('Friend, version 1', $history[0]['title']);
        $this->assertTrue($history[0]['isCurrent']);
        $this->assertNull($history[0]['revert']);
        $this->assertNull($history[0]['refusal']);
        $this->assertSame(0, $history[0]['spaceBelow']);
        $this->assertStringContainsString('and counting', $history[0]['span']);
    }

    #[Test]
    public function an_older_version_of_the_same_template_offers_a_revert_that_warns_first(): void
    {
        $user = User::factory()->create();
        $first = $this->version($user, ['Health' => ['Ongoing' => ['Knee']]], 'Friend');
        $contact = $this->contact($user, $first);
        $second = $this->publish($first, 2);

        $this->moved($contact, $first, $second, $user, now()->subDays(3));

        $history = new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact)->versionHistory();

        $this->assertCount(2, $history);
        $this->assertSame('Friend, version 2', $history[0]['title']);
        $this->assertTrue($history[0]['isCurrent']);
        $this->assertNull($history[0]['revert']);

        $revert = $history[1]['revert'];

        $this->assertSame($first->id, $revert['versionId']);
        $this->assertSame(route('contacts.templateVersion.update', ['vault' => $contact->vault, 'contact' => $contact]), $revert['url']);
        $this->assertSame('Revert to Friend, version 1?', $revert['title']);
        $this->assertSame('Stay on version 2', $revert['cancel']);
        $this->assertStringContainsString('hidden, not deleted', $revert['body']);
        $this->assertNull($history[1]['refusal']);
    }

    #[Test]
    public function the_space_below_a_version_grows_with_how_long_the_one_under_it_was_in_use(): void
    {
        $user = User::factory()->create();
        $first = $this->version($user, ['Health' => ['Ongoing' => ['Knee']]]);
        $contact = $this->contact($user, $first, ['created_at' => now()->subDays(400)]);
        $second = $this->publish($first, 2);
        $third = $this->publish($second, 3);

        $this->moved($contact, $first, $second, $user, now()->subDays(340));
        $this->moved($contact, $second, $third, $user, now()->subDays(300));

        $history = new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact)->versionHistory();

        $this->assertSame(40, $history[0]['spaceBelow']);
        $this->assertSame(50, $history[1]['spaceBelow']);
        $this->assertSame(0, $history[2]['spaceBelow']);

        $this->moved($contact, $third, $first, $user, now()->subDays(10));

        $history = new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact->refresh())->versionHistory();

        $this->assertSame(96, $history[0]['spaceBelow']);
    }

    #[Test]
    public function a_snapshot_that_moved_nothing_is_not_a_line(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee']]]);
        $contact = $this->contact($user, $version);

        RecordSnapshot::factory()->create([
            'snapshotable_type' => Contact::class,
            'snapshotable_id' => $contact->id,
            'structure_version_id' => $version->id,
            'created_at' => now()->subDay(),
        ]);

        $history = new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact)->versionHistory();

        $this->assertCount(1, $history);
        $this->assertTrue($history[0]['isCurrent']);
    }

    #[Test]
    public function a_retired_version_says_why_it_cannot_be_reverted_to(): void
    {
        $user = User::factory()->create();
        $first = $this->version($user, ['Health' => ['Ongoing' => ['Knee']]]);
        $contact = $this->contact($user, $first);
        $second = $this->publish($first, 2);

        $this->moved($contact, $first, $second, $user, now()->subDay());
        $first->update(['status' => VersionStatusEnum::Retired]);

        $history = new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact->refresh())->versionHistory();

        $this->assertNull($history[1]['revert']);
        $this->assertSame('This version has been retired, so nothing can be put back on it.', $history[1]['refusal']);
    }

    #[Test]
    public function a_version_of_another_template_says_why_it_cannot_be_reverted_to(): void
    {
        $user = User::factory()->create();
        $first = $this->version($user, ['Health' => ['Ongoing' => ['Knee']]], 'Acquaintance');
        $contact = $this->contact($user, $first);
        $elsewhere = $this->version($user, ['Health' => ['Ongoing' => ['Knee']]], 'Friend');

        $this->moved($contact, $first, $elsewhere, $user, now()->subDay());

        $history = new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact->refresh())->versionHistory();

        $this->assertNull($history[1]['revert']);
        $this->assertSame('This version belongs to another template, so it cannot be reverted to here.', $history[1]['refusal']);
    }

    #[Test]
    public function going_back_to_a_version_is_a_line_of_its_own(): void
    {
        $user = User::factory()->create();
        $first = $this->version($user, ['Health' => ['Ongoing' => ['Knee']]], 'Friend');
        $contact = $this->contact($user, $first);
        $second = $this->publish($first, 2);

        $this->moved($contact, $first, $second, $user, now()->subDays(4));
        $this->moved($contact, $second, $first, $user, now()->subDays(2));

        $history = new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact)->versionHistory();

        $this->assertCount(3, $history);
        $this->assertSame('Friend, version 1', $history[0]['title']);
        $this->assertTrue($history[0]['isCurrent']);
        $this->assertSame('Friend, version 2', $history[1]['title']);
        $this->assertSame('Friend, version 1', $history[2]['title']);
    }

    #[Test]
    public function it_says_how_much_is_being_kept_out_of_sight_only_when_something_is(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee']]]);
        $contact = $this->contact($user, $version);

        $this->assertNull(new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact)->setAsideLine());

        $knee = $this->fieldCalled($version, 'Knee');
        $this->write($contact, $knee, 'Sore')->update(['is_detached' => true, 'detached_at' => now()]);

        $viewModel = new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact->refresh());

        $this->assertSame(1, $viewModel->setAsideCount());
        $this->assertSame('One value written under another version is hidden.', $viewModel->setAsideLine());
    }

    #[Test]
    public function version_history_is_reached_from_the_actions_menu_rather_than_a_tab(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee']]]);
        $contact = $this->contact($user, $version);

        $viewModel = new ContactViewModel(
            user: $user,
            vault: $contact->vault,
            contact: $contact,
            selectedPageKey: ContactViewModel::PAGE_VERSION_HISTORY,
        );

        $tabs = $viewModel->pages();

        $this->assertNotContains(ContactViewModel::PAGE_VERSION_HISTORY, array_column($tabs, 'key'));
        $this->assertNotContains(true, array_column($tabs, 'isSelected'));
        $this->assertSame(
            route('contacts.show', ['vault' => $contact->vault, 'contact' => $contact, 'page' => 'version-history']),
            $viewModel->versionHistoryUrl(),
        );

        $this->assertTrue($viewModel->onVersionHistory());
        $this->assertFalse($viewModel->onOverview());
        $this->assertSame([], $viewModel->sections());
    }

    private function moved(Contact $contact, StructureVersion $from, StructureVersion $to, User $by, CarbonImmutable $when): void
    {
        RecordSnapshot::factory()->beforeAStructureChange($from)->create([
            'snapshotable_type' => Contact::class,
            'snapshotable_id' => $contact->id,
            'created_by_user_id' => $by->id,
            'created_at' => $when,
        ]);

        $contact->update(['structure_version_id' => $to->id]);
    }

    private function publish(StructureVersion $version, int $number, ?string $note = null): StructureVersion
    {
        $published = StructureVersion::factory()->published()->create([
            'structure_id' => $version->structure_id,
            'version_number' => $number,
            'change_note' => $note,
            'previous_version_id' => $version->id,
        ]);

        $version->structure->update(['published_version_id' => $published->id]);

        return $published;
    }

    #[Test]
    public function it_gives_every_date_field_what_the_reminder_panel_needs(): void
    {
        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee', 'Birthday']]]);
        $contact = $this->contact($user, $version, ['first_name' => 'Michael', 'last_name' => 'Scott']);
        $birthday = $this->fieldCalled($version, 'Birthday');
        $birthday->update(['type' => FieldTypeEnum::RecurringDate, 'account_id' => $user->account_id]);

        $fields = new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact, selectedPageKey: 'health')->sections()[0]['fields'];

        $knee = collect($fields)->firstWhere('label', 'Knee');
        $date = collect($fields)->firstWhere('label', 'Birthday');

        $this->assertNull($knee['reminder']);
        $this->assertFalse($date['reminder']->isOn());
        $this->assertEquals('Birthday — Michael Scott', $date['reminder']->placeholder());
        $this->assertEquals('Europe/Paris', $date['reminder']->timezone());
        $this->assertEquals('09:00', $date['reminder']->time());
        $this->assertTrue($date['reminder']->recurs());
        $this->assertEquals('yearly', $date['reminder']->cadence());
        $this->assertNull($date['reminder']->nextLine());
        $this->assertNull($date['reminder']->quietBecause());
    }

    #[Test]
    public function it_opens_a_date_with_no_rule_on_one_moment_on_the_day(): void
    {
        $user = User::factory()->create();
        $panel = $this->panelFor($user, null)['reminder'];

        $this->assertCount(1, $panel->moments());
        $this->assertEquals('on', $panel->moments()[0]['direction']);
        $this->assertEquals('on the day', $panel->moments()[0]['phrase']);
    }

    #[Test]
    public function it_reads_a_saved_rule_back_with_its_moments_in_words(): void
    {
        $user = User::factory()->create();

        $panel = $this->panelFor($user, [
            'name' => 'Ring Michael',
            'cadence' => ReminderCadenceEnum::Monthly,
            'frequency_interval' => 2,
            'time_of_day' => '18:30:00',
            'timezone' => 'Asia/Tokyo',
            'moments' => [
                ['direction' => 'before', 'amount' => 1, 'unit' => 'weeks'],
                ['direction' => 'on', 'amount' => null, 'unit' => null],
            ],
        ])['reminder'];

        $this->assertTrue($panel->isOn());
        $this->assertEquals('Ring Michael', $panel->name());
        $this->assertEquals('monthly', $panel->cadence());
        $this->assertEquals(2, $panel->interval());
        $this->assertEquals('18:30', $panel->time());
        $this->assertEquals('Asia/Tokyo', $panel->timezone());
        $this->assertEquals('1 week before', $panel->moments()[0]['phrase']);
        $this->assertEquals('on the day', $panel->moments()[1]['phrase']);
        $this->assertEquals(
            'You will be reminded 1 week before and on the day, every 2 months, at 18:30 Asia/Tokyo.',
            $panel->summary(),
        );
        $this->assertStringStartsWith('Next:', $panel->nextLine());
    }

    #[Test]
    public function it_says_why_a_rule_has_gone_quiet(): void
    {
        $user = User::factory()->create();

        $panel = $this->panelFor($user, [
            'is_active' => false,
            'inactive_reason' => ReminderInactiveReasonEnum::FieldRemoved,
        ])['reminder'];

        $this->assertStringContainsString('this field has left the structure', $panel->quietBecause());
    }

    #[Test]
    public function it_finds_a_rule_whose_date_has_been_cleared(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Birthday']]]);
        $contact = $this->contact($user, $version);
        $field = $this->fieldCalled($version, 'Birthday');
        $field->update(['type' => FieldTypeEnum::RecurringDate, 'account_id' => $user->account_id]);

        Reminder::factory()->create([
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'remindable_type' => Contact::class,
            'remindable_id' => $contact->id,
            'field_id' => $field->id,
            'field_value_id' => null,
            'is_active' => false,
            'inactive_reason' => ReminderInactiveReasonEnum::ValueCleared,
        ]);

        $panel = collect(
            new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact, selectedPageKey: 'health')->sections()[0]['fields'],
        )->firstWhere('label', 'Birthday')['reminder'];

        $this->assertTrue($panel->isOn());
        $this->assertStringContainsString('the date it read is gone', $panel->quietBecause());
        $this->assertEquals('It has nothing ahead of it any more.', $panel->nextLine());
    }

    #[Test]
    public function a_date_somebody_asked_to_be_told_about_reads_with_a_mark(): void
    {
        $user = User::factory()->create();

        $field = $this->panelFor($user, [
            'moments' => [['direction' => 'before', 'amount' => 7, 'unit' => 'days']],
        ]);

        $this->assertSame(
            'You will be reminded 7 days before, every year, at 09:00 Europe/Paris.',
            $field['reminderMark'],
        );
    }

    #[Test]
    public function a_date_nobody_asked_to_be_told_about_reads_with_no_mark(): void
    {
        $user = User::factory()->create();

        $this->assertNull($this->panelFor($user, null)['reminderMark']);
    }

    #[Test]
    public function a_rule_that_has_gone_quiet_marks_its_date_with_nothing(): void
    {
        $user = User::factory()->create();

        $field = $this->panelFor($user, [
            'is_active' => false,
            'inactive_reason' => ReminderInactiveReasonEnum::RecordArchived,
        ]);

        $this->assertNull($field['reminderMark']);
    }

    #[Test]
    public function a_watched_date_drawn_beside_the_photograph_carries_the_mark_there_too(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Birthday']]]);
        $field = $this->fieldCalled($version, 'Birthday');
        $field->update([
            'type' => FieldTypeEnum::RecurringDate,
            'account_id' => $user->account_id,
            'is_highlighted' => true,
        ]);
        $contact = $this->contact($user, $version->refresh());

        $value = FieldValue::factory()->create([
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'field_key' => $field->key,
            'value_string' => null,
            'value_date' => '1984-02-03',
        ]);

        Reminder::factory()->for($value, 'fieldValue')->create([
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'remindable_type' => Contact::class,
            'remindable_id' => $contact->id,
            'field_id' => $field->id,
            'moments' => [['direction' => 'before', 'amount' => 7, 'unit' => 'days']],
        ]);

        $facts = new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact)->headerFacts();

        $this->assertSame('Birthday', $facts[0]['label']);
        $this->assertStringContainsString('7 days before', $facts[0]['reminder']);
        $this->assertNull(end($facts)['reminder']);
    }

    /**
     * @param  array<string, mixed>|null  $rule
     * @return array<string, mixed>
     */
    private function panelFor(User $user, ?array $rule): array
    {
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Birthday']]]);
        $contact = $this->contact($user, $version, ['first_name' => 'Michael', 'last_name' => 'Scott']);
        $field = $this->fieldCalled($version, 'Birthday');
        $field->update(['type' => FieldTypeEnum::RecurringDate, 'account_id' => $user->account_id]);

        $value = FieldValue::factory()->create([
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'field_key' => $field->key,
            'value_string' => null,
            'value_date' => '1984-02-03',
        ]);

        if ($rule !== null) {
            Reminder::factory()->for($value, 'fieldValue')->create(array_merge([
                'account_id' => $contact->account_id,
                'vault_id' => $contact->vault_id,
                'remindable_type' => Contact::class,
                'remindable_id' => $contact->id,
                'field_id' => $field->id,
            ], $rule));
        }

        return collect(
            new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact, selectedPageKey: 'health')->sections()[0]['fields'],
        )->firstWhere('label', 'Birthday');
    }

    private function fieldCalled(StructureVersion $version, string $label): Field
    {
        return Field::query()->where('label', $label)->firstOrFail();
    }

    private function write(Contact $contact, Field $field, string $value): FieldValue
    {
        return FieldValue::factory()->create([
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'field_key' => $field->key,
            'value_string' => $value,
        ]);
    }

    /** @param  array<string, array<string, array<int, string>>>  $shape */
    private function version(User $user, array $shape, string $templateName = 'Basic contact'): StructureVersion
    {
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => $templateName]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        $version->pages()->delete();

        foreach ($shape as $pageLabel => $sections) {
            $page = StructureVersionPage::factory()->create([
                'structure_version_id' => $version->id,
                'account_id' => $version->account_id,
                'key' => Str::slug($pageLabel),
                'label' => $pageLabel,
            ]);

            foreach ($sections as $sectionName => $fields) {
                $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => $sectionName]);
                $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);

                foreach ($fields as $label) {
                    Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => $label]);
                }

                StructureVersionSection::factory()->create([
                    'structure_version_id' => $version->id,
                    'structure_version_page_id' => $page->id,
                    'section_version_id' => $sectionVersion->id,
                ]);
            }
        }

        $version->update(['status' => VersionStatusEnum::Published]);

        return $version->refresh();
    }

    /** @param  array<string, mixed>  $attributes */
    #[Test]
    public function it_points_the_star_at_the_route_that_changes_the_mark(): void
    {
        $user = User::factory()->create();
        $contact = $this->contact($user, $this->version($user, []));

        $viewModel = new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact);

        $this->assertEquals(
            route('contacts.favourite.update', ['vault' => $contact->vault, 'contact' => $contact]),
            $viewModel->favouriteUrl(),
        );
    }

    #[Test]
    public function it_names_the_star_after_what_pressing_it_would_do(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, []);

        $ordinary = $this->contact($user, $version);
        $favourite = $this->contact($user, $version, ['is_favourite' => true]);

        $this->assertEquals(
            'Add Michael Scott to favourites',
            new ContactViewModel(user: $user, vault: $ordinary->vault, contact: $ordinary)->favouriteLabel(),
        );

        $this->assertEquals(
            'Remove Michael Scott from favourites',
            new ContactViewModel(user: $user, vault: $favourite->vault, contact: $favourite)->favouriteLabel(),
        );
    }

    #[Test]
    public function the_relationships_tab_stands_among_the_others_and_carries_its_count(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, []);
        $contact = $this->contact($user, $version);
        $other = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
        ]);

        Relationship::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $contact->vault_id,
            'contact_a_id' => $contact->id,
            'contact_b_id' => $other->id,
        ]);

        $tabs = collect((new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact))->pages())
            ->keyBy('key');

        $this->assertArrayHasKey(ContactViewModel::PAGE_RELATIONSHIPS, $tabs);
        $this->assertSame(1, $tabs[ContactViewModel::PAGE_RELATIONSHIPS]['count']);
        $this->assertFalse($tabs[ContactViewModel::PAGE_RELATIONSHIPS]['isSelected']);
    }

    #[Test]
    public function the_relationships_tab_counts_nothing_when_nobody_is_connected(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, []);
        $contact = $this->contact($user, $version);

        $tabs = collect((new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact))->pages())
            ->keyBy('key');

        $this->assertNull($tabs[ContactViewModel::PAGE_RELATIONSHIPS]['count']);
    }

    #[Test]
    public function the_relationships_key_selects_its_own_page_and_not_the_overview(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, []);
        $contact = $this->contact($user, $version);

        $viewModel = new ContactViewModel(
            user: $user,
            vault: $contact->vault,
            contact: $contact,
            selectedPageKey: ContactViewModel::PAGE_RELATIONSHIPS,
        );

        $this->assertTrue($viewModel->onRelationships());
        $this->assertFalse($viewModel->onOverview());
        $this->assertNull($viewModel->selectedPage());
    }

    #[Test]
    public function it_hands_the_family_tree_block_the_contact_of_the_page(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, []);
        $contact = $this->contact($user, $version);

        $viewModel = new ContactViewModel(
            user: $user,
            vault: $contact->vault,
            contact: $contact,
            selectedPageKey: ContactViewModel::PAGE_RELATIONSHIPS,
        );

        $tree = $viewModel->familyTree();

        $this->assertInstanceOf(FamilyTreeBlock::class, $tree);
        $this->assertSame('Michael Scott', $tree->subject()['name']);
        $this->assertSame($tree, $viewModel->familyTree());
    }

    private function contact(User $user, StructureVersion $version, array $attributes = []): Contact
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $user->account_id,
            'structure_version_id' => $version->id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            ...$attributes,
        ]);
    }
}
