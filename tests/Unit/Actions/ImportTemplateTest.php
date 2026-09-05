<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ImportTemplate;
use App\Enums\FieldTypeEnum;
use App\Enums\UserActionEnum;
use App\Enums\ValueListEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\StructureFileIsNotReadable;
use App\Helpers\StructureFile;
use App\Helpers\ValueListSnapshot;
use App\Jobs\LogUserAction;
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
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImportTemplateTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_builds_the_template_the_file_describes(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $file = $this->exported($user);

        $template = new ImportTemplate(user: $user, raw: $file)->execute();

        $this->assertInstanceOf(Template::class, $template);
        $this->assertDatabaseHas('templates', [
            'id' => $template->id,
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin contact',
            'is_system' => false,
            'published_version_id' => null,
        ]);
    }

    #[Test]
    public function it_lands_as_a_draft_holding_frozen_sections(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $template = new ImportTemplate(user: $user, raw: $this->exported($user))->execute();
        $version = $template->versions()->firstOrFail();

        $this->assertEquals(VersionStatusEnum::Draft, $version->status);
        $this->assertEquals(1, $version->version_number);

        foreach ($version->assembly as $row) {
            $this->assertEquals(VersionStatusEnum::Published, $row->sectionVersion->status);
            $this->assertNotNull($row->sectionVersion->resolved_value_lists);
        }
    }

    #[Test]
    public function it_builds_the_pages_the_sections_and_the_fields(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $template = new ImportTemplate(user: $user, raw: $this->exported($user))->execute();
        $version = $template->versions()->firstOrFail();

        $this->assertCount(1, $version->pages);
        $this->assertCount(1, $version->assembly);

        $section = $version->assembly->first()->sectionVersion;

        $this->assertEquals('Personal details', $section->section->name);
        $this->assertCount(4, $section->fields);
        $this->assertEquals('Nickname', $section->fields->firstWhere('key', 'nickname')->label);
    }

    #[Test]
    public function it_puts_a_field_back_inside_the_repeater_it_came_out_of(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $template = new ImportTemplate(user: $user, raw: $this->exported($user))->execute();
        $fields = $template->versions()->firstOrFail()->assembly->first()->sectionVersion->fields;

        $repeater = $fields->firstWhere('key', 'phone_numbers');
        $inside = $fields->firstWhere('key', 'number');

        $this->assertEquals(FieldTypeEnum::Repeater, $repeater->type);
        $this->assertEquals($repeater->id, $inside->parent_field_id);
    }

    #[Test]
    public function it_points_at_the_list_of_choices_the_account_already_curates(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $file = $this->exported($user);
        $lists = ValueList::query()->where('account_id', $user->account_id)->count();

        $template = new ImportTemplate(user: $user, raw: $file)->execute();
        $fields = $template->versions()->firstOrFail()->assembly->first()->sectionVersion->fields;

        $this->assertEquals($lists, ValueList::query()->where('account_id', $user->account_id)->count());
        $this->assertEquals(
            ValueList::query()->where('account_id', $user->account_id)->where('key', ValueListEnum::PhoneLabels->value)->value('id'),
            $fields->firstWhere('key', 'label')->value_list_id,
        );
    }

    #[Test]
    public function it_copies_a_list_of_choices_nobody_here_has(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $file = json_decode($this->exported($user), true);
        $file['value_lists'] = [
            'branches' => [
                'key' => null,
                'name' => 'Branches',
                'items' => [
                    ['key' => null, 'label' => 'Scranton', 'position' => 1, 'is_active' => true],
                    ['key' => null, 'label' => 'Utica', 'position' => 2, 'is_active' => false],
                ],
            ],
        ];

        foreach ($file['template']['pages'][0]['sections'][0]['section']['fields'] as $index => $field) {
            if ($field['key'] === 'label') {
                $file['template']['pages'][0]['sections'][0]['section']['fields'][$index]['value_list_reference'] = 'branches';
            }
        }

        $template = new ImportTemplate(user: $user, raw: json_encode($file))->execute();
        $fields = $template->versions()->firstOrFail()->assembly->first()->sectionVersion->fields;

        $list = ValueList::query()->findOrFail($fields->firstWhere('key', 'label')->value_list_id);

        $this->assertEquals('Branches', $list->name);
        $this->assertNull($list->key);
        $this->assertFalse($list->is_system);
        $this->assertCount(2, $list->items);
        $this->assertFalse($list->items->firstWhere('label', 'Utica')->is_active);
    }

    #[Test]
    public function it_declares_a_kind_of_record_the_account_does_not_have(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $file = json_decode($this->exported($user), true);
        $file['template']['applies_to'] = 'entity';
        $file['template']['entity_type'] = [
            'slug' => 'branch',
            'name_singular' => 'Branch',
            'name_plural' => 'Branches',
            'description' => 'A place with a fax machine',
            'icon' => 'profile',
            'colour' => '#4f8a49',
        ];

        $template = new ImportTemplate(user: $user, raw: json_encode($file))->execute();

        $this->assertDatabaseHas('entity_types', [
            'account_id' => $user->account_id,
            'slug' => 'branch',
            'name_plural' => 'Branches',
        ]);
        $this->assertEquals('branch', $template->entityType->slug);
    }

    #[Test]
    public function it_points_at_a_kind_of_record_the_account_already_has(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $entityType = EntityType::factory()->create(['account_id' => $user->account_id, 'slug' => 'branch']);

        $file = json_decode($this->exported($user), true);
        $file['template']['applies_to'] = 'entity';
        $file['template']['entity_type'] = ['slug' => 'branch', 'name_singular' => 'Branch', 'name_plural' => 'Branches'];

        $template = new ImportTemplate(user: $user, raw: json_encode($file))->execute();

        $this->assertEquals($entityType->id, $template->entity_type_id);
        $this->assertEquals(1, EntityType::query()->where('account_id', $user->account_id)->where('slug', 'branch')->count());
    }

    #[Test]
    public function it_gives_the_copy_a_slug_of_its_own_when_the_wanted_one_is_taken(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $file = $this->exported($user);

        $first = new ImportTemplate(user: $user, raw: $file)->execute();
        $second = new ImportTemplate(user: $user, raw: $file)->execute();

        $this->assertNotEquals($first->slug, $second->slug);
    }

    #[Test]
    public function it_never_writes_a_translation_key_down(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $file = json_decode($this->exported($user), true);
        $file['template']['name'] = null;
        $file['template']['name_translation_key'] = 'Templates';

        $template = new ImportTemplate(user: $user, raw: json_encode($file))->execute();

        $this->assertNull($template->name_translation_key);
        $this->assertEquals('Templates', $template->getRawOriginal('name'));
    }

    #[Test]
    public function it_strips_the_markup_out_of_what_a_file_calls_things(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $file = json_decode($this->exported($user), true);
        $file['template']['name'] = '<script>alert(1)</script>Dunder Mifflin';

        $template = new ImportTemplate(user: $user, raw: json_encode($file))->execute();

        $this->assertStringNotContainsString('<script>', $template->name);
    }

    #[Test]
    public function it_refuses_a_file_holding_one_section(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = $this->publishedTemplate($user);
        $row = $template->publishedVersion->assembly->first();

        $this->expectException(StructureFileIsNotReadable::class);

        new ImportTemplate(user: $user, raw: StructureFile::encode(StructureFile::ofSection($row)))->execute();
    }

    #[Test]
    public function it_writes_nothing_at_all_when_the_file_is_refused(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $templates = Template::query()->count();
        $sections = Section::query()->count();

        try {
            new ImportTemplate(user: $user, raw: 'Michael Scott')->execute();
        } catch (StructureFileIsNotReadable) {
        }

        $this->assertEquals($templates, Template::query()->count());
        $this->assertEquals($sections, Section::query()->count());
    }

    #[Test]
    public function it_logs_the_import(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $template = new ImportTemplate(user: $user, raw: $this->exported($user))->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job) use ($template): bool {
            return $job->action === UserActionEnum::TemplateImported
                && $job->isAbout($template)
                && $job->parameters === ['name' => 'Dunder Mifflin contact'];
        });
    }

    private function exported(User $user): string
    {
        return StructureFile::encode(StructureFile::ofTemplate($this->publishedTemplate($user)));
    }

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
            'key' => 'number',
            'label' => 'Number',
            'position' => 1,
            'parent_field_id' => $repeater->id,
        ]);

        TemplateVersionSection::factory()->create([
            'account_id' => $user->account_id,
            'template_version_id' => $version->id,
            'template_version_page_id' => $version->pages()->firstOrFail()->id,
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
