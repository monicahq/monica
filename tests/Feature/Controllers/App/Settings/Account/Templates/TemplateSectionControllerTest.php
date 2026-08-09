<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Templates;

use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemplateSectionControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_puts_a_new_section_on_the_template(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);

        $this->actingAs($user)->post(route('settings.templateSections.create', $template), ['name' => 'Health'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('sections', ['account_id' => $user->account_id, 'name' => 'Health']);
        $this->assertDatabaseHas('template_version_sections', ['template_version_id' => $version->id]);
    }

    /**
     * A section is a thing the account keeps, not something owned by the
     * template it was made on, so it arrives with a version of its own.
     */
    #[Test]
    public function it_gives_the_new_section_a_draft_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        TemplateVersion::factory()->create(['template_id' => $template->id]);

        $this->actingAs($user)->post(route('settings.templateSections.create', $template), ['name' => 'Health']);

        $section = Section::query()->where('account_id', $user->account_id)->firstOrFail();

        $this->assertEquals(1, $section->versions()->count());
        $this->assertEquals(1, $section->versions()->first()->usage_count);
    }

    #[Test]
    public function it_refuses_a_section_with_no_name(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        TemplateVersion::factory()->create(['template_id' => $template->id]);

        $this->actingAs($user)->post(route('settings.templateSections.create', $template), [])
            ->assertSessionHasErrors('name');
    }

    #[Test]
    public function it_explains_why_a_published_version_takes_no_new_section(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        TemplateVersion::factory()->published()->create(['template_id' => $template->id]);

        $this->actingAs($user)->post(route('settings.templateSections.create', $template), ['name' => 'Health'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('sections', ['account_id' => $user->account_id, 'name' => 'Health']);
    }

    /**
     * The heading is this template's alone. Renaming here must not reach into
     * another template carrying the same section.
     */
    #[Test]
    public function it_retitles_the_section_on_this_template_only(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$template, $row, $section] = $this->templateCarryingASection($user);

        $this->actingAs($user)->put(route('settings.templateSections.update', [$template, $row]), [
            'title_override' => 'Where they live',
        ])->assertRedirect();

        $this->assertEquals('Where they live', $row->fresh()->title_override);
        $this->assertEquals('Address', $section->fresh()->name);
    }

    #[Test]
    public function it_takes_a_section_off_the_template(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$template, $row, $section] = $this->templateCarryingASection($user);

        $this->actingAs($user)->delete(route('settings.templateSections.destroy', [$template, $row]))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertModelMissing($row);
        $this->assertModelExists($section);
    }

    #[Test]
    public function it_refuses_a_template_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $template = Template::factory()->create();
        TemplateVersion::factory()->create(['template_id' => $template->id]);

        $this->actingAs($michael)->post(route('settings.templateSections.create', $template), ['name' => 'Health'])
            ->assertNotFound();
    }

    #[Test]
    public function it_refuses_an_assembly_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $michael->account_id]);
        $row = TemplateVersionSection::factory()->create();

        $this->actingAs($michael)->delete(route('settings.templateSections.destroy', [$template, $row]))
            ->assertNotFound();
    }

    /**
     * @return array{0: Template, 1: TemplateVersionSection, 2: Section}
     */
    private function templateCarryingASection(User $user): array
    {
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Address']);
        $sectionVersion = SectionVersion::factory()->used(1)->create(['section_id' => $section->id]);
        $row = TemplateVersionSection::factory()->create([
            'template_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        return [$template, $row, $section];
    }
}
