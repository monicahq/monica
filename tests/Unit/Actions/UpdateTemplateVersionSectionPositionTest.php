<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateTemplateVersionSectionPosition;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Jobs\LogUserAction;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateTemplateVersionSectionPositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_section_up_the_template_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        $first = TemplateVersionSection::factory()->create(['template_version_id' => $version->id, 'position' => 1]);
        $second = TemplateVersionSection::factory()->create(['template_version_id' => $version->id, 'position' => 2]);
        $third = TemplateVersionSection::factory()->create(['template_version_id' => $version->id, 'position' => 3]);

        new UpdateTemplateVersionSectionPosition(user: $user, row: $third, position: 1)->execute();

        $this->assertEquals(1, $third->fresh()->position);
        $this->assertEquals(2, $first->fresh()->position);
        $this->assertEquals(3, $second->fresh()->position);
    }

    #[Test]
    public function it_puts_a_section_last_when_the_position_is_past_the_end(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        $first = TemplateVersionSection::factory()->create(['template_version_id' => $version->id, 'position' => 1]);
        $second = TemplateVersionSection::factory()->create(['template_version_id' => $version->id, 'position' => 2]);

        new UpdateTemplateVersionSectionPosition(user: $user, row: $first, position: 99)->execute();

        $this->assertEquals(1, $second->fresh()->position);
        $this->assertEquals(2, $first->fresh()->position);
    }

    #[Test]
    public function it_leaves_the_sections_of_another_template_version_where_they_are(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $mine = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        $other = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        $row = TemplateVersionSection::factory()->create(['template_version_id' => $mine->id, 'position' => 1]);
        $elsewhere = TemplateVersionSection::factory()->create(['template_version_id' => $other->id, 'position' => 1]);

        new UpdateTemplateVersionSectionPosition(user: $user, row: $row, position: 5)->execute();

        $this->assertEquals(1, $elsewhere->fresh()->position);
    }

    #[Test]
    public function it_refuses_to_reorder_a_published_template_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $version = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        $row = TemplateVersionSection::factory()->create(['template_version_id' => $version->id, 'position' => 1]);
        $version->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);

        $this->expectException(PublishedVersionIsFrozen::class);

        new UpdateTemplateVersionSectionPosition(user: $user, row: $row->fresh(), position: 1)->execute();
    }

    #[Test]
    public function it_refuses_an_assembly_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $row = TemplateVersionSection::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdateTemplateVersionSectionPosition(user: $michael, row: $row, position: 1)->execute();
    }

    #[Test]
    public function it_writes_the_move_to_the_logs(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Contact']);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Address']);
        $sectionVersion = SectionVersion::factory()->published()->create(['section_id' => $section->id]);
        $row = TemplateVersionSection::factory()->create([
            'template_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        new UpdateTemplateVersionSectionPosition(user: $user, row: $row, position: 1)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::TemplateSectionPositionUpdate
                && $job->parameters === ['section' => 'Address', 'name' => 'Contact'];
        });
    }
}
