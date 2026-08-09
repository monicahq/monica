<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RetireSectionVersion;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Jobs\LogUserAction;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RetireSectionVersionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_stops_a_version_being_offered(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id]);
        $version = SectionVersion::factory()->published()->create(['section_id' => $section->id]);

        new RetireSectionVersion(user: $user, sectionVersion: $version)->execute();

        $version->refresh();

        $this->assertEquals(VersionStatusEnum::Retired, $version->status);
        $this->assertNotNull($version->retired_at);
    }

    #[Test]
    public function it_leaves_every_template_version_already_containing_it_untouched(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id]);
        $version = SectionVersion::factory()->published()->create(['section_id' => $section->id]);
        $row = TemplateVersionSection::factory()->create(['section_version_id' => $version->id]);

        new RetireSectionVersion(user: $user, sectionVersion: $version)->execute();

        $this->assertModelExists($row);
    }

    #[Test]
    public function it_stops_the_section_pointing_at_a_retired_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id]);
        $version = SectionVersion::factory()->published()->create(['section_id' => $section->id]);
        $section->update(['published_version_id' => $version->id]);

        new RetireSectionVersion(user: $user, sectionVersion: $version)->execute();

        $this->assertNull($section->fresh()->published_version_id);
    }

    #[Test]
    public function it_refuses_to_retire_a_draft(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id]);
        $version = SectionVersion::factory()->create(['section_id' => $section->id]);

        $this->expectException(InvalidArgumentException::class);

        new RetireSectionVersion(user: $user, sectionVersion: $version)->execute();
    }

    #[Test]
    public function it_refuses_a_version_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $version = SectionVersion::factory()->published()->create();

        $this->expectException(ModelNotFoundException::class);

        new RetireSectionVersion(user: $michael, sectionVersion: $version)->execute();
    }

    #[Test]
    public function it_writes_the_retirement_to_the_logs(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Address']);
        $version = SectionVersion::factory()->published()->create(['section_id' => $section->id]);

        new RetireSectionVersion(user: $user, sectionVersion: $version)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::SectionVersionRetired
                && $job->parameters === ['name' => 'Address', 'version' => 1];
        });
    }
}
