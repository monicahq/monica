<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Templates;

use App\Enums\VersionStatusEnum;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\Template;
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
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        $this->actingAs($user)->post(route('settings.templateSections.create', $template), ['name' => 'Health'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('sections', ['account_id' => $user->account_id, 'name' => 'Health']);
        $this->assertDatabaseHas('structure_version_sections', ['structure_version_id' => $version->id]);
    }

    #[Test]
    public function it_puts_the_section_on_the_page_the_builder_had_open(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $events = StructureVersionPage::factory()->create(['structure_version_id' => $version->id]);

        $response = $this->actingAs($user)->post(route('settings.templateSections.create', $template), [
            'name' => 'Beet facts',
            'page' => $events->uuid,
        ]);

        $response->assertRedirect(route('settings.templateBuilder.index', ['template' => $template, 'page' => $events->key]));
        $this->assertDatabaseHas('structure_version_sections', ['structure_version_page_id' => $events->id]);
    }

    #[Test]
    public function it_puts_the_section_on_the_first_page_when_none_is_named(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        StructureVersionPage::factory()->create(['structure_version_id' => $version->id]);

        $this->actingAs($user)->post(route('settings.templateSections.create', $template), ['name' => 'Beet facts']);

        $this->assertDatabaseHas('structure_version_sections', [
            'structure_version_page_id' => $version->pages()->firstOrFail()->id,
        ]);
    }

    #[Test]
    public function it_refuses_a_page_that_is_not_on_this_template(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $elsewhere = StructureVersion::factory()->create(['account_id' => $user->account_id])->pages()->sole();

        $this->actingAs($user)->post(route('settings.templateSections.create', $template), [
            'name' => 'Beet facts',
            'page' => $elsewhere->uuid,
        ])->assertSessionHasErrors('page');

        $this->assertDatabaseCount('structure_version_sections', 0);
    }

    #[Test]
    public function it_gives_the_new_section_a_draft_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

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
        StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        $this->actingAs($user)->post(route('settings.templateSections.create', $template), [])
            ->assertSessionHasErrors('name');
    }

    #[Test]
    public function it_explains_why_a_published_version_takes_no_new_section(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);

        $this->actingAs($user)->post(route('settings.templateSections.create', $template), ['name' => 'Health'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('sections', ['account_id' => $user->account_id, 'name' => 'Health']);
    }

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
    public function it_refuses_an_assembly_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $michael->account_id]);
        $row = StructureVersionSection::factory()->create();

        $this->actingAs($michael)->delete(route('settings.templateSections.destroy', [$template, $row]))
            ->assertNotFound();
    }

    /** @return array{0: Template, 1: StructureVersionSection, 2: Section} */
    private function templateCarryingASection(User $user): array
    {
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Address']);
        $sectionVersion = SectionVersion::factory()->used(1)->create(['section_id' => $section->id]);
        $row = StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        return [$template, $row, $section];
    }

    #[Test]
    public function it_puts_the_new_section_on_the_template_wearing_its_mark(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        $this->actingAs($user)->post(route('settings.templateSections.create', $template), [
            'name' => 'Health',
            'icon' => 'health',
            'colour' => '#4f8a49',
        ])->assertRedirect()->assertSessionHas('status');

        $this->assertDatabaseHas('sections', [
            'account_id' => $user->account_id,
            'name' => 'Health',
            'icon' => 'health',
            'colour' => '#4f8a49',
        ]);
    }

    #[Test]
    public function it_creates_the_section_unmarked_when_no_mark_is_asked_for(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        $this->actingAs($user)->post(route('settings.templateSections.create', $template), ['name' => 'Health'])
            ->assertRedirect();

        $this->assertDatabaseHas('sections', ['name' => 'Health', 'icon' => null, 'colour' => null]);
    }

    #[Test]
    public function it_refuses_a_glyph_a_section_may_not_be_marked_with(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        $this->actingAs($user)->post(route('settings.templateSections.create', $template), [
            'name' => 'Health',
            'icon' => 'chevron-down',
        ])->assertSessionHasErrors('icon');

        $this->assertDatabaseMissing('sections', ['name' => 'Health']);
    }

    #[Test]
    public function it_refuses_a_colour_the_set_does_not_offer(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        $this->actingAs($user)->post(route('settings.templateSections.create', $template), [
            'name' => 'Health',
            'colour' => '#ff0000',
        ])->assertSessionHasErrors('colour');

        $this->assertDatabaseMissing('sections', ['name' => 'Health']);
    }

    #[Test]
    public function it_changes_the_mark_on_the_section_itself(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$template, $row, $section] = $this->templateCarryingASection($user);

        $this->actingAs($user)->put(route('settings.templateSections.update', [$template, $row]), [
            'title_override' => 'Where they live',
            'icon' => 'home',
            'colour' => '#2f7f86',
        ])->assertRedirect()->assertSessionHas('status');

        $section->refresh();

        $this->assertEquals('home', $section->icon);
        $this->assertEquals('#2f7f86', $section->colour);
        $this->assertEquals('Where they live', $row->fresh()->title_override);
    }

    #[Test]
    public function it_changes_the_mark_on_every_template_carrying_the_section(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$template, $row, $section] = $this->templateCarryingASection($user);

        $other = Template::factory()->create(['account_id' => $user->account_id]);
        $otherVersion = StructureVersion::factory()->create(['structure_id' => $other->structure->id]);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $otherVersion->id,
            'section_version_id' => $row->section_version_id,
        ]);

        $this->actingAs($user)->put(route('settings.templateSections.update', [$template, $row]), [
            'icon' => 'home',
            'colour' => '#2f7f86',
        ])->assertRedirect();

        $this->assertEquals('home', $section->fresh()->icon);
    }

    #[Test]
    public function it_clears_the_mark_when_the_form_asks_for_none(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$template, $row, $section] = $this->templateCarryingASection($user);
        $section->update(['icon' => 'home', 'colour' => '#2f7f86']);

        $this->actingAs($user)->put(route('settings.templateSections.update', [$template, $row]), [
            'icon' => '',
            'colour' => '',
        ])->assertRedirect();

        $section->refresh();

        $this->assertNull($section->icon);
        $this->assertNull($section->colour);
    }

    #[Test]
    public function it_refuses_a_mark_from_outside_the_set_when_changing_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$template, $row, $section] = $this->templateCarryingASection($user);

        $this->actingAs($user)->put(route('settings.templateSections.update', [$template, $row]), [
            'icon' => 'chevron-down',
        ])->assertSessionHasErrors('icon');

        $this->actingAs($user)->put(route('settings.templateSections.update', [$template, $row]), [
            'colour' => '#ff0000',
        ])->assertSessionHasErrors('colour');

        $this->assertNull($section->fresh()->icon);
    }

    #[Test]
    public function it_explains_why_a_published_version_takes_no_change_of_mark(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        [$template, $row, $section] = $this->templateCarryingASection($user);

        $row->structureVersion->update(['status' => VersionStatusEnum::Published]);

        $this->actingAs($user)->put(route('settings.templateSections.update', [$template, $row]), [
            'icon' => 'home',
            'colour' => '#2f7f86',
        ])->assertRedirect()->assertSessionHas('error');

        $section->refresh();

        $this->assertNull($section->icon);
        $this->assertNull($section->colour);
    }

    #[Test]
    public function it_refuses_a_change_of_mark_reached_through_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $stranger = User::factory()->create();
        [, $row] = $this->templateCarryingASection($stranger);
        $mine = Template::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->put(route('settings.templateSections.update', [$mine, $row]), [
            'icon' => 'home',
        ])->assertNotFound();
    }
}
