<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\FieldTypeEnum;
use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\TemplateAppliesToEnum;
use App\Enums\VersionStatusEnum;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\File;
use App\Models\Reminder;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use App\Models\TemplateVersionSection;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
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
        $michael->update(['template_version_id' => $version->id]);
        $knee = Field::query()->where('label', 'Knee')->firstOrFail();

        $this->actingAs($user)
            ->get(route('contacts.sections.edit', [
                'vault' => $vault,
                'contact' => $michael,
                'templateVersionSection' => $this->sectionNamed($version, 'Ongoing'),
            ]))
            ->assertStatus(200)
            ->assertSee('name="fields['.$knee->id.']"', false)
            ->assertSee('Save this section');
    }

    #[Test]
    public function it_leaves_every_other_block_reading(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $version = $this->versionWithSections($user, 'Health', ['Ongoing' => 'Knee', 'Diet' => 'Allergies']);
        $michael->update(['template_version_id' => $version->id]);
        $knee = Field::query()->where('label', 'Knee')->firstOrFail();
        $allergies = Field::query()->where('label', 'Allergies')->firstOrFail();

        $this->actingAs($user)
            ->get(route('contacts.sections.edit', [
                'vault' => $vault,
                'contact' => $michael,
                'templateVersionSection' => $this->sectionNamed($version, 'Ongoing'),
            ]))
            ->assertStatus(200)
            ->assertSee('name="fields['.$knee->id.']"', false)
            ->assertDontSee('name="fields['.$allergies->id.']"', false)
            ->assertSee('Allergies');
    }

    #[Test]
    public function saving_a_block_reaches_the_facts_beside_the_photograph_too(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $version = $this->versionWithSections($user, 'Health', ['Ongoing' => 'Knee']);
        $michael->update(['template_version_id' => $version->id]);
        $row = $this->sectionNamed($version, 'Ongoing');

        $this->actingAs($user)
            ->get(route('contacts.sections.edit', ['vault' => $vault, 'contact' => $michael, 'templateVersionSection' => $row]))
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
        $michael->update(['template_version_id' => $version->id]);
        $knee = Field::query()->where('label', 'Knee')->firstOrFail();

        $this->actingAs($user)
            ->put(
                route('contacts.sections.update', [
                    'vault' => $vault,
                    'contact' => $michael,
                    'templateVersionSection' => $this->sectionNamed($version, 'Ongoing'),
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
    public function it_writes_nothing_in_a_block_it_was_not_asked_about(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $version = $this->versionWithSections($user, 'Health', ['Ongoing' => 'Knee', 'Diet' => 'Allergies']);
        $michael->update(['template_version_id' => $version->id]);
        $knee = Field::query()->where('label', 'Knee')->firstOrFail();
        $allergies = Field::query()->where('label', 'Allergies')->firstOrFail();

        $this->actingAs($user)->put(
            route('contacts.sections.update', [
                'vault' => $vault,
                'contact' => $michael,
                'templateVersionSection' => $this->sectionNamed($version, 'Ongoing'),
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
        $michael->update(['template_version_id' => $version->id]);
        $knee = Field::query()->where('label', 'Knee')->firstOrFail();
        $knee->sectionVersion->update(['status' => VersionStatusEnum::Draft]);
        $knee->update(['is_required' => true]);

        $section = $this->sectionNamed($version, 'Ongoing');

        $this->actingAs($user)
            ->put(
                route('contacts.sections.update', ['vault' => $vault, 'contact' => $michael, 'templateVersionSection' => $section]),
                ['fields' => [$knee->id => '']],
            )
            ->assertRedirect(route('contacts.sections.edit', ['vault' => $vault, 'contact' => $michael, 'templateVersionSection' => $section]))
            ->assertSessionHasErrors('fields.'.$knee->id);

        $this->assertSame(0, FieldValue::query()->count());
    }

    #[Test]
    public function it_never_puts_the_placeholder_year_on_the_screen(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $version = $this->versionWithSections($user, 'Health', ['Dates' => 'Birthday']);
        $michael->update(['template_version_id' => $version->id]);
        $birthday = Field::query()->where('label', 'Birthday')->firstOrFail();
        $birthday->sectionVersion->update(['status' => VersionStatusEnum::Draft]);
        $birthday->update(['type' => FieldTypeEnum::RecurringDate]);

        $section = $this->sectionNamed($version, 'Dates');

        $this->actingAs($user)->put(
            route('contacts.sections.update', ['vault' => $vault, 'contact' => $michael, 'templateVersionSection' => $section]),
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
            ->get(route('contacts.sections.edit', ['vault' => $vault, 'contact' => $michael, 'templateVersionSection' => $section]))
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
        $michael->update(['template_version_id' => $version->id]);

        $elsewhere = $this->versionWithSections($user, 'Work', ['Desk' => 'Floor']);
        $theirs = $this->sectionNamed($elsewhere, 'Desk');

        $this->actingAs($user)
            ->get(route('contacts.sections.edit', ['vault' => $vault, 'contact' => $michael, 'templateVersionSection' => $theirs]))
            ->assertNotFound();

        $this->actingAs($user)
            ->put(
                route('contacts.sections.update', ['vault' => $vault, 'contact' => $michael, 'templateVersionSection' => $theirs]),
                ['fields' => []],
            )
            ->assertNotFound();
    }

    #[Test]
    public function it_does_not_find_a_block_of_somebody_kept_in_another_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $elsewhere = $this->vaultReadyForContacts($user, 'Someone at work');
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $version = $this->versionWithSections($user, 'Health', ['Ongoing' => 'Knee']);
        $michael->update(['template_version_id' => $version->id]);
        $section = $this->sectionNamed($version, 'Ongoing');

        $this->actingAs($user)
            ->get(route('contacts.sections.edit', ['vault' => $elsewhere, 'contact' => $michael, 'templateVersionSection' => $section]))
            ->assertNotFound();

        $this->actingAs($user)
            ->put(
                route('contacts.sections.update', ['vault' => $elsewhere, 'contact' => $michael, 'templateVersionSection' => $section]),
                ['fields' => []],
            )
            ->assertNotFound();
    }

    #[Test]
    public function it_does_not_find_a_block_of_somebody_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $theirs = $this->vaultReadyForContacts($stranger);
        $michael = $this->contact($theirs, 'Michael', 'Scott');

        $version = $this->versionWithSections($stranger, 'Health', ['Ongoing' => 'Knee']);
        $michael->update(['template_version_id' => $version->id]);
        $section = $this->sectionNamed($version, 'Ongoing');

        $this->actingAs($user)
            ->get(route('contacts.sections.edit', ['vault' => $theirs, 'contact' => $michael, 'templateVersionSection' => $section]))
            ->assertNotFound();

        $this->actingAs($user)
            ->put(
                route('contacts.sections.update', ['vault' => $theirs, 'contact' => $michael, 'templateVersionSection' => $section]),
                ['fields' => []],
            )
            ->assertNotFound();
    }

    #[Test]
    public function it_offers_a_way_to_add_a_repetition_and_to_take_one_away(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $version = $this->versionWithSections($user, 'Information', ['Documents' => 'Document']);
        $michael->update(['template_version_id' => $version->id]);

        $documents = Field::query()->where('label', 'Document')->firstOrFail();
        $documents->sectionVersion->update(['status' => VersionStatusEnum::Draft]);
        $documents->update(['type' => FieldTypeEnum::Repeater, 'is_repeatable' => true]);
        $description = $this->childOf($documents, 'Description');

        $this->actingAs($user)
            ->get(route('contacts.sections.edit', [
                'vault' => $vault,
                'contact' => $michael,
                'templateVersionSection' => $this->sectionNamed($version, 'Documents'),
            ]))
            ->assertStatus(200)
            ->assertSee('Add another')
            ->assertSee('fields['.$documents->id.'][0][values]['.$description->id.']', false);
    }

    #[Test]
    public function it_writes_the_upload_a_repetition_carries(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        [$documents, $file, $description] = $this->documentsOn($user, $michael);
        $section = TemplateVersionSection::query()->where('section_version_id', $documents->section_version_id)->sole();

        $this->actingAs($user)
            ->put(
                route('contacts.sections.update', ['vault' => $vault, 'contact' => $michael, 'templateVersionSection' => $section]),
                ['fields' => [$documents->id => [
                    ['values' => [
                        $file->id => UploadedFile::fake()->create('lease.pdf', 8, 'application/pdf'),
                        $description->id => 'The lease',
                    ]],
                ]]],
            )
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        $stored = File::query()->sole();

        $this->assertSame('lease.pdf', $stored->original_name);
        $this->assertSame($vault->id, $stored->vault_id);
        $this->assertDatabaseHas('field_values', ['field_id' => $file->id, 'value_file_id' => $stored->id]);
        $this->assertDatabaseHas('field_values', ['field_id' => $description->id, 'value_string' => 'The lease']);
    }

    #[Test]
    public function it_takes_away_a_repetition_somebody_removed(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        [$documents, $file, $description] = $this->documentsOn($user, $michael);
        $section = TemplateVersionSection::query()->where('section_version_id', $documents->section_version_id)->sole();
        $url = route('contacts.sections.update', ['vault' => $vault, 'contact' => $michael, 'templateVersionSection' => $section]);

        $this->actingAs($user)->put($url, ['fields' => [$documents->id => [
            ['values' => [
                $file->id => UploadedFile::fake()->create('lease.pdf', 8, 'application/pdf'),
                $description->id => 'The lease',
            ]],
        ]]])->assertSessionHasNoErrors();

        $stored = File::query()->sole();

        $this->actingAs($user)->put($url, [])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('contacts.show', ['vault' => $vault, 'contact' => $michael, 'page' => 'information']));

        $this->assertSame(0, FieldValueRow::query()->count());
        $this->assertDatabaseMissing('field_values', ['field_id' => $description->id]);

        $this->assertSame(0, $stored->refresh()->reference_count);
        $this->assertNotNull($stored->orphaned_at);
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
        $section = TemplateVersionSection::query()->where('section_version_id', $documents->section_version_id)->sole();

        $this->actingAs($user)
            ->put(
                route('contacts.sections.update', ['vault' => $vault, 'contact' => $michael, 'templateVersionSection' => $section]),
                ['fields' => [$documents->id => [
                    ['values' => [$file->id => UploadedFile::fake()->create('holiday.gif', 4, 'image/gif')]],
                ]]],
            )
            ->assertRedirect(route('contacts.sections.edit', ['vault' => $vault, 'contact' => $michael, 'templateVersionSection' => $section]))
            ->assertSessionHasErrors('fields.'.$documents->id.'.0.values.'.$file->id);

        $this->assertSame(0, File::query()->count());
    }

    #[Test]
    public function it_reads_a_repetition_back_as_what_it_holds(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        [$documents, $file, $description] = $this->documentsOn($user, $michael);
        $section = TemplateVersionSection::query()->where('section_version_id', $documents->section_version_id)->sole();

        $this->actingAs($user)->put(
            route('contacts.sections.update', ['vault' => $vault, 'contact' => $michael, 'templateVersionSection' => $section]),
            ['fields' => [$documents->id => [
                ['values' => [
                    $file->id => UploadedFile::fake()->create('lease.pdf', 8, 'application/pdf'),
                    $description->id => 'The lease',
                ]],
            ]]],
        );

        $stored = File::query()->sole();

        $this->actingAs($user)
            ->get(route('contacts.show', ['vault' => $vault, 'contact' => $michael, 'page' => 'information']))
            ->assertStatus(200)
            ->assertSee('lease.pdf')
            ->assertSee('The lease')
            ->assertSee(route('files.show', ['vault' => $vault, 'file' => $stored]), false);
    }

    #[Test]
    public function a_block_that_takes_a_file_says_so_on_its_form(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        [$documents] = $this->documentsOn($user, $michael);
        $section = TemplateVersionSection::query()->where('section_version_id', $documents->section_version_id)->sole();

        $this->actingAs($user)
            ->get(route('contacts.sections.edit', ['vault' => $vault, 'contact' => $michael, 'templateVersionSection' => $section]))
            ->assertStatus(200)
            ->assertSee('enctype="multipart/form-data"', false);
    }

    /** @return array{0: Field, 1: Field, 2: Field} */
    #[Test]
    public function it_offers_a_reminder_under_every_date_it_draws(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        [$version, $birthday] = $this->versionWithADate($user, FieldTypeEnum::RecurringDate);
        $michael->update(['template_version_id' => $version->id]);

        $this->actingAs($user)
            ->get(route('contacts.sections.edit', [
                'vault' => $vault,
                'contact' => $michael,
                'templateVersionSection' => $this->sectionNamed($version, 'Dates'),
            ]))
            ->assertStatus(200)
            ->assertSee('Add a reminder')
            ->assertSee('name="reminders['.$birthday->id.'][on]"', false)
            ->assertSee('name="reminders['.$birthday->id.'][moments][0][direction]"', false);
    }

    #[Test]
    public function it_offers_a_reminder_under_a_complete_date_as_well_as_a_recurring_one(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        [$version, $metOn] = $this->versionWithADate($user, FieldTypeEnum::Date);
        $michael->update(['template_version_id' => $version->id]);

        $this->actingAs($user)
            ->get(route('contacts.sections.edit', [
                'vault' => $vault,
                'contact' => $michael,
                'templateVersionSection' => $this->sectionNamed($version, 'Dates'),
            ]))
            ->assertStatus(200)
            ->assertSee('name="reminders['.$metOn->id.'][on]"', false);
    }

    #[Test]
    public function it_saves_the_reminder_with_the_block(): void
    {
        Queue::fake();

        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        [$version, $birthday] = $this->versionWithADate($user, FieldTypeEnum::RecurringDate);
        $michael->update(['template_version_id' => $version->id]);
        $section = $this->sectionNamed($version, 'Dates');

        $this->actingAs($user)
            ->put(route('contacts.sections.update', [
                'vault' => $vault,
                'contact' => $michael,
                'templateVersionSection' => $section,
            ]), [
                'fields' => [$birthday->id => '1984-02-03'],
                'reminders' => [$birthday->id => $this->askedFor()],
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Saved.');

        $this->assertDatabaseHas('reminders', [
            'remindable_id' => $michael->id,
            'field_id' => $birthday->id,
            'name' => 'Ring Michael',
            'is_active' => true,
            'timezone' => 'Europe/Paris',
        ]);
    }

    #[Test]
    public function it_sends_the_block_back_open_when_a_reminder_is_asked_for_over_an_empty_date(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        [$version, $birthday] = $this->versionWithADate($user, FieldTypeEnum::RecurringDate);
        $michael->update(['template_version_id' => $version->id]);
        $section = $this->sectionNamed($version, 'Dates');

        $this->actingAs($user)
            ->put(route('contacts.sections.update', [
                'vault' => $vault,
                'contact' => $michael,
                'templateVersionSection' => $section,
            ]), [
                'fields' => [$birthday->id => ''],
                'reminders' => [$birthday->id => $this->askedFor()],
            ])
            ->assertRedirect(route('contacts.sections.edit', [
                'vault' => $vault,
                'contact' => $michael,
                'templateVersionSection' => $section,
            ]))
            ->assertSessionHasErrors('fields');

        $this->assertEquals(0, Reminder::query()->count());
    }

    #[Test]
    public function it_refuses_a_cadence_it_does_not_know(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        [$version, $birthday] = $this->versionWithADate($user, FieldTypeEnum::RecurringDate);
        $michael->update(['template_version_id' => $version->id]);

        $this->actingAs($user)
            ->put(route('contacts.sections.update', [
                'vault' => $vault,
                'contact' => $michael,
                'templateVersionSection' => $this->sectionNamed($version, 'Dates'),
            ]), [
                'fields' => [$birthday->id => '1984-02-03'],
                'reminders' => [$birthday->id => $this->askedFor(['cadence' => 'fortnightly'])],
            ])
            ->assertSessionHasErrors('reminders.'.$birthday->id.'.cadence');

        $this->assertEquals(0, Reminder::query()->count());
    }

    #[Test]
    public function it_reads_a_saved_reminder_back_into_the_block(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        [$version, $birthday] = $this->versionWithADate($user, FieldTypeEnum::RecurringDate);
        $michael->update(['template_version_id' => $version->id]);
        $section = $this->sectionNamed($version, 'Dates');

        $this->actingAs($user)->put(route('contacts.sections.update', [
            'vault' => $vault, 'contact' => $michael, 'templateVersionSection' => $section,
        ]), [
            'fields' => [$birthday->id => '1984-02-03'],
            'reminders' => [$birthday->id => $this->askedFor()],
        ]);

        $this->actingAs($user)
            ->get(route('contacts.sections.edit', [
                'vault' => $vault, 'contact' => $michael, 'templateVersionSection' => $section,
            ]))
            ->assertStatus(200)
            ->assertSee('value="Ring Michael"', false)
            ->assertSee('7 days before');
    }

    #[Test]
    public function it_marks_a_watched_date_when_the_block_is_being_read(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        [$version, $birthday] = $this->versionWithADate($user, FieldTypeEnum::RecurringDate);
        $michael->update(['template_version_id' => $version->id]);

        $this->actingAs($user)->put(route('contacts.sections.update', [
            'vault' => $vault, 'contact' => $michael, 'templateVersionSection' => $this->sectionNamed($version, 'Dates'),
        ]), [
            'fields' => [$birthday->id => '1984-02-03'],
            'reminders' => [$birthday->id => $this->askedFor()],
        ]);

        $this->actingAs($user)
            ->get(route('contacts.show', ['vault' => $vault, 'contact' => $michael, 'page' => 'important']))
            ->assertStatus(200)
            ->assertSee('7 days before')
            ->assertSee('M8 6.6V9.2l1.9 1.3', false);
    }

    #[Test]
    public function it_marks_nothing_beside_a_date_nobody_asked_to_be_told_about(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        [$version, $birthday] = $this->versionWithADate($user, FieldTypeEnum::RecurringDate);
        $michael->update(['template_version_id' => $version->id]);

        $this->actingAs($user)->put(route('contacts.sections.update', [
            'vault' => $vault, 'contact' => $michael, 'templateVersionSection' => $this->sectionNamed($version, 'Dates'),
        ]), ['fields' => [$birthday->id => '1984-02-03']]);

        $this->actingAs($user)
            ->get(route('contacts.show', ['vault' => $vault, 'contact' => $michael, 'page' => 'important']))
            ->assertStatus(200)
            ->assertDontSee('M8 6.6V9.2l1.9 1.3', false);
    }

    #[Test]
    public function it_marks_nothing_beside_a_date_whose_rule_has_gone_quiet(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        [$version, $birthday] = $this->versionWithADate($user, FieldTypeEnum::RecurringDate);
        $michael->update(['template_version_id' => $version->id]);

        $this->actingAs($user)->put(route('contacts.sections.update', [
            'vault' => $vault, 'contact' => $michael, 'templateVersionSection' => $this->sectionNamed($version, 'Dates'),
        ]), [
            'fields' => [$birthday->id => '1984-02-03'],
            'reminders' => [$birthday->id => $this->askedFor()],
        ]);

        Reminder::query()->firstOrFail()->update([
            'is_active' => false,
            'inactive_reason' => ReminderInactiveReasonEnum::RecordArchived,
            'deactivated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('contacts.show', ['vault' => $vault, 'contact' => $michael, 'page' => 'important']))
            ->assertStatus(200)
            ->assertDontSee('M8 6.6V9.2l1.9 1.3', false);
    }

    /** @return array{0: TemplateVersion, 1: Field} */
    #[Test]
    #[DataProvider('badMoments')]
    public function it_sends_the_block_back_open_when_the_moments_say_nothing_usable(array $moments): void
    {
        Queue::fake();

        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        [$version, $birthday] = $this->versionWithADate($user, FieldTypeEnum::RecurringDate);
        $michael->update(['template_version_id' => $version->id]);
        $section = $this->sectionNamed($version, 'Dates');

        $response = $this->actingAs($user)
            ->put(route('contacts.sections.update', [
                'vault' => $vault,
                'contact' => $michael,
                'templateVersionSection' => $section,
            ]), [
                'fields' => [$birthday->id => '1984-02-03'],
                'reminders' => [$birthday->id => $this->askedFor(['moments' => $moments])],
            ]);

        $response->assertRedirect()->assertSessionHasErrors("reminders.{$birthday->id}.moments");
        $this->assertDatabaseCount('reminders', 0);
    }

    /** @return array<string, array{0: array<int, array<string, string|null>>}> */
    public static function badMoments(): array
    {
        return [
            'the same moment twice' => [[
                ['direction' => 'before', 'amount' => '7', 'unit' => 'days'],
                ['direction' => 'before', 'amount' => '7', 'unit' => 'days'],
            ]],
            'twenty four months before' => [[
                ['direction' => 'before', 'amount' => '24', 'unit' => 'months'],
            ]],
            'four hundred days before' => [[
                ['direction' => 'before', 'amount' => '400', 'unit' => 'days'],
            ]],
            'sixty weeks after' => [[
                ['direction' => 'after', 'amount' => '60', 'unit' => 'weeks'],
            ]],
            'no moment at all' => [[]],
            'a moment with no amount' => [[
                ['direction' => 'before', 'amount' => null, 'unit' => 'days'],
            ]],
        ];
    }

    #[Test]
    public function it_leaves_the_moments_alone_when_nobody_asked_for_a_reminder(): void
    {
        Queue::fake();

        $user = User::factory()->create(['timezone' => 'Europe/Paris']);
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        [$version, $birthday] = $this->versionWithADate($user, FieldTypeEnum::RecurringDate);
        $michael->update(['template_version_id' => $version->id]);
        $section = $this->sectionNamed($version, 'Dates');

        $this->actingAs($user)
            ->put(route('contacts.sections.update', [
                'vault' => $vault,
                'contact' => $michael,
                'templateVersionSection' => $section,
            ]), [
                'fields' => [$birthday->id => '1984-02-03'],
                'reminders' => [$birthday->id => $this->askedFor(['on' => null, 'moments' => []])],
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'Saved.');
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
        $michael->update(['template_version_id' => $version->id]);
        $section = $this->sectionNamed($version, 'Dates');

        $this->actingAs($user)
            ->put(route('contacts.sections.update', [
                'vault' => $vault,
                'contact' => $michael,
                'templateVersionSection' => $section,
            ]), [
                'fields' => [$birthday->id => '1984-02-03'],
                'reminders' => [$birthday->id => $this->askedFor()],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('reminder_occurrences', [
            'source_local_date' => '2027-02-03',
            'local_date' => '2027-01-27',
            'status' => 'pending',
        ]);
    }

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
            'recurs' => '1',
            'cadence' => 'yearly',
            'interval' => '1',
            'time' => '09:00',
            'timezone' => 'Europe/Paris',
            'moments' => [['direction' => 'before', 'amount' => '7', 'unit' => 'days']],
        ], $changes);
    }

    private function documentsOn(User $user, Contact $contact): array
    {
        $version = $this->versionWithSections($user, 'Information', ['Documents' => 'Document']);
        $contact->update(['template_version_id' => $version->id]);

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
    private function versionWithSections(User $user, string $pageLabel, array $sections): TemplateVersion
    {
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);

        $page = TemplateVersionPage::factory()->create([
            'template_version_id' => $version->id,
            'account_id' => $version->account_id,
            'key' => Str::slug($pageLabel),
            'label' => $pageLabel,
        ]);

        $position = 1;

        foreach ($sections as $sectionName => $fieldLabel) {
            $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => $sectionName]);
            $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);

            Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => $fieldLabel]);

            TemplateVersionSection::factory()->create([
                'template_version_id' => $version->id,
                'template_version_page_id' => $page->id,
                'section_version_id' => $sectionVersion->id,
                'position' => $position++,
            ]);
        }

        $version->update(['status' => VersionStatusEnum::Published]);

        return $version->refresh();
    }

    private function sectionNamed(TemplateVersion $version, string $name): TemplateVersionSection
    {
        return $version->assembly
            ->first(fn (TemplateVersionSection $row): bool => $row->sectionVersion->section->name === $name);
    }

    /** @param  array<string, mixed>  $attributes */
    private function contact(Vault $vault, string $firstName, string $lastName, array $attributes = []): Contact
    {
        return Contact::factory()->create([
            'vault_id' => $vault->id,
            'account_id' => $vault->account_id,
            'template_version_id' => $vault->defaultTemplates()->sole()->template_version_id,
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
            'applies_to' => TemplateAppliesToEnum::Contact,
            'template_version_id' => $template->published_version_id,
        ]);

        return $vault;
    }

    private function publishedContactTemplate(User $user, string $name): Template
    {
        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'name' => $name,
        ]);

        $version = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);

        $template->published_version_id = $version->id;
        $template->save();

        return $template;
    }
}
