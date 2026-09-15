<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Settings\Account\Templates;

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

class TemplateShareControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_two_ways_a_template_leaves(): void
    {
        $user = User::factory()->create();
        $template = $this->publishedTemplate($user);

        $this->actingAs($user)->get(route('settings.templateShare.index', $template))
            ->assertStatus(200)
            ->assertSee(__('Export a file'))
            ->assertSee(__('Publish to the marketplace'))
            ->assertSee('dunder-mifflin-contact-v1.json');
    }

    #[Test]
    public function it_says_what_never_leaves(): void
    {
        $user = User::factory()->create();
        $template = $this->publishedTemplate($user);

        $this->actingAs($user)->get(route('settings.templateShare.index', $template))
            ->assertSee(__('What never leaves, either way'));
    }

    #[Test]
    public function it_is_not_offered_for_a_template_nobody_has_published(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('settings.templateShare.index', $template))
            ->assertNotFound();
    }

    #[Test]
    public function it_does_not_show_a_template_in_another_account(): void
    {
        $template = $this->publishedTemplate(User::factory()->create());

        $this->actingAs(User::factory()->create())->get(route('settings.templateShare.index', $template))
            ->assertNotFound();
    }

    private function publishedTemplate(User $user): Template
    {
        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin contact',
            'slug' => 'dunder-mifflin-contact',
        ]);

        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Personal details']);
        $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);

        Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => 'Nickname']);

        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
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
