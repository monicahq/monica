<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DeactivateSection;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeactivateSectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_stops_a_section_being_offered(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id]);

        new DeactivateSection(user: $user, section: $section)->execute();

        $this->assertFalse($section->fresh()->is_active);
    }

    #[Test]
    public function it_leaves_every_template_version_already_carrying_it_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id]);
        $version = SectionVersion::factory()->published()->create(['section_id' => $section->id]);
        $row = TemplateVersionSection::factory()->create(['section_version_id' => $version->id]);

        new DeactivateSection(user: $user, section: $section)->execute();

        $this->assertModelExists($row);
    }

    #[Test]
    public function it_refuses_a_section_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $section = Section::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DeactivateSection(user: $michael, section: $section)->execute();
    }

    #[Test]
    public function it_writes_the_change_to_the_logs(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Address']);

        new DeactivateSection(user: $user, section: $section)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::SectionDeactivated
                && $job->parameters === ['name' => 'Address'];
        });
    }
}
