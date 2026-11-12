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
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\Template;
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
            ->assertStatus(200);
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
            ->assertStatus(200);
    }

    private function exportedSection(User $user): string
    {
        $template = $this->publishedTemplate($user);

        return StructureFile::encode(StructureFile::ofSection($template->publishedVersion->assembly->first()));
    }

    /** @return array{Template, StructureVersionPage} */
    private function draft(User $user): array
    {
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        $version = StructureVersion::factory()->create([
            'account_id' => $user->account_id,
            'structure_id' => $template->structure->id,
        ]);

        return [$template, $version->pages()->firstOrFail()];
    }

    private function publishedTemplate(User $user): Template
    {
        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin contact',
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
