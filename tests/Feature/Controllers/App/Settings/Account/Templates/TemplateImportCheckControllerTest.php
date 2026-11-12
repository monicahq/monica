<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Templates;

use App\Enums\FieldTypeEnum;
use App\Enums\VersionStatusEnum;
use App\Helpers\StructureFile;
use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateImportCheckControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_says_what_is_in_a_file_that_reads(): void
    {
        $user = User::factory()->create();
        $file = StructureFile::encode(StructureFile::ofTemplate($this->publishedTemplate($user)));

        $this->actingAs($user)->post(route('settings.templateImportCheck.create'), ['file' => $file])
            ->assertStatus(200)
            ->assertSee('This file can be imported')
            ->assertSee('A whole template')
            ->assertSee('Dunder Mifflin contact')
            ->assertSee('1 page, 1 section, 1 field');
    }

    #[Test]
    public function it_writes_nothing_at_all(): void
    {
        $user = User::factory()->create();
        $file = StructureFile::encode(StructureFile::ofTemplate($this->publishedTemplate($user)));

        $templates = Template::query()->count();
        $sections = Section::query()->count();

        $this->actingAs($user)->post(route('settings.templateImportCheck.create'), ['file' => $file])->assertStatus(200);

        $this->assertEquals($templates, Template::query()->count());
        $this->assertEquals($sections, Section::query()->count());
    }

    #[Test]
    public function it_says_which_part_of_a_file_is_wrong(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('settings.templateImportCheck.create'), ['file' => '{"monica": {"format": "csv", "format_version": 1}}'])
            ->assertStatus(200)
            ->assertSee('This file cannot be imported')
            ->assertSee('Nothing was imported. Fix the file and check it again.');
    }

    #[Test]
    public function it_never_renders_what_was_pasted_as_markup(): void
    {
        $user = User::factory()->create();
        $file = json_encode([
            'monica' => ['format' => 'template', 'format_version' => StructureFile::FORMAT_VERSION],
            'template' => [
                'name' => '<script>alert(1)</script>Dunder Mifflin',
                'applies_to' => 'contact',
                'pages' => [[
                    'key' => 'general',
                    'label' => 'General',
                    'position' => 1,
                    'sections' => [[
                        'position' => 1,
                        'section' => [
                            'name' => 'Personal details',
                            'fields' => [['key' => 'nickname', 'type' => 'text', 'label' => 'Nickname', 'position' => 1]],
                        ],
                    ]],
                ]],
            ],
            'value_lists' => [],
        ]);

        $this->actingAs($user)->post(route('settings.templateImportCheck.create'), ['file' => $file])
            ->assertStatus(200)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    private function publishedTemplate(User $user): Template
    {
        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin contact',
            'slug' => 'dunder-mifflin-contact',
        ]);

        $version = StructureVersion::factory()->create([
            'account_id' => $user->account_id,
            'structure_id' => $template->structure->id,
        ]);

        $section = Section::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Personal details',
        ]);

        $sectionVersion = SectionVersion::factory()->create([
            'account_id' => $user->account_id,
            'section_id' => $section->id,
        ]);

        Field::factory()->create([
            'section_version_id' => $sectionVersion->id,
            'type' => FieldTypeEnum::Text,
            'key' => 'nickname',
            'label' => 'Nickname',
        ]);

        StructureVersionSection::factory()->create([
            'account_id' => $user->account_id,
            'structure_version_id' => $version->id,
            'structure_version_page_id' => $version->pages()->firstOrFail()->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $sectionVersion->status = VersionStatusEnum::Published;
        $sectionVersion->published_at = now();
        $sectionVersion->save();

        $section->published_version_id = $sectionVersion->id;
        $section->save();

        $version->status = VersionStatusEnum::Published;
        $version->published_at = now();
        $version->save();

        $template->structure->update(['published_version_id' => $version->id]);

        return $template->fresh();
    }
}
