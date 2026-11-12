<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\FieldTypeEnum;
use App\Enums\VersionStatusEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\File;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Pennant\Feature;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactSectionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_opens_one_block_for_editing(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $version = $this->versionWithSections($user, 'Health', ['Ongoing' => 'Knee']);
        $michael->update(['structure_version_id' => $version->id]);
        $knee = Field::query()->where('label', 'Knee')->firstOrFail();

        $this->actingAs($user)
            ->get(route('contacts.sections.edit', [
                'vault' => $vault,
                'contact' => $michael,
                'structureVersionSection' => $this->sectionNamed($version, 'Ongoing'),
            ]))
            ->assertStatus(200)
            ->assertSee('name="fields['.$knee->id.']"', false);
    }

    #[Test]
    public function saving_a_block_reaches_the_facts_beside_the_photograph_too(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $version = $this->versionWithSections($user, 'Health', ['Ongoing' => 'Knee']);
        $michael->update(['structure_version_id' => $version->id]);
        $row = $this->sectionNamed($version, 'Ongoing');

        $this->actingAs($user)
            ->get(route('contacts.sections.edit', ['vault' => $vault, 'contact' => $michael, 'structureVersionSection' => $row]))
            ->assertStatus(200)
            ->assertSee('x-target="section-'.$row->id.' contact-facts"', false)
            ->assertSee('id="contact-facts"', false);
    }

    #[Test]
    public function it_saves_one_block(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $version = $this->versionWithSections($user, 'Health', ['Ongoing' => 'Knee']);
        $michael->update(['structure_version_id' => $version->id]);
        $knee = Field::query()->where('label', 'Knee')->firstOrFail();

        $this->actingAs($user)
            ->put(
                route('contacts.sections.update', [
                    'vault' => $vault,
                    'contact' => $michael,
                    'structureVersionSection' => $this->sectionNamed($version, 'Ongoing'),
                ]),
                ['fields' => [$knee->id => 'Twisted it skiing']],
            )
            ->assertRedirect(route('contacts.show', ['vault' => $vault, 'contact' => $michael, 'page' => 'health']))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('field_values', [
            'valuable_id' => $michael->id,
            'field_id' => $knee->id,
            'value_string' => 'Twisted it skiing',
        ]);
    }

    #[Test]
    public function it_keeps_every_note_when_a_section_holding_more_than_a_page_is_saved_unchanged(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');
        [$notes, $note] = $this->notesOn($user, $michael);

        $rows = [];

        foreach (range(1, 12) as $number) {
            $rows[] = $this->writeNote($michael, $notes, $note, "Note {$number}");
        }

        $section = StructureVersionSection::query()->where('section_version_id', $notes->section_version_id)->sole();

        $this->actingAs($user)
            ->get(route('contacts.sections.edit', ['vault' => $vault, 'contact' => $michael, 'structureVersionSection' => $section]))
            ->assertSee('Note 1')
            ->assertSee('Note 12');

        $this->actingAs($user)
            ->put(
                route('contacts.sections.update', ['vault' => $vault, 'contact' => $michael, 'structureVersionSection' => $section]),
                ['fields' => [$notes->id => array_map(fn (FieldValueRow $row): array => [
                    'id' => $row->id,
                    'values' => [$note->id => $row->values()->sole()->value_text],
                ], $rows)]],
            )
            ->assertSessionHasNoErrors();

        $this->assertSame(12, FieldValueRow::query()->where('field_id', $notes->id)->count());
    }

    #[Test]
    public function it_writes_nothing_in_a_block_it_was_not_asked_about(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $version = $this->versionWithSections($user, 'Health', ['Ongoing' => 'Knee', 'Diet' => 'Allergies']);
        $michael->update(['structure_version_id' => $version->id]);
        $knee = Field::query()->where('label', 'Knee')->firstOrFail();
        $allergies = Field::query()->where('label', 'Allergies')->firstOrFail();

        $this->actingAs($user)->put(
            route('contacts.sections.update', [
                'vault' => $vault,
                'contact' => $michael,
                'structureVersionSection' => $this->sectionNamed($version, 'Ongoing'),
            ]),
            ['fields' => [$knee->id => 'Twisted it skiing', $allergies->id => 'Peanuts']],
        );

        $this->assertDatabaseHas('field_values', ['field_id' => $knee->id, 'value_string' => 'Twisted it skiing']);
        $this->assertDatabaseMissing('field_values', ['field_id' => $allergies->id]);
    }

    #[Test]
    public function it_sends_a_block_back_open_when_a_required_field_is_left_empty(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $version = $this->versionWithSections($user, 'Health', ['Ongoing' => 'Knee']);
        $michael->update(['structure_version_id' => $version->id]);
        $knee = Field::query()->where('label', 'Knee')->firstOrFail();
        $knee->sectionVersion->update(['status' => VersionStatusEnum::Draft]);
        $knee->update(['is_required' => true]);

        $section = $this->sectionNamed($version, 'Ongoing');

        $this->actingAs($user)
            ->put(
                route('contacts.sections.update', ['vault' => $vault, 'contact' => $michael, 'structureVersionSection' => $section]),
                ['fields' => [$knee->id => '']],
            )
            ->assertRedirect(route('contacts.sections.edit', ['vault' => $vault, 'contact' => $michael, 'structureVersionSection' => $section]))
            ->assertSessionHasErrors('fields.'.$knee->id);

        $this->assertSame(0, FieldValue::query()->count());
    }

    #[Test]
    public function it_refuses_a_new_choice_longer_than_a_label(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');
        [$section, $category] = $this->sectionWithAMultipleChoice($user, $michael);

        $this->actingAs($user)
            ->put(
                route('contacts.sections.update', ['vault' => $vault, 'contact' => $michael, 'structureVersionSection' => $section]),
                ['new_choices' => [$category->id => [str_repeat('a', 256)]]],
            )
            ->assertSessionHasErrors('new_choices.'.$category->id.'.0');

        $this->assertSame(0, FieldValue::query()->count());
    }

    #[Test]
    public function it_sends_the_block_back_open_when_a_new_choice_names_one_no_longer_offered(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');
        [$section, $category] = $this->sectionWithAMultipleChoice($user, $michael);
        ValueListItem::factory()->disabled()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $category->value_list_id,
            'label' => 'Party Planning Committee',
        ]);

        $this->actingAs($user)
            ->put(
                route('contacts.sections.update', ['vault' => $vault, 'contact' => $michael, 'structureVersionSection' => $section]),
                ['new_choices' => [$category->id => ['party planning committee']]],
            )
            ->assertRedirect(route('contacts.sections.edit', ['vault' => $vault, 'contact' => $michael, 'structureVersionSection' => $section]))
            ->assertSessionHasErrors(['fields' => '“Party Planning Committee” is no longer offered. Offer it again from the value lists in your settings.']);

        $this->assertSame(0, FieldValue::query()->count());
    }

    #[Test]
    public function it_does_not_validate_against_a_section_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $theirs = $this->versionWithSections(User::factory()->create(), 'Health', ['Ongoing' => 'Confidential diagnosis']);
        Field::query()->where('label', 'Confidential diagnosis')->firstOrFail()->update(['is_required' => true]);
        $section = $this->sectionNamed($theirs, 'Ongoing');

        $this->actingAs($user)
            ->put(
                route('contacts.sections.update', ['vault' => $vault, 'contact' => $michael, 'structureVersionSection' => $section->id]),
                [],
            )
            ->assertNotFound()
            ->assertSessionDoesntHaveErrors();
    }

    #[Test]
    public function it_never_puts_the_placeholder_year_on_the_screen(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $version = $this->versionWithSections($user, 'Health', ['Dates' => 'Birthday']);
        $michael->update(['structure_version_id' => $version->id]);
        $birthday = Field::query()->where('label', 'Birthday')->firstOrFail();
        $birthday->sectionVersion->update(['status' => VersionStatusEnum::Draft]);
        $birthday->update(['type' => FieldTypeEnum::RecurringDate]);

        $section = $this->sectionNamed($version, 'Dates');

        $this->actingAs($user)->put(
            route('contacts.sections.update', ['vault' => $vault, 'contact' => $michael, 'structureVersionSection' => $section]),
            [
                'fields' => [$birthday->id => '1964-03-15'],
                'year_is_unknown' => [$birthday->id => '1'],
            ],
        );

        $stored = FieldValue::query()->where('field_id', $birthday->id)->firstOrFail();

        $this->assertTrue($stored->year_is_unknown);
        $this->assertSame(FieldValue::PLACEHOLDER_YEAR, (int) $stored->value_date->format('Y'));

        $reading = (string) $this->actingAs($user)
            ->get(route('contacts.show', ['vault' => $vault, 'contact' => $michael, 'page' => 'health']))
            ->getContent();

        $editing = (string) $this->actingAs($user)
            ->get(route('contacts.sections.edit', ['vault' => $vault, 'contact' => $michael, 'structureVersionSection' => $section]))
            ->getContent();

        $this->assertStringNotContainsString((string) FieldValue::PLACEHOLDER_YEAR, $reading);
        $this->assertStringNotContainsString((string) FieldValue::PLACEHOLDER_YEAR, $editing);
    }

    #[Test]
    public function it_does_not_find_a_block_the_structure_does_not_carry(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $version = $this->versionWithSections($user, 'Health', ['Ongoing' => 'Knee']);
        $michael->update(['structure_version_id' => $version->id]);

        $elsewhere = $this->versionWithSections($user, 'Work', ['Desk' => 'Floor']);
        $theirs = $this->sectionNamed($elsewhere, 'Desk');

        $this->actingAs($user)
            ->get(route('contacts.sections.edit', ['vault' => $vault, 'contact' => $michael, 'structureVersionSection' => $theirs]))
            ->assertNotFound();

        $this->actingAs($user)
            ->put(
                route('contacts.sections.update', ['vault' => $vault, 'contact' => $michael, 'structureVersionSection' => $theirs]),
                ['fields' => []],
            )
            ->assertNotFound();
    }

    #[Test]
    public function it_refuses_a_file_of_a_kind_the_field_does_not_take(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        [$documents, $file] = $this->documentsOn($user, $michael);
        $section = StructureVersionSection::query()->where('section_version_id', $documents->section_version_id)->sole();

        $this->actingAs($user)
            ->put(
                route('contacts.sections.update', ['vault' => $vault, 'contact' => $michael, 'structureVersionSection' => $section]),
                ['fields' => [$documents->id => [
                    ['values' => [$file->id => UploadedFile::fake()->create('holiday.gif', 4, 'image/gif')]],
                ]]],
            )
            ->assertRedirect(route('contacts.sections.edit', ['vault' => $vault, 'contact' => $michael, 'structureVersionSection' => $section]))
            ->assertSessionHasErrors('fields.'.$documents->id.'.0.values.'.$file->id);

        $this->assertSame(0, File::query()->count());
    }

    #[Test]
    public function it_refuses_a_file_and_saves_nothing_while_file_upload_is_off_for_the_account(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        [$documents, $file] = $this->documentsOn($user, $michael);
        $section = StructureVersionSection::query()->where('section_version_id', $documents->section_version_id)->sole();
        $user->account->features()->deactivate('file-upload');
        Feature::flushCache();

        $this->actingAs($user)
            ->put(
                route('contacts.sections.update', ['vault' => $vault, 'contact' => $michael, 'structureVersionSection' => $section]),
                ['fields' => [$documents->id => [
                    ['values' => [$file->id => UploadedFile::fake()->create('lease.pdf', 8, 'application/pdf')]],
                ]]],
            )
            ->assertRedirect(route('contacts.sections.edit', ['vault' => $vault, 'contact' => $michael, 'structureVersionSection' => $section]))
            ->assertSessionHasErrors(['fields.'.$documents->id.'.0.values.'.$file->id]);

        $this->assertSame(0, File::query()->count());
    }

    #[Test]
    public function it_refuses_a_cadence_it_does_not_know(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        [$version, $birthday] = $this->versionWithADate($user, FieldTypeEnum::RecurringDate);
        $michael->update(['structure_version_id' => $version->id]);

        $this->actingAs($user)
            ->put(route('contacts.sections.update', [
                'vault' => $vault,
                'contact' => $michael,
                'structureVersionSection' => $this->sectionNamed($version, 'Dates'),
            ]), [
                'fields' => [$birthday->id => '1984-02-03'],
                'reminders' => [$birthday->id => $this->askedFor(['repeat' => 'fortnightly:1'])],
            ])
            ->assertSessionHasErrors('reminders.'.$birthday->id.'.cadence');

        $this->assertEquals(0, Reminder::query()->count());
    }

    #[Test]
    public function it_writes_the_deadlines_of_the_reminder_it_just_saved(): void
    {
        Queue::fake();
        CarbonImmutable::setTestNow('2027-01-01 06:00:00');

        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        [$version, $birthday] = $this->versionWithADate($user, FieldTypeEnum::RecurringDate);
        $michael->update(['structure_version_id' => $version->id]);
        $section = $this->sectionNamed($version, 'Dates');

        $this->actingAs($user)
            ->put(route('contacts.sections.update', [
                'vault' => $vault,
                'contact' => $michael,
                'structureVersionSection' => $section,
            ]), [
                'fields' => [$birthday->id => '1984-02-03'],
                'reminders' => [$birthday->id => $this->askedFor()],
            ])
            ->assertRedirect();

        $this->assertSame(
            ['2027-02-03'],
            ReminderOccurrence::query()->get()->map(fn (ReminderOccurrence $occurrence): string => $occurrence->local_date->toDateString())->all(),
        );
        $this->assertDatabaseHas('reminder_occurrences', ['answer' => null, 'cancelled_at' => null]);
    }

    /** @return array{0: StructureVersion, 1: Field} */
    private function versionWithADate(User $user, FieldTypeEnum $type): array
    {
        $version = $this->versionWithSections($user, 'Important', ['Dates' => 'Birthday']);
        $field = Field::query()->where('label', 'Birthday')->firstOrFail();
        $field->update(['type' => $type, 'account_id' => $user->account_id]);

        return [$version, $field->refresh()];
    }

    /**
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    private function askedFor(array $changes = []): array
    {
        return array_merge([
            'on' => '1',
            'name' => 'Ring Michael',
            'repeat' => 'yearly:1',
            'time' => '09:00',
            'timezone' => 'Europe/Paris',
        ], $changes);
    }

    /** @return array{0: Field, 1: Field} */
    private function notesOn(User $user, Contact $contact): array
    {
        $version = $this->versionWithSections($user, 'Notebook', ['Notes' => 'Notes']);
        $contact->update(['structure_version_id' => $version->id]);

        $notes = Field::query()->where('label', 'Notes')->firstOrFail();
        $notes->sectionVersion->update(['status' => VersionStatusEnum::Draft]);
        $notes->update(['type' => FieldTypeEnum::Repeater, 'is_repeatable' => true]);

        return [$notes, $this->childOf($notes, 'Note', ['type' => FieldTypeEnum::Textarea])];
    }

    private function writeNote(Contact $contact, Field $notes, Field $note, string $text): FieldValueRow
    {
        $row = FieldValueRow::factory()->create([
            'valuable_id' => $contact->id,
            'field_id' => $notes->id,
        ]);

        FieldValue::factory()->create([
            'valuable_id' => $contact->id,
            'field_id' => $note->id,
            'field_value_row_id' => $row->id,
            'value_string' => null,
            'value_text' => $text,
        ]);

        return $row;
    }

    /** @return array{0: Field, 1: Field, 2: Field} */
    private function documentsOn(User $user, Contact $contact): array
    {
        $version = $this->versionWithSections($user, 'Information', ['Documents' => 'Document']);
        $contact->update(['structure_version_id' => $version->id]);

        $documents = Field::query()->where('label', 'Document')->firstOrFail();
        $documents->sectionVersion->update(['status' => VersionStatusEnum::Draft]);
        $documents->update(['type' => FieldTypeEnum::Repeater, 'is_repeatable' => true]);

        $file = $this->childOf($documents, 'File', [
            'type' => FieldTypeEnum::File,
            'options' => ['accepted_mime_types' => ['application/pdf']],
        ]);

        return [$documents, $file, $this->childOf($documents, 'Description')];
    }

    /** @param  array<string, mixed>  $attributes */
    private function childOf(Field $repeater, string $label, array $attributes = []): Field
    {
        $sectionVersion = $repeater->sectionVersion()->first();
        $sectionVersion->update(['status' => VersionStatusEnum::Draft]);

        $child = Field::factory()->create([
            'account_id' => $repeater->account_id,
            'section_version_id' => $repeater->section_version_id,
            'parent_field_id' => $repeater->id,
            'label' => $label,
            ...$attributes,
        ]);

        $sectionVersion->update(['status' => VersionStatusEnum::Published]);

        return $child;
    }

    /** @param  array<string, string>  $sections  the field label, keyed by the section name */
    private function versionWithSections(User $user, string $pageLabel, array $sections): StructureVersion
    {
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        $page = StructureVersionPage::factory()->create([
            'structure_version_id' => $version->id,
            'account_id' => $version->account_id,
            'key' => Str::slug($pageLabel),
            'label' => $pageLabel,
        ]);

        $position = 1;

        foreach ($sections as $sectionName => $fieldLabel) {
            $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => $sectionName]);
            $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);

            Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => $fieldLabel]);

            StructureVersionSection::factory()->create([
                'structure_version_id' => $version->id,
                'structure_version_page_id' => $page->id,
                'section_version_id' => $sectionVersion->id,
                'position' => $position++,
            ]);
        }

        $version->update(['status' => VersionStatusEnum::Published]);

        return $version->refresh();
    }

    /** @return array{0: StructureVersionSection, 1: Field} */
    private function sectionWithAMultipleChoice(User $user, Contact $contact): array
    {
        $version = $this->versionWithSections($user, 'About', ['Belongs to' => 'Category']);
        $contact->update(['structure_version_id' => $version->id]);
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $category = Field::query()->where('label', 'Category')->firstOrFail();
        $category->sectionVersion->update(['status' => VersionStatusEnum::Draft]);
        $category->update(['type' => FieldTypeEnum::Multiselect, 'value_list_id' => $list->id]);

        return [$this->sectionNamed($version, 'Belongs to'), $category->refresh()];
    }

    private function sectionNamed(StructureVersion $version, string $name): StructureVersionSection
    {
        return $version->assembly
            ->first(fn (StructureVersionSection $row): bool => $row->sectionVersion->section->name === $name);
    }

    /** @param  array<string, mixed>  $attributes */
    private function contact(Vault $vault, string $firstName, string $lastName, array $attributes = []): Contact
    {
        return Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'structure_version_id' => $vault->defaultTemplates()->sole()->structure_version_id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            ...$attributes,
        ]);
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
}
