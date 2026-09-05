<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Templates;

use App\Enums\VersionStatusEnum;
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

class TemplateVersionHistoryControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_every_version_newest_first(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Basic contact']);
        $first = TemplateVersion::factory()->published()->create(['template_id' => $template->id, 'version_number' => 1]);
        TemplateVersion::factory()->create([
            'template_id' => $template->id,
            'version_number' => 2,
            'previous_version_id' => $first->id,
        ]);

        $response = $this->actingAs($user)->get(route('settings.templateVersions.index', $template));

        $response->assertStatus(200);
        $response->assertSee('Versions of Basic contact');
        $response->assertSeeInOrder(['v2', 'v1']);
        $response->assertSee('Draft');
        $response->assertSee('Published');
        $response->assertSee('based on v1');
        $response->assertSee('the first version');
    }

    #[Test]
    public function it_shows_the_pages_the_sections_and_the_fields_of_a_version(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Personal details']);
        $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);
        Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => 'Birthday']);
        TemplateVersionSection::factory()->create([
            'template_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $response = $this->actingAs($user)->get(route('settings.templateVersions.index', $template));

        $response->assertStatus(200);
        $response->assertSee('Page 1');
        $response->assertSee('Personal details');
        $response->assertSee('Birthday');
        $response->assertSee('1 section · 1 field', escape: false);
    }

    #[Test]
    public function it_marks_a_section_that_is_new_in_a_version(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        $first = TemplateVersion::factory()->create(['template_id' => $template->id, 'version_number' => 1]);
        TemplateVersionSection::factory()->create(['template_version_id' => $first->id]);
        $first->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);

        $second = TemplateVersion::factory()->create([
            'template_id' => $template->id,
            'version_number' => 2,
            'previous_version_id' => $first->id,
        ]);
        TemplateVersionSection::factory()->create(['template_version_id' => $second->id]);

        $response = $this->actingAs($user)->get(route('settings.templateVersions.index', $template));

        $response->assertStatus(200);
        $response->assertSee('New in this version');
    }

    #[Test]
    public function it_says_when_a_template_has_no_version_yet(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        $response = $this->actingAs($user)->get(route('settings.templateVersions.index', $template));

        $response->assertStatus(200);
        $response->assertSee('This template has no version yet');
    }

    #[Test]
    public function it_offers_the_way_back_to_the_builder(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Basic contact']);
        TemplateVersion::factory()->create(['template_id' => $template->id]);

        $response = $this->actingAs($user)->get(route('settings.templateVersions.index', $template));

        $response->assertStatus(200);
        $response->assertSee(route('settings.templateBuilder.index', $template), escape: false);
        $response->assertSee('Back to Basic contact');
    }

    #[Test]
    public function it_changes_nothing_it_shows(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);
        $before = $version->updated_at;

        $this->actingAs($user)->get(route('settings.templateVersions.index', $template))->assertStatus(200);

        $this->assertDatabaseHas('template_versions', [
            'id' => $version->id,
            'status' => VersionStatusEnum::Published->value,
            'updated_at' => $before,
        ]);
    }

    #[Test]
    public function it_cannot_be_reached_for_a_template_in_another_account(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create();
        TemplateVersion::factory()->create(['template_id' => $template->id]);

        $this->actingAs($user)->get(route('settings.templateVersions.index', $template))->assertStatus(404);
    }

    #[Test]
    public function a_visitor_cannot_reach_it(): void
    {
        $template = Template::factory()->create();

        $this->get(route('settings.templateVersions.index', $template))->assertRedirect(route('auth.login.new'));
    }
}
