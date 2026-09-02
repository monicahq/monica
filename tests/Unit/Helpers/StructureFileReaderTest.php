<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Enums\FieldTypeEnum;
use App\Enums\RelationTargetEnum;
use App\Enums\ValueListEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\StructureFileIsNotReadable;
use App\Helpers\StructureFile;
use App\Helpers\StructureFileReader;
use App\Helpers\ValueListSnapshot;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionSection;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StructureFileReaderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reads_a_file_this_application_wrote(): void
    {
        $user = User::factory()->create();
        $file = StructureFile::ofTemplate($this->publishedTemplate($user));

        $read = StructureFileReader::read(StructureFile::encode($file));

        $this->assertEquals('template', $read['format']);
        $this->assertEquals(1, $read['format_version']);
        $this->assertEquals('Dunder Mifflin contact', $read['template']['name']);
        $this->assertCount(1, $read['template']['pages']);
        $this->assertCount(1, $read['template']['pages'][0]['sections']);
        $this->assertEquals('Personal details', $read['template']['pages'][0]['sections'][0]['section']['name']);
        $this->assertCount(4, $read['template']['pages'][0]['sections'][0]['section']['fields']);
    }

    #[Test]
    public function it_reads_a_section_file(): void
    {
        $user = User::factory()->create();
        $template = $this->publishedTemplate($user);
        $row = $template->publishedVersion->assembly->first();

        $read = StructureFileReader::read(StructureFile::encode(StructureFile::ofSection($row)));

        $this->assertEquals('section', $read['format']);
        $this->assertEquals('Personal details', $read['section']['name']);
        $this->assertEquals('Dunder Mifflin contact', $read['from_template']['name']);
    }

    #[Test]
    public function it_reads_the_lists_of_choices_by_the_reference_the_fields_name(): void
    {
        $user = User::factory()->create();
        $file = StructureFile::ofTemplate($this->publishedTemplate($user));

        $read = StructureFileReader::read(StructureFile::encode($file));

        $this->assertArrayHasKey(ValueListEnum::PhoneLabels->value, $read['value_lists']);
        $this->assertEquals('Mobile', $read['value_lists'][ValueListEnum::PhoneLabels->value]['items'][0]['label']);

        $fields = collect($read['template']['pages'][0]['sections'][0]['section']['fields']);

        $this->assertEquals(
            ValueListEnum::PhoneLabels->value,
            $fields->firstWhere('type', FieldTypeEnum::Select->value)['value_list_reference'],
        );
    }

    #[Test]
    public function it_keeps_the_repeater_a_field_sits_in(): void
    {
        $user = User::factory()->create();
        $file = StructureFile::ofTemplate($this->publishedTemplate($user));

        $read = StructureFileReader::read(StructureFile::encode($file));

        $fields = collect($read['template']['pages'][0]['sections'][0]['section']['fields']);

        $this->assertEquals('phone_numbers', $fields->firstWhere('key', 'number')['parent_field_key']);
    }

    #[Test]
    public function it_refuses_an_empty_paste(): void
    {
        $this->expectException(StructureFileIsNotReadable::class);

        StructureFileReader::read("   \n  ");
    }

    #[Test]
    public function it_refuses_a_file_heavier_than_the_ceiling(): void
    {
        $this->expectException(StructureFileIsNotReadable::class);

        StructureFileReader::read(str_repeat('a', StructureFileReader::MAX_BYTES + 1));
    }

    #[Test]
    public function it_refuses_text_that_is_not_utf_eight(): void
    {
        $this->expectException(StructureFileIsNotReadable::class);

        StructureFileReader::read("\xB1\x31");
    }

    #[Test]
    public function it_refuses_text_that_is_not_json(): void
    {
        $this->expectException(StructureFileIsNotReadable::class);

        StructureFileReader::read('Michael Scott');
    }

    #[Test]
    public function it_refuses_json_nested_deeper_than_it_will_read(): void
    {
        $raw = str_repeat('[', 200).str_repeat(']', 200);

        $this->expectException(StructureFileIsNotReadable::class);

        StructureFileReader::read($raw);
    }

    #[Test]
    public function it_refuses_a_list_rather_than_a_block(): void
    {
        $this->expectException(StructureFileIsNotReadable::class);

        StructureFileReader::read('[1, 2, 3]');
    }

    #[Test]
    public function it_refuses_a_file_with_no_header(): void
    {
        $this->expectException(StructureFileIsNotReadable::class);

        StructureFileReader::read(json_encode(['template' => ['name' => 'Dunder Mifflin contact']]));
    }

    #[Test]
    public function it_refuses_a_kind_of_file_it_does_not_read(): void
    {
        $this->expectException(StructureFileIsNotReadable::class);

        StructureFileReader::read(json_encode(['monica' => ['format' => 'contacts', 'format_version' => 1]]));
    }

    #[Test]
    public function it_refuses_a_file_written_in_a_later_format(): void
    {
        $this->expectExceptionMessageMatches('/format 2/');

        StructureFileReader::read(json_encode([
            'monica' => ['format' => 'template', 'format_version' => StructureFile::FORMAT_VERSION + 1],
        ]));
    }

    #[Test]
    public function it_refuses_a_name_this_version_does_not_know(): void
    {
        $file = $this->file();
        $file['template']['run_this'] = 'rm -rf';

        $this->expectException(StructureFileIsNotReadable::class);

        StructureFileReader::read(json_encode($file));
    }

    /**
     * A file exported before whether a field drives a reminder became a property
     * of its type still carries the key. Refusing it would refuse every
     * structure already in circulation, so the key is read by nothing and the
     * file imports.
     */
    #[Test]
    public function it_still_reads_a_file_carrying_the_reminder_flag_it_no_longer_has(): void
    {
        $file = $this->file();
        $file['template']['pages'][0]['sections'][0]['section']['fields'][0]['is_reminder_source'] = true;

        $read = StructureFileReader::read(json_encode($file));

        $field = $read['template']['pages'][0]['sections'][0]['section']['fields'][0];

        $this->assertEquals('nickname', $field['key']);
        $this->assertArrayNotHasKey('is_reminder_source', $field);
    }

    #[Test]
    public function it_refuses_a_kind_of_field_it_cannot_render(): void
    {
        $file = $this->file();
        $file['template']['pages'][0]['sections'][0]['section']['fields'][0]['type'] = 'php';

        $this->expectException(StructureFileIsNotReadable::class);

        StructureFileReader::read(json_encode($file));
    }

    #[Test]
    public function it_refuses_a_field_pointing_at_a_list_the_file_does_not_carry(): void
    {
        $file = $this->file();
        $file['template']['pages'][0]['sections'][0]['section']['fields'][0]['type'] = 'select';
        $file['template']['pages'][0]['sections'][0]['section']['fields'][0]['value_list_reference'] = 'countries';

        $this->expectException(StructureFileIsNotReadable::class);

        StructureFileReader::read(json_encode($file));
    }

    #[Test]
    public function it_refuses_a_field_offering_choices_from_nowhere(): void
    {
        $file = $this->file();
        $file['template']['pages'][0]['sections'][0]['section']['fields'][0]['type'] = 'select';

        $this->expectException(StructureFileIsNotReadable::class);

        StructureFileReader::read(json_encode($file));
    }

    #[Test]
    public function it_refuses_a_field_sitting_in_something_that_is_not_a_repeater(): void
    {
        $file = $this->file();
        $file['template']['pages'][0]['sections'][0]['section']['fields'][] = [
            'key' => 'street',
            'type' => 'text',
            'label' => 'Street',
            'parent_field_key' => 'nickname',
        ];

        $this->expectException(StructureFileIsNotReadable::class);

        StructureFileReader::read(json_encode($file));
    }

    #[Test]
    public function it_refuses_a_repeater_inside_another_repeater(): void
    {
        $file = $this->file();
        $file['template']['pages'][0]['sections'][0]['section']['fields'] = [
            ['key' => 'phones', 'type' => 'repeater', 'label' => 'Phones', 'is_repeatable' => true],
            ['key' => 'more', 'type' => 'repeater', 'label' => 'More', 'is_repeatable' => true, 'parent_field_key' => 'phones'],
        ];

        $this->expectException(StructureFileIsNotReadable::class);

        StructureFileReader::read(json_encode($file));
    }

    #[Test]
    public function it_refuses_a_field_sitting_inside_itself(): void
    {
        $file = $this->file();
        $file['template']['pages'][0]['sections'][0]['section']['fields'] = [
            ['key' => 'phones', 'type' => 'repeater', 'label' => 'Phones', 'is_repeatable' => true, 'parent_field_key' => 'phones'],
        ];

        $this->expectException(StructureFileIsNotReadable::class);

        StructureFileReader::read(json_encode($file));
    }

    #[Test]
    public function it_refuses_two_fields_of_one_section_carrying_the_same_key(): void
    {
        $file = $this->file();
        $file['template']['pages'][0]['sections'][0]['section']['fields'][] = [
            'key' => 'nickname',
            'type' => 'text',
            'label' => 'Nickname again',
        ];

        $this->expectException(StructureFileIsNotReadable::class);

        StructureFileReader::read(json_encode($file));
    }

    #[Test]
    public function it_refuses_more_fields_than_a_file_is_meant_to_carry(): void
    {
        $file = $this->file();
        $fields = [];

        for ($i = 0; $i < StructureFileReader::MAX_FIELDS_PER_SECTION + 1; $i++) {
            $fields[] = ['key' => "field_{$i}", 'type' => 'text', 'label' => "Field {$i}"];
        }

        $file['template']['pages'][0]['sections'][0]['section']['fields'] = $fields;

        $this->expectException(StructureFileIsNotReadable::class);

        StructureFileReader::read(json_encode($file));
    }

    #[Test]
    public function it_refuses_a_label_longer_than_the_column_holds(): void
    {
        $file = $this->file();
        $file['template']['pages'][0]['sections'][0]['section']['fields'][0]['label'] = str_repeat('a', 300);

        $this->expectException(StructureFileIsNotReadable::class);

        StructureFileReader::read(json_encode($file));
    }

    #[Test]
    public function it_refuses_a_key_that_is_not_a_key(): void
    {
        $file = $this->file();
        $file['template']['pages'][0]['sections'][0]['section']['fields'][0]['key'] = '../../etc/passwd';

        $this->expectException(StructureFileIsNotReadable::class);

        StructureFileReader::read(json_encode($file));
    }

    #[Test]
    public function it_refuses_a_number_written_as_words(): void
    {
        $file = $this->file();
        $file['template']['pages'][0]['sections'][0]['section']['fields'][0]['position'] = 'first';

        $this->expectException(StructureFileIsNotReadable::class);

        StructureFileReader::read(json_encode($file));
    }

    #[Test]
    public function it_refuses_a_kind_of_file_a_field_takes_that_is_not_a_mime_type(): void
    {
        $file = $this->file();
        $file['template']['pages'][0]['sections'][0]['section']['fields'][0]['options'] = [
            'accepted_mime_types' => ['../../../etc/passwd'],
        ];

        $this->expectException(StructureFileIsNotReadable::class);

        StructureFileReader::read(json_encode($file));
    }

    #[Test]
    public function it_refuses_a_template_that_says_it_describes_a_kind_of_record_without_naming_one(): void
    {
        $file = $this->file();
        $file['template']['applies_to'] = 'entity';

        $this->expectException(StructureFileIsNotReadable::class);

        StructureFileReader::read(json_encode($file));
    }

    #[Test]
    public function it_refuses_a_template_holding_no_page(): void
    {
        $file = $this->file();
        $file['template']['pages'] = [];

        $this->expectException(StructureFileIsNotReadable::class);

        StructureFileReader::read(json_encode($file));
    }

    #[Test]
    public function it_never_carries_a_translation_key_over(): void
    {
        $file = $this->file();
        $file['template']['name'] = null;
        $file['template']['name_translation_key'] = 'Templates';

        $read = StructureFileReader::read(json_encode($file));

        $this->assertEquals('Templates', $read['template']['name']);
        $this->assertArrayNotHasKey('name_translation_key', $read['template']);
    }

    #[Test]
    public function it_never_carries_a_validation_blob_over(): void
    {
        $file = $this->file();
        $file['template']['pages'][0]['sections'][0]['section']['fields'][0]['validation'] = ['rules' => 'anything at all'];

        $read = StructureFileReader::read(json_encode($file));

        $this->assertArrayNotHasKey('validation', $read['template']['pages'][0]['sections'][0]['section']['fields'][0]);
    }

    #[Test]
    public function it_never_carries_a_default_value_naming_a_choice_over(): void
    {
        $file = $this->file();
        $file['value_lists'] = [
            'countries' => ['key' => null, 'name' => 'Countries', 'items' => [['key' => null, 'label' => 'France', 'position' => 1, 'is_active' => true]]],
        ];
        $file['template']['pages'][0]['sections'][0]['section']['fields'][0]['type'] = 'select';
        $file['template']['pages'][0]['sections'][0]['section']['fields'][0]['value_list_reference'] = 'countries';
        $file['template']['pages'][0]['sections'][0]['section']['fields'][0]['default_value'] = [4815162342];

        $read = StructureFileReader::read(json_encode($file));

        $this->assertNull($read['template']['pages'][0]['sections'][0]['section']['fields'][0]['default_value']);
    }

    #[Test]
    public function it_refuses_a_default_value_with_a_shape_of_its_own(): void
    {
        $file = $this->file();
        $file['template']['pages'][0]['sections'][0]['section']['fields'][0]['default_value'] = [['nested' => 'thing']];

        $this->expectException(StructureFileIsNotReadable::class);

        StructureFileReader::read(json_encode($file));
    }

    #[Test]
    public function it_refuses_a_glyph_this_version_does_not_have(): void
    {
        $file = $this->file();
        $file['template']['applies_to'] = 'entity';
        $file['template']['entity_type'] = [
            'slug' => 'animal',
            'name_singular' => 'Animal',
            'name_plural' => 'Animals',
            'icon' => 'skull',
        ];

        $this->expectException(StructureFileIsNotReadable::class);

        StructureFileReader::read(json_encode($file));
    }

    #[Test]
    public function it_refuses_a_colour_that_is_not_a_colour(): void
    {
        $file = $this->file();
        $file['template']['applies_to'] = 'entity';
        $file['template']['entity_type'] = [
            'slug' => 'animal',
            'name_singular' => 'Animal',
            'name_plural' => 'Animals',
            'colour' => 'red;}</style><script>',
        ];

        $this->expectException(StructureFileIsNotReadable::class);

        StructureFileReader::read(json_encode($file));
    }

    #[Test]
    public function it_refuses_a_section_file_that_holds_a_template(): void
    {
        $file = $this->file();
        $file['monica']['format'] = 'section';

        $this->expectException(StructureFileIsNotReadable::class);

        StructureFileReader::read(json_encode($file));
    }

    /**
     * The smallest file the reader accepts, which every refusal above breaks in
     * exactly one way.
     *
     * @return array<string, mixed>
     */
    private function file(): array
    {
        return [
            'monica' => ['format' => 'template', 'format_version' => StructureFile::FORMAT_VERSION],
            'exported_at' => '2026-08-13T09:12:04Z',
            'template' => [
                'slug' => 'dunder-mifflin-contact',
                'name' => 'Dunder Mifflin contact',
                'applies_to' => 'contact',
                'pages' => [
                    [
                        'key' => 'general',
                        'label' => 'General',
                        'position' => 1,
                        'sections' => [
                            [
                                'position' => 1,
                                'section' => [
                                    'slug' => 'personal-details',
                                    'name' => 'Personal details',
                                    'fields' => [
                                        ['key' => 'nickname', 'type' => 'text', 'label' => 'Nickname', 'position' => 1],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'value_lists' => [],
        ];
    }

    /**
     * A published template holding one of everything worth reading back: a
     * plain field, a choice pointing at a supplied list, a repeater and a field
     * inside it, and a relation pointing at a kind of record.
     */
    private function publishedTemplate(User $user): Template
    {
        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin contact',
        ]);

        $version = TemplateVersion::factory()->create([
            'account_id' => $user->account_id,
            'template_id' => $template->id,
        ]);

        $page = $version->pages()->firstOrFail();

        $section = Section::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Personal details',
        ]);

        $sectionVersion = SectionVersion::factory()->create([
            'account_id' => $user->account_id,
            'section_id' => $section->id,
        ]);

        $list = ValueList::factory()->supplied(ValueListEnum::PhoneLabels)->create([
            'account_id' => $user->account_id,
        ]);

        ValueListItem::factory()->create([
            'account_id' => $user->account_id,
            'value_list_id' => $list->id,
            'label' => 'Mobile',
        ]);

        $entityType = EntityType::factory()->create(['account_id' => $user->account_id]);

        Field::factory()->create([
            'section_version_id' => $sectionVersion->id,
            'key' => 'nickname',
            'label' => 'Nickname',
            'position' => 1,
        ]);

        Field::factory()->create([
            'section_version_id' => $sectionVersion->id,
            'type' => FieldTypeEnum::Select,
            'key' => 'label',
            'label' => 'Label',
            'position' => 2,
            'value_list_id' => $list->id,
        ]);

        $repeater = Field::factory()->repeater()->create([
            'section_version_id' => $sectionVersion->id,
            'key' => 'phone_numbers',
            'position' => 3,
        ]);

        Field::factory()->create([
            'section_version_id' => $sectionVersion->id,
            'type' => FieldTypeEnum::Relation,
            'relation_target' => RelationTargetEnum::Entity,
            'relation_entity_type_id' => $entityType->id,
            'key' => 'number',
            'label' => 'Number',
            'position' => 1,
            'parent_field_id' => $repeater->id,
        ]);

        TemplateVersionSection::factory()->create([
            'account_id' => $user->account_id,
            'template_version_id' => $version->id,
            'template_version_page_id' => $page->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $sectionVersion->status = VersionStatusEnum::Published;
        $sectionVersion->resolved_value_lists = ValueListSnapshot::of($sectionVersion);
        $sectionVersion->published_at = now();
        $sectionVersion->save();

        $section->published_version_id = $sectionVersion->id;
        $section->save();

        $version->status = VersionStatusEnum::Published;
        $version->published_at = now();
        $version->save();

        $template->published_version_id = $version->id;
        $template->save();

        return $template->fresh();
    }
}
