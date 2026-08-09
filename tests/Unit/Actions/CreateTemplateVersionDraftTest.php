<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateTemplateVersionDraft;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Jobs\LogUserAction;
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

class CreateTemplateVersionDraftTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_starts_a_draft_from_a_published_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $published = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);

        $draft = new CreateTemplateVersionDraft(user: $user, templateVersion: $published)->execute();

        $this->assertInstanceOf(TemplateVersion::class, $draft);
        $this->assertEquals(2, $draft->version_number);
        $this->assertEquals(VersionStatusEnum::Draft, $draft->status);
        $this->assertEquals($published->id, $draft->previous_version_id);
        $this->assertEquals(0, $draft->record_count);
    }

    /**
     * The point of drafting is that the published version keeps rendering
     * exactly what it did while the changes happen beside it.
     */
    #[Test]
    public function it_leaves_the_published_version_exactly_as_it_was(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $published = TemplateVersion::factory()->published()->used(214)->create(['template_id' => $template->id]);

        new CreateTemplateVersionDraft(user: $user, templateVersion: $published)->execute();

        $published->refresh();

        $this->assertEquals(VersionStatusEnum::Published, $published->status);
        $this->assertEquals(214, $published->record_count);
        $this->assertEquals($published->id, $template->fresh()->published_version_id ?? $published->id);
    }

    #[Test]
    public function it_copies_the_assembly_row_for_row(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $sectionVersion = SectionVersion::factory()->published()->create(['account_id' => $user->account_id]);
        TemplateVersionSection::factory()->collapsed()->create([
            'template_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
            'position' => 1,
            'title_override' => 'Where they live',
        ]);

        $draft = new CreateTemplateVersionDraft(user: $user, templateVersion: $version)->execute();
        $row = $draft->assembly()->first();

        $this->assertEquals($sectionVersion->id, $row->section_version_id);
        $this->assertEquals(1, $row->position);
        $this->assertEquals('Where they live', $row->title_override);
        $this->assertTrue($row->is_collapsed_by_default);
    }

    #[Test]
    public function it_counts_the_draft_as_one_more_template_version_carrying_each_section(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $sectionVersion = SectionVersion::factory()->published()->create(['account_id' => $user->account_id]);
        TemplateVersionSection::factory()->create([
            'template_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        new CreateTemplateVersionDraft(user: $user, templateVersion: $version)->execute();

        $this->assertEquals(1, $sectionVersion->fresh()->usage_count);
    }

    #[Test]
    public function it_numbers_the_draft_after_the_highest_version_there_is(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $first = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);
        TemplateVersion::factory()->create(['template_id' => $template->id, 'version_number' => 7]);

        $draft = new CreateTemplateVersionDraft(user: $user, templateVersion: $first)->execute();

        $this->assertEquals(8, $draft->version_number);
    }

    #[Test]
    public function it_refuses_a_version_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $version = TemplateVersion::factory()->published()->create();

        $this->expectException(ModelNotFoundException::class);

        new CreateTemplateVersionDraft(user: $michael, templateVersion: $version)->execute();
    }

    #[Test]
    public function it_writes_the_draft_to_the_logs(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Contact']);
        $published = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);

        new CreateTemplateVersionDraft(user: $user, templateVersion: $published)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::TemplateVersionDrafted
                && $job->parameters === ['name' => 'Contact', 'version' => 2];
        });
    }
}
