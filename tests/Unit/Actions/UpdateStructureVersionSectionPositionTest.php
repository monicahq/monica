<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateStructureVersionSectionPosition;
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

class UpdateStructureVersionSectionPositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_section_up_the_structure_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Contact']);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Address']);
        $sectionVersion = SectionVersion::factory()->published()->create(['section_id' => $section->id]);
        $first = StructureVersionSection::factory()->create(['structure_version_id' => $version->id, 'position' => 1]);
        $second = StructureVersionSection::factory()->create(['structure_version_id' => $version->id, 'position' => 2]);
        $third = StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
            'position' => 3,
        ]);

        new UpdateStructureVersionSectionPosition(user: $user, row: $third, position: 1)->execute();

        $this->assertEquals(1, $third->fresh()->position);
        $this->assertEquals(2, $first->fresh()->position);
        $this->assertEquals(3, $second->fresh()->position);

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::TemplateSectionPositionUpdate
                && $job->parameters === ['section' => 'Address', 'name' => 'Contact'];
        });
    }

    #[Test]
    public function it_puts_a_section_last_when_the_position_is_past_the_end(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        $first = StructureVersionSection::factory()->create(['structure_version_id' => $version->id, 'position' => 1]);
        $second = StructureVersionSection::factory()->create(['structure_version_id' => $version->id, 'position' => 2]);

        new UpdateStructureVersionSectionPosition(user: $user, row: $first, position: 99)->execute();

        $this->assertEquals(1, $second->fresh()->position);
        $this->assertEquals(2, $first->fresh()->position);
    }

    #[Test]
    public function it_leaves_the_sections_of_another_structure_version_where_they_are(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $mine = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        $other = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        $row = StructureVersionSection::factory()->create(['structure_version_id' => $mine->id, 'position' => 1]);
        $elsewhere = StructureVersionSection::factory()->create(['structure_version_id' => $other->id, 'position' => 1]);

        new UpdateStructureVersionSectionPosition(user: $user, row: $row, position: 5)->execute();

        $this->assertEquals(1, $elsewhere->fresh()->position);
    }

    #[Test]
    public function it_refuses_to_reorder_a_published_structure_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        $row = StructureVersionSection::factory()->create(['structure_version_id' => $version->id, 'position' => 1]);
        $version->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);

        $this->expectException(PublishedVersionIsFrozen::class);

        new UpdateStructureVersionSectionPosition(user: $user, row: $row->fresh(), position: 1)->execute();
    }

    #[Test]
    public function it_refuses_an_assembly_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $row = StructureVersionSection::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdateStructureVersionSectionPosition(user: $michael, row: $row, position: 1)->execute();
    }
}
