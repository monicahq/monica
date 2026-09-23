<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateLifeEventType;
use App\Actions\CreateStructureVersionDraft;
use App\Actions\RemoveSectionFromStructureVersion;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Jobs\LogUserAction;
use App\Models\LifeEventCategory;
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

class RemoveSectionFromStructureVersionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_takes_a_section_off_a_structure_version(): void
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

        new RemoveSectionFromStructureVersion(user: $user, row: $row)->execute();

        $this->assertModelMissing($row);

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::SectionRemovedFromTemplate
                && $job->parameters === ['section' => 'Address', 'name' => 'Contact'];
        });
    }

    #[Test]
    public function it_leaves_the_section_itself_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $sectionVersion = SectionVersion::factory()->published()->used(3)->create(['account_id' => $user->account_id]);
        $row = StructureVersionSection::factory()->create([
            'account_id' => $user->account_id,
            'section_version_id' => $sectionVersion->id,
        ]);

        new RemoveSectionFromStructureVersion(user: $user, row: $row)->execute();

        $this->assertModelExists($sectionVersion);
        $this->assertEquals(2, $sectionVersion->fresh()->usage_count);
    }

    #[Test]
    public function it_never_takes_the_count_below_zero(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $sectionVersion = SectionVersion::factory()->published()->create(['account_id' => $user->account_id]);
        $row = StructureVersionSection::factory()->create([
            'account_id' => $user->account_id,
            'section_version_id' => $sectionVersion->id,
        ]);

        new RemoveSectionFromStructureVersion(user: $user, row: $row)->execute();

        $this->assertEquals(0, $sectionVersion->fresh()->usage_count);
    }

    #[Test]
    public function it_leaves_the_count_alone_when_it_refuses(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        $sectionVersion = SectionVersion::factory()->published()->used(3)->create(['account_id' => $user->account_id]);
        $row = StructureVersionSection::factory()->create([
            'account_id' => $user->account_id,
            'structure_version_id' => $structureVersion->id,
            'section_version_id' => $sectionVersion->id,
        ]);
        $structureVersion->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);

        try {
            new RemoveSectionFromStructureVersion(user: $user, row: $row->fresh())->execute();
        } catch (PublishedVersionIsFrozen) {
        }

        $this->assertEquals(3, $sectionVersion->fresh()->usage_count);
    }

    #[Test]
    public function it_refuses_to_take_anything_off_a_published_structure_version(): void
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

        new RemoveSectionFromStructureVersion(user: $user, row: $row->fresh())->execute();
    }

    #[Test]
    public function it_refuses_an_assembly_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $row = StructureVersionSection::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new RemoveSectionFromStructureVersion(user: $michael, row: $row)->execute();
    }

    #[Test]
    public function it_takes_a_section_off_a_life_event_type_structure(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $draft = $this->lifeEventTypeDraft($user);
        $row = StructureVersionSection::factory()->create([
            'account_id' => $user->account_id,
            'structure_version_id' => $draft->id,
        ]);

        new RemoveSectionFromStructureVersion(user: $user, row: $row)->execute();

        $this->assertModelMissing($row);
    }

    private function lifeEventTypeDraft(User $user): StructureVersion
    {
        $lifeEventType = new CreateLifeEventType(
            user: $user,
            lifeEventCategory: LifeEventCategory::factory()->create(['account_id' => $user->account_id]),
            name: 'Restaurant',
        )->execute();

        return new CreateStructureVersionDraft(user: $user, structureVersion: $lifeEventType->structure->publishedVersion)->execute();
    }
}
