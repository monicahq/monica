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
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateImportControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_box_a_file_is_pasted_into(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('settings.templateImport.new'))
            ->assertStatus(200)
            ->assertSee('Import a template')
            ->assertSee('Check this file')
            ->assertSee('How it goes');
    }

    #[Test]
    public function it_offers_the_import_from_the_list_of_templates(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('settings.templates.index'))
            ->assertStatus(200)
            ->assertSee(route('settings.templateImport.new'), false);
    }

    #[Test]
    public function it_imports_the_file(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $file = $this->exported($user);

        $response = $this->actingAs($user)->post(route('settings.templateImport.create'), ['file' => $file]);

        $template = Template::query()->where('account_id', $user->account_id)->where('slug', '!=', 'dunder-mifflin-contact')->firstOrFail();

        $response->assertRedirect(route('settings.templateBuilder.index', $template));
        $response->assertSessionHas('status');

        $this->assertEquals('Dunder Mifflin contact', $template->name);
        $this->assertNull($template->structure->published_version_id);
    }

    #[Test]
    public function it_refuses_a_file_that_does_not_read_and_writes_nothing(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $templates = Template::query()->count();

        $response = $this->actingAs($user)->post(route('settings.templateImport.create'), ['file' => 'Michael Scott']);

        $response->assertRedirect(route('settings.templateImport.new'));
        $response->assertSessionHasErrors('file');

        $this->assertEquals($templates, Template::query()->count());
    }

    #[Test]
    public function it_asks_for_something_to_read(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('settings.templateImport.create'), ['file' => ''])
            ->assertSessionHasErrors('file');
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
