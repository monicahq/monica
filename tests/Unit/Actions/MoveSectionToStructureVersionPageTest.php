<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\MoveSectionToStructureVersionPage;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Jobs\LogUserAction;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MoveSectionToStructureVersionPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_section_to_another_page(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        $general = $structureVersion->pages()->firstOrFail();
        $health = StructureVersionPage::factory()->create(['structure_version_id' => $structureVersion->id]);
        $row = StructureVersionSection::factory()->create([
            'structure_version_id' => $structureVersion->id,
            'structure_version_page_id' => $general->id,
        ]);

        $result = new MoveSectionToStructureVersionPage(
            user: $user,
            row: $row,
            page: $health,
        )->execute();

        $this->assertInstanceOf(StructureVersionSection::class, $result);
        $this->assertEquals($health->id, $row->refresh()->structure_version_page_id);
        $this->assertEquals(1, $row->position);
    }

    #[Test]
    public function it_puts_the_section_last_on_the_page_it_lands_on(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        $general = $structureVersion->pages()->firstOrFail();
        $health = StructureVersionPage::factory()->create(['structure_version_id' => $structureVersion->id]);

        StructureVersionSection::factory()->create([
            'structure_version_id' => $structureVersion->id,
            'structure_version_page_id' => $health->id,
            'position' => 1,
        ]);
        $row = StructureVersionSection::factory()->create([
            'structure_version_id' => $structureVersion->id,
            'structure_version_page_id' => $general->id,
        ]);

        new MoveSectionToStructureVersionPage(user: $user, row: $row, page: $health)->execute();

        $this->assertEquals(2, $row->refresh()->position);
    }

    #[Test]
    public function it_numbers_the_page_it_left_from_one_again(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        $general = $structureVersion->pages()->firstOrFail();
        $health = StructureVersionPage::factory()->create(['structure_version_id' => $structureVersion->id]);

        $leaving = StructureVersionSection::factory()->create([
            'structure_version_id' => $structureVersion->id,
            'structure_version_page_id' => $general->id,
            'position' => 1,
        ]);
        $staying = StructureVersionSection::factory()->create([
            'structure_version_id' => $structureVersion->id,
            'structure_version_page_id' => $general->id,
            'position' => 2,
        ]);

        new MoveSectionToStructureVersionPage(user: $user, row: $leaving, page: $health)->execute();

        $this->assertEquals(1, $staying->refresh()->position);
    }

    #[Test]
    public function it_leaves_a_section_already_on_the_page_where_it_is(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        $general = $structureVersion->pages()->firstOrFail();
        $row = StructureVersionSection::factory()->create([
            'structure_version_id' => $structureVersion->id,
            'structure_version_page_id' => $general->id,
            'position' => 4,
        ]);

        new MoveSectionToStructureVersionPage(user: $user, row: $row, page: $general)->execute();

        $this->assertEquals(4, $row->refresh()->position);
    }

    #[Test]
    public function it_refuses_a_page_on_another_structure_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        $row = StructureVersionSection::factory()->create(['structure_version_id' => $structureVersion->id]);

        $this->expectException(ModelNotFoundException::class);

        new MoveSectionToStructureVersionPage(
            user: $user,
            row: $row,
            page: $elsewhere->pages()->firstOrFail(),
        )->execute();
    }

    #[Test]
    public function it_refuses_an_assembly_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $row = StructureVersionSection::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new MoveSectionToStructureVersionPage(
            user: $user,
            row: $row,
            page: $row->structureVersion->pages()->firstOrFail(),
        )->execute();
    }

    #[Test]
    public function it_refuses_to_move_a_section_on_a_published_structure_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        $general = $structureVersion->pages()->firstOrFail();
        $health = StructureVersionPage::factory()->create(['structure_version_id' => $structureVersion->id]);
        $row = StructureVersionSection::factory()->create([
            'structure_version_id' => $structureVersion->id,
            'structure_version_page_id' => $general->id,
        ]);
        $structureVersion->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);
        $row->refresh();

        $this->expectException(PublishedVersionIsFrozen::class);

        new MoveSectionToStructureVersionPage(user: $user, row: $row, page: $health)->execute();
    }

    #[Test]
    public function it_logs_the_move(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        $general = $structureVersion->pages()->firstOrFail();
        $health = StructureVersionPage::factory()->create([
            'structure_version_id' => $structureVersion->id,
            'label' => 'Health',
        ]);
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Beet facts']);
        $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);
        $row = StructureVersionSection::factory()->create([
            'structure_version_id' => $structureVersion->id,
            'structure_version_page_id' => $general->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        new MoveSectionToStructureVersionPage(user: $user, row: $row, page: $health)->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::SectionMovedToPage
                && $job->parameters === ['section' => 'Beet facts', 'label' => 'Health'],
        );
    }
}
