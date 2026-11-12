<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Templates;

use App\Enums\FieldTypeEnum;
use App\Enums\VersionStatusEnum;
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

class TemplateBuilderControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_sections_and_the_fields(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Paper stock']);
        $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);
        Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => 'Weight']);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $this->actingAs($user)->get(route('settings.templateBuilder.index', $template))
            ->assertStatus(200)
            ->assertSee('Paper stock')
            ->assertSee('Weight');
    }

    #[Test]
    public function it_asks_a_repeater_which_end_it_collects_at_and_asks_nothing_else(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $sectionVersion = SectionVersion::factory()->create(['account_id' => $user->account_id]);
        $repeater = Field::factory()->create([
            'section_version_id' => $sectionVersion->id,
            'label' => 'Notes',
            'type' => FieldTypeEnum::Repeater,
            'is_repeatable' => true,
        ]);
        $plain = Field::factory()->create([
            'section_version_id' => $sectionVersion->id,
            'label' => 'Weight',
            'type' => FieldTypeEnum::Number,
        ]);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $onTheRepeater = $this->actingAs($user)->get(route('settings.templateBuilder.field', [
            'template' => $template, 'page' => $version->pages->first()->key, 'field' => $repeater->id,
        ]));

        $onTheRepeater->assertStatus(200);
        $onTheRepeater->assertSee('New repetitions');
        $onTheRepeater->assertSee('New ones at the top');
        $onTheRepeater->assertSee('New ones at the bottom');

        $onThePlainField = $this->actingAs($user)->get(route('settings.templateBuilder.field', [
            'template' => $template, 'page' => $version->pages->first()->key, 'field' => $plain->id,
        ]));

        $onThePlainField->assertStatus(200);
        $onThePlainField->assertDontSee('New repetitions');
    }

    #[Test]
    public function it_names_the_way_out_after_the_export_while_the_marketplace_is_switched_off(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create([
            'structure_id' => $template->structure->id,
            'status' => VersionStatusEnum::Published,
            'published_at' => now(),
        ]);
        $template->structure->update(['published_version_id' => $version->id]);

        $this->actingAs($user)->get(route('settings.templateBuilder.index', $template))
            ->assertSee(__('Export the template'))
            ->assertSee(route('settings.templateShare.index', $template), escape: false)
            ->assertDontSee(__('Share on marketplace'));
    }

    #[Test]
    public function it_reopens_the_card_that_asks_for_a_page_name_when_it_was_rejected(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        $this->actingAs($user)
            ->from(route('settings.templateBuilder.index', $template))
            ->post(route('settings.templatePages.create', $template), ['label' => '']);

        $this->actingAs($user)->get(route('settings.templateBuilder.index', $template))
            ->assertStatus(200)
            ->assertSee('x-data="{ open: true }"', false);
    }

    #[Test]
    public function it_opens_a_template_that_has_no_version_at_all(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('settings.templateBuilder.index', $template))
            ->assertStatus(200)
            ->assertSee(__('Create a new version'));
    }
}
