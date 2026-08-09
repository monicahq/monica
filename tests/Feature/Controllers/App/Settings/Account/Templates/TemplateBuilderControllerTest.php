<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Templates;

use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionSection;
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
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Health']);
        $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);
        Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => 'Weight']);
        TemplateVersionSection::factory()->create([
            'template_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $this->actingAs($user)->get(route('settings.templateBuilder.index', $template))
            ->assertStatus(200)
            ->assertSee('Health')
            ->assertSee('Weight');
    }

    #[Test]
    public function it_opens_the_field_named_in_the_query_string(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $sectionVersion = SectionVersion::factory()->create(['account_id' => $user->account_id]);
        $field = Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => 'Weight']);
        TemplateVersionSection::factory()->create([
            'template_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $this->actingAs($user)->get(route('settings.templateBuilder.index', ['template' => $template, 'field' => $field->id]))
            ->assertStatus(200)
            ->assertSee(__('Delete the field'));
    }

    #[Test]
    public function it_says_a_template_with_no_field_open_has_none(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        TemplateVersion::factory()->create(['template_id' => $template->id]);

        $this->actingAs($user)->get(route('settings.templateBuilder.index', $template))
            ->assertStatus(200)
            ->assertSee(__('Pick a field on the left to edit it. What you change applies to this draft only.'));
    }

    /**
     * A template whose only version was thrown away still has to open, with the
     * way forward on the screen rather than a crash.
     */
    #[Test]
    public function it_opens_a_template_that_has_no_version_at_all(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('settings.templateBuilder.index', $template))
            ->assertStatus(200)
            ->assertSee(__('Start a draft'));
    }

    #[Test]
    public function it_does_not_show_the_template_of_another_account(): void
    {
        $michael = User::factory()->create();
        $template = Template::factory()->create();

        $this->actingAs($michael)->get(route('settings.templateBuilder.index', $template))->assertNotFound();
    }

    #[Test]
    public function it_turns_a_visitor_away(): void
    {
        $template = Template::factory()->create();

        $this->get(route('settings.templateBuilder.index', $template))->assertRedirect(route('auth.login.new'));
    }
}
