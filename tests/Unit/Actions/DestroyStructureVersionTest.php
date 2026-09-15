<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyStructureVersion;
use App\Enums\UserActionEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Jobs\LogUserAction;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\StructureVersion;
use App\Models\StructureVersionSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use App\Actions\CreateActivityType;
use App\Actions\CreateStructureVersionDraft;
use App\Models\ActivityCategory;
use Tests\TestCase;

class DestroyStructureVersionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_throws_away_a_draft(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        new DestroyStructureVersion(user: $user, structureVersion: $version)->execute();

        $this->assertModelMissing($version);
    }

    #[Test]
    public function it_takes_the_assembly_with_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $row = StructureVersionSection::factory()->create(['structure_version_id' => $version->id]);

        new DestroyStructureVersion(user: $user, structureVersion: $version)->execute();

        $this->assertModelMissing($row);
    }

    #[Test]
    public function it_leaves_each_section_in_one_structure_version_fewer(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $sectionVersion = SectionVersion::factory()->published()->used(3)->create(['account_id' => $user->account_id]);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        new DestroyStructureVersion(user: $user, structureVersion: $version)->execute();

        $this->assertEquals(2, $sectionVersion->fresh()->usage_count);
    }

    #[Test]
    public function it_never_takes_the_count_below_zero(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $sectionVersion = SectionVersion::factory()->published()->create(['account_id' => $user->account_id]);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        new DestroyStructureVersion(user: $user, structureVersion: $version)->execute();

        $this->assertEquals(0, $sectionVersion->fresh()->usage_count);
    }

    #[Test]
    public function it_refuses_to_throw_away_a_published_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);

        $this->expectException(PublishedVersionIsFrozen::class);

        new DestroyStructureVersion(user: $user, structureVersion: $version)->execute();
    }

    #[Test]
    public function it_refuses_a_version_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $version = StructureVersion::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DestroyStructureVersion(user: $michael, structureVersion: $version)->execute();
    }

    #[Test]
    public function it_writes_the_deletion_to_the_logs(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Contact']);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        new DestroyStructureVersion(user: $user, structureVersion: $version)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::TemplateVersionDeletion
                && $job->parameters === ['name' => 'Contact', 'version' => 1];
        });
    }

    #[Test]
    public function it_throws_away_a_draft_of_an_activity_type_structure(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $draft = $this->activityTypeDraft($user);

        new DestroyStructureVersion(user: $user, structureVersion: $draft)->execute();

        $this->assertModelMissing($draft);
        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::TemplateVersionDeletion && $job->parameters['name'] === 'Restaurant',
        );
    }

    private function activityTypeDraft(User $user): StructureVersion
    {
        $activityType = new CreateActivityType(
            user: $user,
            activityCategory: ActivityCategory::factory()->create(['account_id' => $user->account_id]),
            name: 'Restaurant',
        )->execute();

        return new CreateStructureVersionDraft(user: $user, structureVersion: $activityType->structure->publishedVersion)->execute();
    }
}
