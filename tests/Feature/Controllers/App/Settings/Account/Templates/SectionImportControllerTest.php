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
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SectionImportControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_box_a_section_file_is_pasted_into(): void
    {
        $user = User::factory()->create();
        [$template, $page] = $this->draft($user);

        $this->actingAs($user)->get(route('settings.sectionImport.new', [$template, $page]))
            ->assertStatus(200)
            ->assertSee('Import a section')
            ->assertSee('Check this file');
    }

    #[Test]
    public function it_offers_the_import_from_the_builder_of_a_draft(): void
    {
        $user = User::factory()->create();
        [$template, $page] = $this->draft($user);

        $this->actingAs($user)->get(route('settings.templateBuilder.index', $template))
            ->assertStatus(200)
            ->assertSee(route('settings.sectionImport.new', [$template, $page]), false);
    }

    #[Test]
    public function it_does_not_offer_the_import_on_a_published_version(): void
    {
        $user = User::factory()->create();
        $template = $this->publishedTemplate($user);

        $this->actingAs($user)->get(route('settings.templateBuilder.index', $template))
            ->assertStatus(200)
            ->assertDontSee('Import a section');
    }

    #[Test]
    public function it_imports_the_section_onto_the_page(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $file = $this->exportedSection($user);
        [$template, $page] = $this->draft($user);

        $response = $this->actingAs($user)->post(route('settings.sectionImport.create', [$template, $page]), ['file' => $file]);

        $response->assertRedirect(route('settings.templateBuilder.index', ['template' => $template, 'page' => $page->key]));
        $response->assertSessionHas('status');

        $this->assertEquals(1, TemplateVersionSection::query()->where('template_version_page_id', $page->id)->count());
    }

    #[Test]
    public function it_imports_the_section_wearing_the_mark_it_was_exported_with(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $file = $this->exportedSection($user);
        [$template, $page] = $this->draft($user);

        $this->actingAs($user)->post(route('settings.sectionImport.create', [$template, $page]), ['file' => $file])
            ->assertRedirect();

        /* Two sections named the same now, the one exported from and the copy.
           The copy is the one the draft carries. */
        $copy = TemplateVersionSection::query()
            ->where('template_version_page_id', $page->id)
            ->sole()
            ->sectionVersion
            ->section;

        $this->assertEquals('health', $copy->icon);
        $this->assertEquals('#4f8a49', $copy->colour);
    }

    #[Test]
    public function it_imports_a_section_carrying_no_mark_onto_the_defaults(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $file = $this->exportedSection($user, marked: false);
        [$template, $page] = $this->draft($user);

        $this->actingAs($user)->post(route('settings.sectionImport.create', [$template, $page]), ['file' => $file])
            ->assertRedirect();

        $copy = TemplateVersionSection::query()
            ->where('template_version_page_id', $page->id)
            ->sole()
            ->sectionVersion
            ->section;

        $this->assertNull($copy->icon);
        $this->assertNull($copy->colour);
    }

    #[Test]
    public function it_refuses_a_section_file_asking_for_a_mark_this_version_does_not_offer(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $file = json_decode($this->exportedSection($user), true);
        $file['section']['icon'] = 'chevron-down';
        [$template, $page] = $this->draft($user);

        $this->actingAs($user)->post(route('settings.sectionImport.create', [$template, $page]), [
            'file' => json_encode($file),
        ])->assertRedirect()->assertSessionHasErrors('file');

        $this->assertEquals(0, TemplateVersionSection::query()->where('template_version_page_id', $page->id)->count());
    }

    #[Test]
    public function it_refuses_a_file_that_does_not_read_and_writes_nothing(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$template, $page] = $this->draft($user);
        $sections = Section::query()->count();

        $response = $this->actingAs($user)->post(route('settings.sectionImport.create', [$template, $page]), ['file' => 'Michael Scott']);

        $response->assertRedirect(route('settings.sectionImport.new', [$template, $page]));
        $response->assertSessionHasErrors('file');

        $this->assertEquals($sections, Section::query()->count());
    }

    #[Test]
    public function it_refuses_a_published_version(): void
    {
        $user = User::factory()->create();
        $template = $this->publishedTemplate($user);
        $page = $template->publishedVersion->pages()->firstOrFail();

        $this->actingAs($user)->get(route('settings.sectionImport.new', [$template, $page]))->assertNotFound();
    }

    #[Test]
    public function it_refuses_a_page_that_is_not_on_this_template(): void
    {
        $user = User::factory()->create();
        [$template] = $this->draft($user);
        [, $elsewhere] = $this->draft($user);

        $this->actingAs($user)->get(route('settings.sectionImport.new', [$template, $elsewhere]))->assertNotFound();
    }

    #[Test]
    public function it_refuses_a_template_in_another_account(): void
    {
        $user = User::factory()->create();
        [$template, $page] = $this->draft(User::factory()->create());

        $this->actingAs($user)->get(route('settings.sectionImport.new', [$template, $page]))->assertNotFound();
    }

    private function exportedSection(User $user, bool $marked = true): string
    {
        $template = $this->publishedTemplate($user, $marked);

        return StructureFile::encode(StructureFile::ofSection($template->publishedVersion->assembly->first()));
    }

    /**
     * @return array{Template, TemplateVersionPage}
     */
    private function draft(User $user): array
    {
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        $version = TemplateVersion::factory()->create([
            'account_id' => $user->account_id,
            'template_id' => $template->id,
        ]);

        return [$template, $version->pages()->firstOrFail()];
    }

    private function publishedTemplate(User $user, bool $marked = true): Template
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
            'icon' => $marked ? 'health' : null,
            'colour' => $marked ? '#4f8a49' : null,
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
