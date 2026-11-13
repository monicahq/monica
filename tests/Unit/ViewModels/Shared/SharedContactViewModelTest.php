<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Shared;

use App\Enums\FieldTypeEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\ShareLink;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Shared\SharedContactViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SharedContactViewModelTest extends TestCase
{
    use RefreshDatabase;

    private const string TOKEN = 'ScottsTotsScottsTotsScottsTotsScottsTotsScottsTotsScottsTots1234';

    #[Test]
    public function it_draws_the_sections_the_link_lets_through(): void
    {
        $contact = $this->sharedContact();
        $link = $this->linkOnto($contact);

        $sections = $this->viewModel($link, $contact)->sections();

        $this->assertSame(['Facts', 'Doctors'], array_column($sections, 'name'));
        $this->assertSame([['label' => 'Lives', 'value' => 'Scranton']], $sections[0]['fields']);
    }

    #[Test]
    public function it_never_names_the_people_a_relation_points_at(): void
    {
        $contact = $this->sharedContact();
        $lives = Field::query()->where('label', 'Lives')->firstOrFail();
        $boss = Field::factory()->relation()->create(['section_version_id' => $lives->section_version_id, 'label' => 'Boss']);
        $david = Contact::factory()->create(['vault_id' => $contact->vault_id, 'first_name' => 'David', 'last_name' => 'Wallace']);
        FieldValue::factory()->create([
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'field_id' => $boss->id,
            'field_key' => $boss->key,
            'value_string' => null,
            'related_contact_id' => $david->id,
        ]);

        $sections = $this->viewModel($this->linkOnto($contact), $contact)->sections();

        $this->assertSame([['label' => 'Lives', 'value' => 'Scranton']], $sections[0]['fields']);
    }

    #[Test]
    public function it_never_reads_a_section_the_link_holds_back(): void
    {
        $contact = $this->sharedContact();
        $link = $this->linkOnto($contact, $this->holdingBack($contact, ['Doctors']));

        $this->assertSame(['Facts'], array_column($this->viewModel($link, $contact)->sections(), 'name'));
    }

    #[Test]
    public function a_section_held_back_stays_held_back_once_the_contact_moves_to_another_version(): void
    {
        $contact = $this->sharedContact();
        $link = $this->linkOnto($contact, $this->holdingBack($contact, ['Doctors']));

        $newVersion = StructureVersion::factory()->create(['structure_id' => $contact->structureVersion->structure_id, 'version_number' => $contact->structureVersion->version_number + 1]);
        $newVersion->pages()->delete();

        foreach ($contact->structureVersion->pages as $page) {
            $copy = StructureVersionPage::factory()->create([
                'structure_version_id' => $newVersion->id,
                'account_id' => $newVersion->account_id,
                'key' => $page->key,
                'label' => $page->label,
            ]);

            foreach ($contact->structureVersion->assembly->where('structure_version_page_id', $page->id) as $row) {
                StructureVersionSection::factory()->create([
                    'structure_version_id' => $newVersion->id,
                    'structure_version_page_id' => $copy->id,
                    'section_version_id' => $row->section_version_id,
                    'position' => $row->position,
                ]);
            }
        }

        $contact->update(['structure_version_id' => $newVersion->id]);

        $this->assertSame(['Facts'], array_column($this->viewModel($link->refresh(), $contact->refresh())->sections(), 'name'));
    }

    #[Test]
    public function a_link_moved_onto_a_contact_of_another_template_shows_none_of_its_sections(): void
    {
        $michael = $this->sharedContact();
        $link = $this->linkOnto($michael);
        $jim = $this->sharedContact();

        $link->update(['shareable_id' => $jim->id]);

        $this->assertSame([], $this->viewModel($link->refresh(), $jim)->sections());
    }

    #[Test]
    public function a_field_marked_sensitive_stays_hidden_inside_a_shared_section(): void
    {
        $contact = $this->sharedContact();
        $link = $this->linkOnto($contact);

        $sections = $this->viewModel($link, $contact, 'work')->sections();

        $this->assertSame([['label' => 'Employer', 'value' => 'Dunder Mifflin']], $sections[0]['fields']);
    }

    #[Test]
    public function a_field_marked_sensitive_appears_when_the_link_says_so(): void
    {
        $contact = $this->sharedContact();
        $link = $this->linkOnto($contact, ['includes_sensitive_fields' => true]);

        $labels = array_column($this->viewModel($link, $contact, 'work')->sections()[0]['fields'], 'label');

        $this->assertContains('Social security number', $labels);
    }

    #[Test]
    public function it_offers_a_tab_for_each_page_that_has_something_on_it(): void
    {
        $contact = $this->sharedContact();
        $link = $this->linkOnto($contact);

        $this->assertSame(['overview', 'work'], array_column($this->viewModel($link, $contact)->tabs(), 'key'));
    }

    #[Test]
    public function a_page_whose_every_section_is_held_back_is_not_a_tab(): void
    {
        $contact = $this->sharedContact();
        $link = $this->linkOnto($contact, $this->holdingBack($contact, ['Where they worked']));

        $this->assertSame([], $this->viewModel($link, $contact)->tabs());
    }

    #[Test]
    public function one_page_left_means_no_tab_list(): void
    {
        $contact = $this->sharedContact();
        $link = $this->linkOnto($contact, $this->holdingBack($contact, ['Facts', 'Doctors']));

        $viewModel = $this->viewModel($link, $contact);

        $this->assertSame([], $viewModel->tabs());
        $this->assertSame(['Where they worked'], array_column($viewModel->sections(), 'name'));
    }

    #[Test]
    public function an_address_naming_a_page_that_is_held_back_falls_back_to_one_that_is_not(): void
    {
        $contact = $this->sharedContact();
        $link = $this->linkOnto($contact, $this->holdingBack($contact, ['Where they worked']));

        $this->assertSame(['Facts', 'Doctors'], array_column($this->viewModel($link, $contact, 'work')->sections(), 'name'));
    }

    #[Test]
    public function a_section_whose_every_field_was_dropped_is_not_drawn(): void
    {
        $contact = $this->sharedContact();
        FieldValue::query()->delete();
        $link = $this->linkOnto($contact);

        $this->assertSame([], $this->viewModel($link, $contact)->sections());
    }

    #[Test]
    public function it_says_who_shared_it_and_on_what_terms(): void
    {
        $contact = $this->sharedContact();
        $user = $contact->account->user;
        $link = $this->linkOnto($contact, [
            'created_by_user_id' => $user->id,
            'expires_at' => now()->addDays(30),
        ]);

        $viewModel = $this->viewModel($link, $contact);

        $this->assertStringContainsString(now()->addDays(30)->isoFormat('LL'), $viewModel->terms());
    }

    #[Test]
    public function it_puts_the_highlighted_answers_under_the_name(): void
    {
        $contact = $this->sharedContact();
        Field::query()->where('label', 'Lives')->update(['is_highlighted' => true]);
        $link = $this->linkOnto($contact);

        $this->assertSame(
            [['label' => 'Lives', 'value' => 'Scranton']],
            $this->viewModel($link->refresh(), $contact->refresh())->headerFacts(),
        );
    }

    #[Test]
    public function it_leaves_a_highlighted_answer_out_of_the_header_when_its_section_is_held_back(): void
    {
        $contact = $this->sharedContact();
        Field::query()->where('label', 'Lives')->update(['is_highlighted' => true]);
        $link = $this->linkOnto($contact, $this->holdingBack($contact, ['Facts']));

        $this->assertSame([], $this->viewModel($link->refresh(), $contact->refresh())->headerFacts());
    }

    #[Test]
    public function it_names_the_groups_of_the_contact_only_when_the_link_shows_them(): void
    {
        $contact = $this->sharedContact();
        GroupMember::factory()->create(['contact_id' => $contact->id, 'group_id' => Group::factory()->create(['vault_id' => $contact->vault_id, 'name' => 'Scranton branch'])->id]);
        GroupMember::factory()->former()->create(['contact_id' => $contact->id, 'group_id' => Group::factory()->create(['vault_id' => $contact->vault_id, 'name' => 'Utica branch'])->id]);

        $link = $this->linkOnto($contact);

        $this->assertSame([], $this->viewModel($link, $contact)->groups());

        $link->update(['includes_groups' => true]);

        $this->assertSame(['Scranton branch'], $this->viewModel($link, $contact)->groups());
    }

    private function viewModel(ShareLink $link, Contact $contact, ?string $page = null): SharedContactViewModel
    {
        return new SharedContactViewModel(
            shareLink: $link,
            contact: $contact,
            token: self::TOKEN,
            selectedPageKey: $page,
        );
    }

    /** @param  array<string, mixed>  $attributes */
    private function linkOnto(Contact $contact, array $attributes = []): ShareLink
    {
        return ShareLink::factory()->for($contact, 'shareable')->create([
            'token' => hash('sha256', self::TOKEN),
            ...$attributes,
        ]);
    }

    private function sharedContact(): Contact
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        $this->page($version, 'Overview', [
            'Facts' => [['Lives', false]],
            'Doctors' => [['Doctor', false]],
        ]);
        $this->page($version, 'Work', [
            'Where they worked' => [['Employer', false], ['Social security number', true]],
        ]);

        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'structure_version_id' => $version->refresh()->id,
            'first_name' => 'Michael',
            'last_name' => 'Scott',
        ]);

        $this->answer($contact, 'Lives', 'Scranton');
        $this->answer($contact, 'Doctor', 'Dr Ferrand');
        $this->answer($contact, 'Employer', 'Dunder Mifflin');
        $this->answer($contact, 'Social security number', '123-45-6789');

        return $contact;
    }

    /** @param  array<string, array<int, array{0: string, 1: bool}>>  $sections */
    private function page(StructureVersion $version, string $label, array $sections): void
    {
        $page = StructureVersionPage::factory()->create([
            'structure_version_id' => $version->id,
            'account_id' => $version->account_id,
            'key' => Str::slug($label),
            'label' => $label,
        ]);

        $position = 1;

        foreach ($sections as $sectionName => $fields) {
            $section = Section::factory()->create(['account_id' => $version->account_id, 'name' => $sectionName]);
            $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);

            foreach ($fields as [$fieldLabel, $isSensitive]) {
                Field::factory()->create([
                    'section_version_id' => $sectionVersion->id,
                    'label' => $fieldLabel,
                    'type' => FieldTypeEnum::Text,
                    'is_sensitive' => $isSensitive,
                ]);
            }

            StructureVersionSection::factory()->create([
                'structure_version_id' => $version->id,
                'structure_version_page_id' => $page->id,
                'section_version_id' => $sectionVersion->id,
                'position' => $position++,
            ]);
        }
    }

    private function answer(Contact $contact, string $label, string $value): void
    {
        $field = Field::query()->where('label', $label)->firstOrFail();

        FieldValue::factory()->create([
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
     * @param  list<string>  $names
     * @return array{included_section_ids: list<int>}
     */
    private function holdingBack(Contact $contact, array $names): array
    {
        return [
            'included_section_ids' => $contact->structureVersion->assembly
                ->reject(fn (StructureVersionSection $row): bool => in_array($row->sectionVersion->section->name, $names, true))
                ->map(fn (StructureVersionSection $row): int => $row->sectionVersion->section_id)
                ->values()
                ->all(),
        ];
    }
}
