<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroySectionVersion;
use App\Enums\UserActionEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Jobs\LogUserAction;
use App\Models\Field;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroySectionVersionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_throws_away_a_draft_and_the_fields_in_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id]);
        $version = SectionVersion::factory()->create(['section_id' => $section->id]);
        $field = Field::factory()->create(['section_version_id' => $version->id]);

        new DestroySectionVersion(user: $user, sectionVersion: $version)->execute();

        $this->assertModelMissing($version);
        $this->assertModelMissing($field);
    }

    #[Test]
    public function it_refuses_to_throw_away_a_published_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id]);
        $version = SectionVersion::factory()->published()->create(['section_id' => $section->id]);

        $this->expectException(PublishedVersionIsFrozen::class);

        new DestroySectionVersion(user: $user, sectionVersion: $version)->execute();
    }

    #[Test]
    public function it_refuses_to_throw_away_a_draft_a_template_version_already_points_at(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id]);
        $version = SectionVersion::factory()->used(1)->create(['section_id' => $section->id]);

        $this->expectException(PublishedVersionIsFrozen::class);

        new DestroySectionVersion(user: $user, sectionVersion: $version)->execute();
    }

    #[Test]
    public function it_refuses_a_version_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $version = SectionVersion::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DestroySectionVersion(user: $michael, sectionVersion: $version)->execute();
    }

    #[Test]
    public function it_writes_the_deletion_to_the_logs(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Address']);
        $version = SectionVersion::factory()->create(['section_id' => $section->id]);

        new DestroySectionVersion(user: $user, sectionVersion: $version)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::SectionVersionDeletion
                && $job->parameters === ['name' => 'Address', 'version' => 1];
        });
    }
}
