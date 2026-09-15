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

class SectionShareControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_two_ways_a_section_leaves(): void
    {
        $user = User::factory()->create();
        $row = $this->publishedRow($user);

        $this->actingAs($user)->get(route('settings.sectionShare.index', [$row->structureVersion->structure->owner, $row->id]))
            ->assertStatus(200)
            ->assertSee(__('Export the section as a file'))
            ->assertSee(__('Publish the section'))
            ->assertSee('personal-details.json');
    }

    #[Test]
    public function it_lists_the_fields_that_are_going_out(): void
    {
        $user = User::factory()->create();
        $row = $this->publishedRow($user);

        $this->actingAs($user)->get(route('settings.sectionShare.index', [$row->structureVersion->structure->owner, $row->id]))
            ->assertSee(__('What is going out'))
            ->assertSee('Nickname');
    }

    #[Test]
    public function it_does_not_show_a_section_on_another_template(): void
    {
        $user = User::factory()->create();
        $row = $this->publishedRow($user);
        $other = Template::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->get(route('settings.sectionShare.index', [$other, $row->id]))
            ->assertNotFound();
    }

    #[Test]
    public function it_does_not_show_a_section_in_another_account(): void
    {
        $row = $this->publishedRow(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->get(route('settings.sectionShare.index', [$row->structureVersion->structure->owner, $row->id]))
            ->assertNotFound();
    }

    #[Test]
    public function it_is_not_offered_for_a_section_still_being_written(): void
    {
        $user = User::factory()->create();
        $row = $this->publishedRow($user);

        $row->sectionVersion->status = VersionStatusEnum::Draft;
        $row->sectionVersion->save();

        $this->actingAs($user)->get(route('settings.sectionShare.index', [$row->structureVersion->structure->owner, $row->id]))
            ->assertNotFound();
    }

    private function publishedRow(User $user): StructureVersionSection
    {
        $template = Template::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin contact',
            'slug' => 'dunder-mifflin-contact',
        ]);

        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        $section = Section::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Personal details',
            'slug' => 'personal-details',
        ]);

        $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);

        Field::factory()->create(['section_version_id' => $sectionVersion->id, 'label' => 'Nickname']);

        $row = StructureVersionSection::factory()->create([
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

        return $row->fresh();
    }
}
