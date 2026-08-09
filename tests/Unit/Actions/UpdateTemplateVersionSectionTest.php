<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateTemplateVersionSection;
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

class UpdateTemplateVersionSectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_how_a_section_reads_on_one_template_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $row = TemplateVersionSection::factory()->create(['account_id' => $user->account_id]);

        new UpdateTemplateVersionSection(
            user: $user,
            row: $row,
            titleOverride: 'Where they live',
            isCollapsedByDefault: true,
        )->execute();

        $row->refresh();

        $this->assertEquals('Where they live', $row->title_override);
        $this->assertTrue($row->is_collapsed_by_default);
    }

    /**
     * The heading a template version gives a section does not rename the section for anybody
     * else who carries it.
     */
    #[Test]
    public function it_leaves_the_name_of_the_section_itself_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Address']);
        $sectionVersion = SectionVersion::factory()->published()->create(['section_id' => $section->id]);
        $row = TemplateVersionSection::factory()->create([
            'account_id' => $user->account_id,
            'section_version_id' => $sectionVersion->id,
        ]);

        new UpdateTemplateVersionSection(user: $user, row: $row, titleOverride: 'Where they live')->execute();

        $this->assertEquals('Address', $section->fresh()->name);
    }

    #[Test]
    public function it_puts_the_sections_own_name_back_when_the_override_is_taken_away(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $row = TemplateVersionSection::factory()->create([
            'account_id' => $user->account_id,
            'title_override' => 'Where they live',
        ]);

        new UpdateTemplateVersionSection(user: $user, row: $row)->execute();

        $this->assertNull($row->fresh()->title_override);
    }

    #[Test]
    public function it_refuses_to_change_anything_on_a_published_template_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $templateVersion = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        $row = TemplateVersionSection::factory()->create([
            'account_id' => $user->account_id,
            'template_version_id' => $templateVersion->id,
        ]);
        $templateVersion->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);

        $this->expectException(PublishedVersionIsFrozen::class);

        new UpdateTemplateVersionSection(user: $user, row: $row->fresh(), titleOverride: 'Nope')->execute();
    }

    #[Test]
    public function it_refuses_an_assembly_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $row = TemplateVersionSection::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdateTemplateVersionSection(user: $michael, row: $row)->execute();
    }

    #[Test]
    public function it_writes_the_change_to_the_logs(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Contact']);
        $templateVersion = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Address']);
        $sectionVersion = SectionVersion::factory()->published()->create(['section_id' => $section->id]);
        $row = TemplateVersionSection::factory()->create([
            'template_version_id' => $templateVersion->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        new UpdateTemplateVersionSection(user: $user, row: $row)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::TemplateSectionUpdate
                && $job->parameters === ['section' => 'Address', 'name' => 'Contact'];
        });
    }
}
