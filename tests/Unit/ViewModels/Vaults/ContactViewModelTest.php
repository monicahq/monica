<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\FieldTypeEnum;
use App\Enums\VersionStatusEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use App\Models\TemplateVersionSection;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactViewModel;
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
    public function a_section_marks_itself_in_a_hue_of_its_own(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee'], 'Practitioners' => ['Doctor']]]);
        $contact = $this->contact($user, $version);

        $sections = (new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact, selectedPageKey: 'health'))->sections();

        $this->assertNotSame($sections[0]['iconClass'], $sections[1]['iconClass']);
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
            route('contacts.sections.edit', ['vault' => $contact->vault, 'contact' => $contact, 'templateVersionSection' => $row]),
            $sections[0]['editUrl'],
        );
        $this->assertSame(
            route('contacts.sections.update', ['vault' => $contact->vault, 'contact' => $contact, 'templateVersionSection' => $row]),
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

    /**
     * A block made entirely of types nothing can fill in yet has nothing to
     * open, and a button leading to a form of read-only sentences is worse than
     * no button.
     */
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
    public function it_says_where_the_pages_of_the_record_are_decided(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee']]], 'Someone in the family');
        $contact = $this->contact($user, $version);

        $viewModel = new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact);

        $this->assertSame(
            route('settings.templateBuilder.index', $version->template),
            $viewModel->templateUrl(),
        );
    }

    #[Test]
    public function a_version_with_no_page_still_draws_the_overview(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);
        $version->pages()->delete();
        $contact = $this->contact($user, $version->refresh());

        $viewModel = new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact);

        $this->assertCount(1, $viewModel->pages());
        $this->assertTrue($viewModel->onOverview());
    }

    #[Test]
    public function the_last_thing_beside_the_photograph_is_how_long_it_has_been(): void
    {
        $user = User::factory()->create();
        $version = $this->version($user, ['Health' => ['Ongoing' => ['Knee']]]);
        $contact = $this->contact($user, $version, ['last_activity_at' => now()->subDays(4)]);

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
        $version->template->update(['published_version_id' => $version->id]);

        $this->assertNull(new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact)->templateUpdate());
    }

    /**
     * A record deliberately put on an earlier version is not asked about it
     * every time somebody opens it, and neither is one on a template nobody has
     * published anything for.
     */
    #[Test]
    public function nothing_is_offered_when_the_published_version_is_not_newer(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $current = TemplateVersion::factory()->published()->create([
            'template_id' => $template->id,
            'version_number' => 2,
        ]);
        $contact = $this->contact($user, $current);

        $this->assertNull(new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact)->templateUpdate());

        $older = TemplateVersion::factory()->published()->create([
            'template_id' => $template->id,
            'version_number' => 1,
        ]);
        $template->update(['published_version_id' => $older->id]);

        $this->assertNull(new ContactViewModel(user: $user, vault: $contact->vault, contact: $contact->refresh())->templateUpdate());
    }

    private function publish(TemplateVersion $version, int $number, ?string $note = null): TemplateVersion
    {
        $published = TemplateVersion::factory()->published()->create([
            'template_id' => $version->template_id,
            'version_number' => $number,
            'change_note' => $note,
            'previous_version_id' => $version->id,
        ]);

        $version->template->update(['published_version_id' => $published->id]);

        return $published;
    }

    private function fieldCalled(TemplateVersion $version, string $label): Field
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

    /**
     * A published version carrying the pages, sections and fields described.
     * It is assembled while the version is still a draft, because a published
     * one is frozen and refuses every one of these rows.
     *
     * @param  array<string, array<string, array<int, string>>>  $shape
     */
    private function version(User $user, array $shape, string $templateName = 'Basic contact'): TemplateVersion
    {
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => $templateName]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);

        /* The factory hands over the page the product supplies; the shape asked
           for here is the whole version rather than an addition to it. */
        $version->pages()->delete();

        foreach ($shape as $pageLabel => $sections) {
            $page = TemplateVersionPage::factory()->create([
                'template_version_id' => $version->id,
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

                TemplateVersionSection::factory()->create([
                    'template_version_id' => $version->id,
                    'template_version_page_id' => $page->id,
                    'section_version_id' => $sectionVersion->id,
                ]);
            }
        }

        $version->update(['status' => VersionStatusEnum::Published]);

        return $version->refresh();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function contact(User $user, TemplateVersion $version, array $attributes = []): Contact
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $user->account_id,
            'template_version_id' => $version->id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
            ...$attributes,
        ]);
    }
}
