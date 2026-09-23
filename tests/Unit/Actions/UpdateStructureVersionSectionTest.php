<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateStructureVersionSection;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Jobs\LogUserAction;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateStructureVersionSectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_how_a_section_reads_on_one_structure_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Contact']);
        $structureVersion = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Address']);
        $sectionVersion = SectionVersion::factory()->published()->create(['section_id' => $section->id]);
        $row = StructureVersionSection::factory()->create([
            'structure_version_id' => $structureVersion->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        new UpdateStructureVersionSection(
            user: $user,
            row: $row,
            titleOverride: 'Where they live',
            isCollapsedByDefault: true,
        )->execute();

        $row->refresh();

        $this->assertEquals('Where they live', $row->title_override);
        $this->assertTrue($row->is_collapsed_by_default);

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::TemplateSectionUpdate
                && $job->parameters === ['section' => 'Address', 'name' => 'Contact'];
        });
    }

    #[Test]
    public function it_leaves_the_name_of_the_section_itself_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Address']);
        $sectionVersion = SectionVersion::factory()->published()->create(['section_id' => $section->id]);
        $row = StructureVersionSection::factory()->create([
            'account_id' => $user->account_id,
            'section_version_id' => $sectionVersion->id,
        ]);

        new UpdateStructureVersionSection(user: $user, row: $row, titleOverride: 'Where they live')->execute();

        $this->assertEquals('Address', $section->fresh()->name);
    }

    #[Test]
    public function it_puts_the_sections_own_name_back_when_the_override_is_taken_away(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $row = StructureVersionSection::factory()->create([
            'account_id' => $user->account_id,
            'title_override' => 'Where they live',
        ]);

        new UpdateStructureVersionSection(user: $user, row: $row)->execute();

        $this->assertNull($row->fresh()->title_override);
    }

    #[Test]
    public function it_refuses_to_change_anything_on_a_published_structure_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        $row = StructureVersionSection::factory()->create([
            'account_id' => $user->account_id,
            'structure_version_id' => $structureVersion->id,
        ]);
        $structureVersion->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);

        $this->expectException(PublishedVersionIsFrozen::class);

        new UpdateStructureVersionSection(user: $user, row: $row->fresh(), titleOverride: 'Nope')->execute();
    }

    #[Test]
    public function it_refuses_an_assembly_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $row = StructureVersionSection::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdateStructureVersionSection(user: $michael, row: $row)->execute();
    }
}
