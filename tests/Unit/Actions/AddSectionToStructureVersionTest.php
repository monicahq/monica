<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\AddSectionToStructureVersion;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Exceptions\SectionIsAlreadyInTheStructureVersion;
use App\Jobs\LogUserAction;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AddSectionToStructureVersionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_puts_a_section_on_a_structure_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        $sectionVersion = SectionVersion::factory()->published()->create(['account_id' => $user->account_id]);

        $row = new AddSectionToStructureVersion(
            user: $user,
            structureVersion: $structureVersion,
            sectionVersion: $sectionVersion,
            titleOverride: 'Where they live',
            isCollapsedByDefault: true,
        )->execute();

        $this->assertInstanceOf(StructureVersionSection::class, $row);

        $this->assertDatabaseHas('structure_version_sections', [
            'account_id' => $user->account_id,
            'structure_version_id' => $structureVersion->id,
            'section_version_id' => $sectionVersion->id,
            'position' => 1,
            'title_override' => 'Where they live',
            'is_collapsed_by_default' => true,
        ]);
    }

    #[Test]
    public function it_puts_a_section_on_the_first_page_when_nobody_says_which(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        $sectionVersion = SectionVersion::factory()->published()->create(['account_id' => $user->account_id]);

        $row = new AddSectionToStructureVersion(
            user: $user,
            structureVersion: $structureVersion,
            sectionVersion: $sectionVersion,
        )->execute();

        $this->assertEquals($structureVersion->pages()->firstOrFail()->id, $row->structure_version_page_id);
    }

    #[Test]
    public function it_puts_a_section_on_the_page_it_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        $health = StructureVersionPage::factory()->create(['structure_version_id' => $structureVersion->id]);
        $sectionVersion = SectionVersion::factory()->published()->create(['account_id' => $user->account_id]);

        $row = new AddSectionToStructureVersion(
            user: $user,
            structureVersion: $structureVersion,
            sectionVersion: $sectionVersion,
            structureVersionPage: $health,
        )->execute();

        $this->assertEquals($health->id, $row->structure_version_page_id);
        $this->assertEquals(1, $row->position);
    }

    #[Test]
    public function it_counts_the_position_within_the_page_rather_than_the_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        $health = StructureVersionPage::factory()->create(['structure_version_id' => $structureVersion->id]);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $structureVersion->id,
            'structure_version_page_id' => $structureVersion->pages()->firstOrFail()->id,
            'position' => 4,
        ]);
        $sectionVersion = SectionVersion::factory()->published()->create(['account_id' => $user->account_id]);

        $row = new AddSectionToStructureVersion(
            user: $user,
            structureVersion: $structureVersion,
            sectionVersion: $sectionVersion,
            structureVersionPage: $health,
        )->execute();

        $this->assertEquals(1, $row->position);
    }

    #[Test]
    public function it_refuses_a_page_on_another_structure_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        $sectionVersion = SectionVersion::factory()->published()->create(['account_id' => $user->account_id]);

        $this->expectException(ModelNotFoundException::class);

        new AddSectionToStructureVersion(
            user: $user,
            structureVersion: $structureVersion,
            sectionVersion: $sectionVersion,
            structureVersionPage: $elsewhere->pages()->firstOrFail(),
        )->execute();
    }

    #[Test]
    public function it_puts_a_new_section_at_the_end_of_the_structure_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $structureVersion->id,
            'position' => 3,
        ]);
        $sectionVersion = SectionVersion::factory()->published()->create(['account_id' => $user->account_id]);

        $row = new AddSectionToStructureVersion(
            user: $user,
            structureVersion: $structureVersion,
            sectionVersion: $sectionVersion,
        )->execute();

        $this->assertEquals(4, $row->position);
    }

    #[Test]
    public function it_counts_the_structure_version_as_one_more_carrying_the_section(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        $sectionVersion = SectionVersion::factory()->published()->create(['account_id' => $user->account_id]);

        new AddSectionToStructureVersion(
            user: $user,
            structureVersion: $structureVersion,
            sectionVersion: $sectionVersion,
        )->execute();

        $this->assertEquals(1, $sectionVersion->fresh()->usage_count);
    }

    #[Test]
    public function it_refuses_the_same_section_twice_on_one_structure_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        $sectionVersion = SectionVersion::factory()->published()->create(['account_id' => $user->account_id]);

        new AddSectionToStructureVersion(
            user: $user,
            structureVersion: $structureVersion,
            sectionVersion: $sectionVersion,
        )->execute();

        $this->expectException(SectionIsAlreadyInTheStructureVersion::class);

        new AddSectionToStructureVersion(
            user: $user,
            structureVersion: $structureVersion,
            sectionVersion: $sectionVersion,
        )->execute();
    }

    #[Test]
    public function it_refuses_to_add_anything_to_a_published_structure_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->published()->create(['account_id' => $user->account_id]);
        $sectionVersion = SectionVersion::factory()->published()->create(['account_id' => $user->account_id]);

        $this->expectException(PublishedVersionIsFrozen::class);

        new AddSectionToStructureVersion(
            user: $user,
            structureVersion: $structureVersion,
            sectionVersion: $sectionVersion,
        )->execute();
    }

    #[Test]
    public function it_refuses_a_section_that_is_no_longer_offered(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        $sectionVersion = SectionVersion::factory()->retired()->create(['account_id' => $user->account_id]);

        $this->expectException(InvalidArgumentException::class);

        new AddSectionToStructureVersion(
            user: $user,
            structureVersion: $structureVersion,
            sectionVersion: $sectionVersion,
        )->execute();
    }

    #[Test]
    public function it_accepts_a_section_that_is_still_a_draft(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        $sectionVersion = SectionVersion::factory()->create(['account_id' => $user->account_id]);

        $row = new AddSectionToStructureVersion(
            user: $user,
            structureVersion: $structureVersion,
            sectionVersion: $sectionVersion,
        )->execute();

        $this->assertEquals(VersionStatusEnum::Draft, $row->sectionVersion->status);
    }

    #[Test]
    public function it_refuses_a_structure_version_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create();
        $sectionVersion = SectionVersion::factory()->published()->create(['account_id' => $michael->account_id]);

        $this->expectException(ModelNotFoundException::class);

        new AddSectionToStructureVersion(
            user: $michael,
            structureVersion: $structureVersion,
            sectionVersion: $sectionVersion,
        )->execute();
    }

    #[Test]
    public function it_refuses_a_section_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create(['account_id' => $michael->account_id]);
        $sectionVersion = SectionVersion::factory()->published()->create();

        $this->expectException(ModelNotFoundException::class);

        new AddSectionToStructureVersion(
            user: $michael,
            structureVersion: $structureVersion,
            sectionVersion: $sectionVersion,
        )->execute();
    }

    #[Test]
    public function it_writes_the_addition_to_the_logs(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Contact']);
        $structureVersion = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Address']);
        $sectionVersion = SectionVersion::factory()->published()->create(['section_id' => $section->id]);

        new AddSectionToStructureVersion(
            user: $user,
            structureVersion: $structureVersion,
            sectionVersion: $sectionVersion,
        )->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::SectionAddedToTemplate
                && $job->parameters === ['section' => 'Address', 'name' => 'Contact'];
        });
    }
}
