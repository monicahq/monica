<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Templates;

use App\Enums\FieldTypeEnum;
use App\Enums\VersionStatusEnum;
use App\Helpers\StructureFile;
use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SectionImportCheckControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_says_what_is_in_a_section_file_that_reads(): void
    {
        $user = User::factory()->create();
        $file = $this->exportedSection($user);
        [$template, $page] = $this->draft($user);

        $this->actingAs($user)->post(route('settings.sectionImportCheck.create', [$template, $page]), ['file' => $file])
            ->assertStatus(200)
            ->assertSee('This file can be imported')
            ->assertSee('One section')
            ->assertSee('Personal details');
    }

    #[Test]
    public function it_writes_nothing_at_all(): void
    {
        $user = User::factory()->create();
        $file = $this->exportedSection($user);
        [$template, $page] = $this->draft($user);

        $sections = Section::query()->count();

        $this->actingAs($user)->post(route('settings.sectionImportCheck.create', [$template, $page]), ['file' => $file])->assertStatus(200);

        $this->assertEquals($sections, Section::query()->count());
    }

    #[Test]
    public function it_refuses_a_file_holding_a_whole_template(): void
    {
        $user = User::factory()->create();
        $file = StructureFile::encode(StructureFile::ofTemplate($this->publishedTemplate($user)));
        [$template, $page] = $this->draft($user);

        $this->actingAs($user)->post(route('settings.sectionImportCheck.create', [$template, $page]), ['file' => $file])
            ->assertStatus(200)
            ->assertSee('This file cannot be imported');
    }

    #[Test]
    public function it_refuses_a_published_version(): void
    {
        $user = User::factory()->create();
        $template = $this->publishedTemplate($user);
        $page = $template->publishedVersion->pages()->firstOrFail();

        $this->actingAs($user)->post(route('settings.sectionImportCheck.create', [$template, $page]), ['file' => '{}'])
            ->assertNotFound();
    }

    private function exportedSection(User $user): string
    {
        $template = $this->publishedTemplate($user);

        return StructureFile::encode(StructureFile::ofSection($template->publishedVersion->assembly->first()));
    }

    /** @return array{Template, TemplateVersionPage} */
    private function draft(User $user): array
    {
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        $version = TemplateVersion::factory()->create([
            'account_id' => $user->account_id,
            'template_id' => $template->id,
        ]);

        return [$template, $version->pages()->firstOrFail()];
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

        Field::factory()->create([
            'section_version_id' => $sectionVersion->id,
            'type' => FieldTypeEnum::Text,
            'key' => 'nickname',
            'label' => 'Nickname',
        ]);

        TemplateVersionSection::factory()->create([
            'account_id' => $user->account_id,
            'template_version_id' => $version->id,
            'template_version_page_id' => $version->pages()->firstOrFail()->id,
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

        $template->published_version_id = $version->id;
        $template->save();

        return $template->fresh();
    }
}
