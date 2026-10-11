<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Enums\CountryEnum;
use App\Enums\FieldTypeEnum;
use App\Enums\ValueListEnum;
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
            ->assertSee('name="fields['.$knee->id.']"', false)
            ->assertSee('Save this section');
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
    public function it_saves_an_address_and_what_it_is_for(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');
        $labels = ValueList::factory()->create(['account_id' => $user->account_id]);
        $work = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $labels->id, 'label' => 'Work']);
        [$address, $section] = $this->addressOn($user, $michael, ['value_list_id' => $labels->id]);
        $usa = $this->countryOf($user, CountryEnum::UnitedStates);

        $this->actingAs($user)
            ->put(
                route('contacts.sections.update', ['vault' => $vault, 'contact' => $michael, 'structureVersionSection' => $section]),
                [
                    'fields' => [$address->id => [
                        'street' => '1725 Slough Avenue',
                        'extended' => '',
                        'po_box' => '',
                        'city' => 'Scranton',
                        'region' => 'PA',
                        'postal_code' => '18505',
                        'country' => (string) $usa->id,
                    ]],
                    'labels' => [$address->id => (string) $work->id],
                ],
            )
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        $stored = FieldValue::query()->where('field_id', $address->id)->sole();

        $this->assertSame($work->id, $stored->value_list_item_id);
        $this->assertEquals([
            'po_box' => '',
            'extended' => '',
            'street' => '1725 Slough Avenue',
            'city' => 'Scranton',
            'region' => 'PA',
            'postal_code' => '18505',
            'country' => 'United States',
        ], $stored->value_json);
    }

    #[Test]
    public function it_saves_the_wording_somebody_typed_for_the_label_of_an_address(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $dwight = $this->contact($vault, 'Dwight', 'Schrute');
        $labels = ValueList::factory()->create(['account_id' => $user->account_id]);
        $custom = ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $labels->id,
            'key' => ValueListItem::OPEN_KEY,
            'label' => 'Custom...',
        ]);
        [$address, $section] = $this->addressOn($user, $dwight, ['value_list_id' => $labels->id]);

        $this->actingAs($user)
            ->put(
                route('contacts.sections.update', ['vault' => $vault, 'contact' => $dwight, 'structureVersionSection' => $section]),
                [
                    'fields' => [$address->id => ['street' => 'Schrute Farms', 'city' => 'Honesdale']],
                    'labels' => [$address->id => (string) $custom->id],
                    'label_wordings' => [$address->id => 'Beet farm'],
                ],
            )
            ->assertSessionHasNoErrors();

        $stored = FieldValue::query()->where('field_id', $address->id)->sole();

        $this->assertSame($custom->id, $stored->value_list_item_id);
        $this->assertSame('Beet farm', $stored->label_wording);
        $this->assertSame('Schrute Farms', $stored->value_json['street']);
    }

    #[Test]
    public function it_clears_an_address_whose_boxes_all_came_back_empty(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');
        [$address, $section] = $this->addressOn($user, $michael);
        $route = route('contacts.sections.update', ['vault' => $vault, 'contact' => $michael, 'structureVersionSection' => $section]);

        $this->actingAs($user)->put($route, ['fields' => [$address->id => ['city' => 'Scranton']]]);
        $this->assertSame(1, FieldValue::query()->count());

        $this->actingAs($user)
            ->put($route, ['fields' => [$address->id => array_fill_keys(FieldValue::ADDRESS_PARTS, '')]])
            ->assertSessionHasNoErrors();

        $this->assertSame(0, FieldValue::query()->count());
    }

    #[Test]
    public function it_sends_the_block_back_open_when_a_required_address_is_left_empty(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');
        [$address, $section] = $this->addressOn($user, $michael, ['is_required' => true]);

        $response = $this->actingAs($user)->put(
            route('contacts.sections.update', ['vault' => $vault, 'contact' => $michael, 'structureVersionSection' => $section]),
            ['fields' => [$address->id => array_fill_keys(FieldValue::ADDRESS_PARTS, '')]],
        );

        $response->assertRedirect(route('contacts.sections.edit', ['vault' => $vault, 'contact' => $michael, 'structureVersionSection' => $section]));
        $response->assertSessionHasErrors('fields');
        $this->assertStringContainsString('Address', session('errors')->first('fields'));
        $this->assertSame(0, FieldValue::query()->count());
    }

    #[Test]
    public function it_refuses_a_street_longer_than_its_box_and_names_the_box(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');
        [$address, $section] = $this->addressOn($user, $michael);

        $this->actingAs($user)
            ->put(
                route('contacts.sections.update', ['vault' => $vault, 'contact' => $michael, 'structureVersionSection' => $section]),
                ['fields' => [$address->id => ['street' => str_repeat('a', 256)]]],
            )
            ->assertSessionHasErrors("fields.{$address->id}.street");

        $this->assertStringContainsString('street of address', session('errors')->first("fields.{$address->id}.street"));
        $this->assertSame(0, FieldValue::query()->count());
    }

    #[Test]
    public function it_refuses_a_country_the_account_does_not_offer(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');
        [$address, $section] = $this->addressOn($user, $michael);
        $this->countryOf($user, CountryEnum::UnitedStates);
        $theirs = $this->countryOf(User::factory()->create(), CountryEnum::Canada);

        $this->actingAs($user)
            ->put(
                route('contacts.sections.update', ['vault' => $vault, 'contact' => $michael, 'structureVersionSection' => $section]),
                ['fields' => [$address->id => ['city' => 'Toronto', 'country' => (string) $theirs->id]]],
            )
            ->assertSessionHasErrors("fields.{$address->id}.country");

        $this->assertSame(0, FieldValue::query()->count());
    }

    #[Test]
    public function it_opens_an_address_as_seven_boxes_to_fill_in(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');
        [$address, $section] = $this->addressOn($user, $michael);

        $response = $this->actingAs($user)->get(route('contacts.sections.edit', [
            'vault' => $vault,
            'contact' => $michael,
            'structureVersionSection' => $section,
        ]));

        $response->assertStatus(200);

        foreach (FieldValue::ADDRESS_PARTS as $part) {
            $response->assertSee('name="fields['.$address->id.']['.$part.']"', false);
        }

        $response->assertDontSee('This kind of field cannot be filled in here yet.');
    }

    #[Test]
    public function it_opens_an_address_a_device_wrote_with_every_part_it_wrote(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $dwight = $this->contact($vault, 'Dwight', 'Schrute');
        [$address, $section] = $this->addressOn($user, $dwight);
        $this->countryOf($user, CountryEnum::UnitedStates);
        $this->deviceAddressOn($dwight, $address);

        $this->actingAs($user)
            ->get(route('contacts.sections.edit', ['vault' => $vault, 'contact' => $dwight, 'structureVersionSection' => $section]))
            ->assertStatus(200)
            ->assertSee('value="PO Box 12"', false)
            ->assertSee('value="Suite 3"', false)
            ->assertSee('<option value="keep" selected>USA</option>', false);
    }

    #[Test]
    public function it_keeps_every_part_a_device_wrote_when_only_the_city_changes(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $dwight = $this->contact($vault, 'Dwight', 'Schrute');
        [$address, $section] = $this->addressOn($user, $dwight);
        $this->countryOf($user, CountryEnum::UnitedStates);
        $this->deviceAddressOn($dwight, $address);

        $this->actingAs($user)
            ->put(
                route('contacts.sections.update', ['vault' => $vault, 'contact' => $dwight, 'structureVersionSection' => $section]),
                ['fields' => [$address->id => [
                    'street' => '1725 Slough Avenue',
                    'extended' => 'Suite 3',
                    'po_box' => 'PO Box 12',
                    'city' => 'Stamford',
                    'region' => 'PA',
                    'postal_code' => '18505',
                    'country' => 'keep',
                ]]],
            )
            ->assertSessionHasNoErrors();

        $this->assertEquals([
            'po_box' => 'PO Box 12',
            'extended' => 'Suite 3',
            'street' => '1725 Slough Avenue',
            'city' => 'Stamford',
            'region' => 'PA',
            'postal_code' => '18505',
            'country' => 'USA',
        ], FieldValue::query()->where('field_id', $address->id)->sole()->value_json);
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
    public function it_does_not_find_a_block_of_somebody_kept_in_another_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $elsewhere = $this->vaultReadyForContacts($user, 'Someone at work');
        $michael = $this->contact($vault, 'Michael', 'Scott');

        $version = $this->versionWithSections($user, 'Health', ['Ongoing' => 'Knee']);
        $michael->update(['structure_version_id' => $version->id]);
        $section = $this->sectionNamed($version, 'Ongoing');

        $this->actingAs($user)
            ->get(route('contacts.sections.edit', ['vault' => $elsewhere, 'contact' => $michael, 'structureVersionSection' => $section]))
            ->assertNotFound();

        $this->actingAs($user)
            ->put(
                route('contacts.sections.update', ['vault' => $elsewhere, 'contact' => $michael, 'structureVersionSection' => $section]),
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
        $michael->update(['structure_version_id' => $version->id]);

        $documents = Field::query()->where('label', 'Document')->firstOrFail();
        $documents->sectionVersion->update(['status' => VersionStatusEnum::Draft]);
        $documents->update(['type' => FieldTypeEnum::Repeater, 'is_repeatable' => true]);
        $description = $this->childOf($documents, 'Description');

        $this->actingAs($user)
            ->get(route('contacts.sections.edit', [
                'vault' => $vault,
                'contact' => $michael,
                'structureVersionSection' => $this->sectionNamed($version, 'Documents'),
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
        $section = StructureVersionSection::query()->where('section_version_id', $documents->section_version_id)->sole();

        $this->actingAs($user)
            ->put(
                route('contacts.sections.update', ['vault' => $vault, 'contact' => $michael, 'structureVersionSection' => $section]),
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
    public function it_writes_the_type_beside_the_email_of_a_repetition(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        [$documents] = $this->documentsOn($user, $michael);
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $work = ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'key' => 'work',
            'label' => 'Work',
        ]);
        $email = $this->childOf($documents, 'Email', [
            'type' => FieldTypeEnum::Email,
            'value_list_id' => $list->id,
        ]);
        $section = StructureVersionSection::query()->where('section_version_id', $documents->section_version_id)->sole();

        $this->actingAs($user)->put(
            route('contacts.sections.update', ['vault' => $vault, 'contact' => $michael, 'structureVersionSection' => $section]),
            ['fields' => [$documents->id => [
                [
                    'values' => [$email->id => 'michael@dundermifflin.example'],
                    'labels' => [$email->id => (string) $work->id],
                ],
            ]]],
        )->assertSessionHasNoErrors();

        $this->assertDatabaseHas('field_values', [
            'field_id' => $email->id,
            'value_string' => 'michael@dundermifflin.example',
            'value_list_item_id' => $work->id,
        ]);
    }

    #[Test]
    public function it_names_the_type_of_a_repeated_email_inside_its_own_repetition(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        [$documents] = $this->documentsOn($user, $michael);
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'label' => 'Work']);
        $email = $this->childOf($documents, 'Email', [
            'type' => FieldTypeEnum::Email,
            'value_list_id' => $list->id,
        ]);

        $this->actingAs($user)
            ->get(route('contacts.sections.edit', [
                'vault' => $vault,
                'contact' => $michael,
                'structureVersionSection' => StructureVersionSection::query()->where('section_version_id', $documents->section_version_id)->sole(),
            ]))
            ->assertStatus(200)
            ->assertSee('fields['.$documents->id.'][0][labels]['.$email->id.']', false)
            ->assertDontSee('labels['.$documents->id.'][0]', false);
    }

    #[Test]
    public function it_writes_two_repeated_addresses_each_with_what_it_is_for(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $dwight = $this->contact($vault, 'Dwight', 'Schrute');

        $version = $this->versionWithSections($user, 'Information', ['Addresses' => 'Addresses']);
        $dwight->update(['structure_version_id' => $version->id]);
        $addresses = Field::query()->where('label', 'Addresses')->firstOrFail();
        $addresses->sectionVersion->update(['status' => VersionStatusEnum::Draft]);
        $addresses->update(['type' => FieldTypeEnum::Repeater, 'is_repeatable' => true]);
        $list = ValueList::factory()->create(['account_id' => $user->account_id]);
        $home = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'label' => 'Home']);
        $holidayHome = ValueListItem::factory()->create(['account_id' => $user->account_id, 'value_list_id' => $list->id, 'label' => 'Holiday home']);
        $address = $this->childOf($addresses, 'Address', ['type' => FieldTypeEnum::Address, 'value_list_id' => $list->id]);

        $this->actingAs($user)
            ->put(
                route('contacts.sections.update', ['vault' => $vault, 'contact' => $dwight, 'structureVersionSection' => $this->sectionNamed($version, 'Addresses')]),
                ['fields' => [$addresses->id => [
                    [
                        'values' => [$address->id => ['street' => '1725 Slough Avenue', 'city' => 'Scranton']],
                        'labels' => [$address->id => (string) $home->id],
                    ],
                    [
                        'values' => [$address->id => ['street' => 'Schrute Farms', 'city' => 'Honesdale']],
                        'labels' => [$address->id => (string) $holidayHome->id],
                    ],
                ]]],
            )
            ->assertSessionHasNoErrors();

        $rows = FieldValueRow::query()->where('field_id', $addresses->id)->orderBy('position')->get();

        $this->assertCount(2, $rows);

        $first = FieldValue::query()->where('field_value_row_id', $rows[0]->id)->sole();
        $second = FieldValue::query()->where('field_value_row_id', $rows[1]->id)->sole();

        $this->assertEqualsCanonicalizing(FieldValue::ADDRESS_PARTS, array_keys($first->value_json));
        $this->assertSame('1725 Slough Avenue', $first->value_json['street']);
        $this->assertSame($home->id, $first->value_list_item_id);
        $this->assertSame('Schrute Farms', $second->value_json['street']);
        $this->assertSame($holidayHome->id, $second->value_list_item_id);
    }

    #[Test]
    public function it_takes_away_a_repetition_somebody_removed(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        [$documents, $file, $description] = $this->documentsOn($user, $michael);
        $section = StructureVersionSection::query()->where('section_version_id', $documents->section_version_id)->sole();
        $url = route('contacts.sections.update', ['vault' => $vault, 'contact' => $michael, 'structureVersionSection' => $section]);

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
            ->assertSessionHasErrors(['fields.'.$documents->id.'.0.values.'.$file->id => 'Uploads are unavailable on this account for now.']);

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
        $section = StructureVersionSection::query()->where('section_version_id', $documents->section_version_id)->sole();

        $this->actingAs($user)->put(
            route('contacts.sections.update', ['vault' => $vault, 'contact' => $michael, 'structureVersionSection' => $section]),
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
        $section = StructureVersionSection::query()->where('section_version_id', $documents->section_version_id)->sole();

        $this->actingAs($user)
            ->get(route('contacts.sections.edit', ['vault' => $vault, 'contact' => $michael, 'structureVersionSection' => $section]))
            ->assertStatus(200)
            ->assertSee('enctype="multipart/form-data"', false);
    }

    #[Test]
    public function it_offers_a_reminder_under_every_date_it_draws(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        [$version, $birthday] = $this->versionWithADate($user, FieldTypeEnum::RecurringDate);
        $michael->update(['structure_version_id' => $version->id]);

        $this->actingAs($user)
            ->get(route('contacts.sections.edit', [
                'vault' => $vault,
                'contact' => $michael,
                'structureVersionSection' => $this->sectionNamed($version, 'Dates'),
            ]))
            ->assertStatus(200)
            ->assertSee('Remind me about this date')
            ->assertSee('I do not know the year')
            ->assertSee('name="reminders['.$birthday->id.'][on]"', false)
            ->assertSee('name="reminders['.$birthday->id.'][repeat]"', false)
            ->assertSee('name="reminders['.$birthday->id.'][time]"', false)
            ->assertDontSee('[moments]', false);
    }

    #[Test]
    public function it_offers_a_reminder_under_a_complete_date_as_well_as_a_recurring_one(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        [$version, $metOn] = $this->versionWithADate($user, FieldTypeEnum::Date);
        $michael->update(['structure_version_id' => $version->id]);

        $this->actingAs($user)
            ->get(route('contacts.sections.edit', [
                'vault' => $vault,
                'contact' => $michael,
                'structureVersionSection' => $this->sectionNamed($version, 'Dates'),
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
        $michael->update(['structure_version_id' => $version->id]);
        $section = $this->sectionNamed($version, 'Dates');

        $this->actingAs($user)
            ->put(route('contacts.sections.update', [
                'vault' => $vault,
                'contact' => $michael,
                'structureVersionSection' => $section,
            ]), [
                'fields' => [$birthday->id => ''],
                'reminders' => [$birthday->id => $this->askedFor()],
            ])
            ->assertRedirect(route('contacts.sections.edit', [
                'vault' => $vault,
                'contact' => $michael,
                'structureVersionSection' => $section,
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
    public function it_reads_a_saved_reminder_back_into_the_block(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        [$version, $birthday] = $this->versionWithADate($user, FieldTypeEnum::RecurringDate);
        $michael->update(['structure_version_id' => $version->id]);
        $section = $this->sectionNamed($version, 'Dates');

        $this->actingAs($user)->put(route('contacts.sections.update', [
            'vault' => $vault, 'contact' => $michael, 'structureVersionSection' => $section,
        ]), [
            'fields' => [$birthday->id => '1984-02-03'],
            'reminders' => [$birthday->id => $this->askedFor()],
        ]);

        $this->actingAs($user)
            ->get(route('contacts.sections.edit', [
                'vault' => $vault, 'contact' => $michael, 'structureVersionSection' => $section,
            ]))
            ->assertStatus(200)
            ->assertSee('value="Ring Michael"', false)
            ->assertSee('<option value="yearly:1" selected', false)
            ->assertSee('Next reminder on');
    }

    #[Test]
    public function it_reads_how_often_from_the_one_list_the_panel_offers(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');

        [$version, $birthday] = $this->versionWithADate($user, FieldTypeEnum::RecurringDate);
        $michael->update(['structure_version_id' => $version->id]);

        $this->actingAs($user)->put(route('contacts.sections.update', [
            'vault' => $vault, 'contact' => $michael, 'structureVersionSection' => $this->sectionNamed($version, 'Dates'),
        ]), [
            'fields' => [$birthday->id => '1984-02-03'],
            'reminders' => [$birthday->id => $this->askedFor(['repeat' => 'yearly:5'])],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('reminders', ['cadence' => 'yearly', 'frequency_interval' => 5]);
    }

    #[Test]
    public function it_saves_the_date_alone_when_nobody_asked_for_a_reminder(): void
    {
        Queue::fake();

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
                'reminders' => [$birthday->id => $this->askedFor(['on' => null])],
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

    #[Test]
    public function it_shows_a_note_across_its_section(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');
        [$notes, $note] = $this->notesOn($user, $michael);
        $this->writeNote($michael, $notes, $note, 'Hates Toby with a passion.');

        $this->actingAs($user)
            ->get(route('contacts.show', ['vault' => $vault, 'contact' => $michael, 'page' => 'notebook']))
            ->assertStatus(200)
            ->assertSee('Hates Toby with a passion.')
            ->assertSee('Added ');
    }

    #[Test]
    public function it_folds_a_long_note_behind_read_more(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');
        [$notes, $note] = $this->notesOn($user, $michael);
        $row = $this->writeNote($michael, $notes, $note, "Regional manager.\n\nImprov class.\n\nDinner party.\n\nScott’s Tots.\n\nThe Dundies.");

        $this->actingAs($user)
            ->get(route('contacts.show', ['vault' => $vault, 'contact' => $michael, 'page' => 'notebook']))
            ->assertStatus(200)
            ->assertSee('Read more')
            ->assertSee('aria-controls="note-'.$row->id.'-rest"', false)
            ->assertSee('id="note-'.$row->id.'-rest"', false)
            ->assertSee('The Dundies.');
    }

    #[Test]
    public function it_offers_to_add_a_note_when_there_is_none(): void
    {
        $user = User::factory()->create();
        $vault = $this->vaultReadyForContacts($user);
        $michael = $this->contact($vault, 'Michael', 'Scott');
        $this->notesOn($user, $michael);
        $version = $michael->refresh()->structureVersion;

        $this->actingAs($user)
            ->get(route('contacts.show', ['vault' => $vault, 'contact' => $michael, 'page' => 'notebook']))
            ->assertStatus(200)
            ->assertSee('No note yet')
            ->assertSee('Add a note')
            ->assertSee(route('contacts.sections.edit', [
                'vault' => $vault,
                'contact' => $michael,
                'structureVersionSection' => $this->sectionNamed($version, 'Notes'),
            ]), false);
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

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{0: Field, 1: StructureVersionSection}
     */
    private function addressOn(User $user, Contact $contact, array $attributes = []): array
    {
        $version = $this->versionWithSections($user, 'Information', ['Addresses' => 'Address']);
        $contact->update(['structure_version_id' => $version->id]);

        $address = Field::query()->where('label', 'Address')->firstOrFail();
        $address->sectionVersion->update(['status' => VersionStatusEnum::Draft]);
        $address->update(['type' => FieldTypeEnum::Address, ...$attributes]);
        $address->sectionVersion->update(['status' => VersionStatusEnum::Published]);

        return [$address->refresh(), $this->sectionNamed($version, 'Addresses')];
    }

    private function deviceAddressOn(Contact $contact, Field $address): FieldValue
    {
        return FieldValue::factory()->create([
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'valuable_type' => $contact->getMorphClass(),
            'valuable_id' => $contact->id,
            'field_id' => $address->id,
            'field_key' => $address->key,
            'value_string' => null,
            'value_json' => [
                'po_box' => 'PO Box 12',
                'extended' => 'Suite 3',
                'street' => '1725 Slough Avenue',
                'city' => 'Scranton',
                'region' => 'PA',
                'postal_code' => '18505',
                'country' => 'USA',
            ],
        ]);
    }

    private function countryOf(User $user, CountryEnum $country): ValueListItem
    {
        $countries = ValueList::query()
            ->where('account_id', $user->account_id)
            ->where('key', ValueListEnum::Countries->value)
            ->first()
            ?? ValueList::factory()->supplied(ValueListEnum::Countries)->create(['account_id' => $user->account_id]);

        return ValueListItem::factory()->supplied($country)->create([
            'account_id' => $user->account_id,
            'value_list_id' => $countries->id,
        ]);
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
