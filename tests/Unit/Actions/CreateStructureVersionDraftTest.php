<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateStructureVersionDraft;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Jobs\LogUserAction;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateStructureVersionDraftTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_starts_a_draft_from_a_published_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Contact']);
        $published = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);

        $draft = new CreateStructureVersionDraft(user: $user, structureVersion: $published)->execute();

        $this->assertInstanceOf(StructureVersion::class, $draft);
        $this->assertEquals(2, $draft->version_number);
        $this->assertEquals(VersionStatusEnum::Draft, $draft->status);
        $this->assertEquals($published->id, $draft->previous_version_id);
        $this->assertEquals(0, $draft->record_count);

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::TemplateVersionDrafted
                && $job->parameters === ['name' => 'Contact', 'version' => 2];
        });
    }

    #[Test]
    public function it_leaves_the_published_version_exactly_as_it_was(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $published = StructureVersion::factory()->published()->used(214)->create(['structure_id' => $template->structure->id]);

        new CreateStructureVersionDraft(user: $user, structureVersion: $published)->execute();

        $published->refresh();

        $this->assertEquals(VersionStatusEnum::Published, $published->status);
        $this->assertEquals(214, $published->record_count);
        $this->assertEquals($published->id, $template->structure->fresh()->published_version_id ?? $published->id);
    }

    #[Test]
    public function it_copies_the_assembly_row_for_row(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $sectionVersion = SectionVersion::factory()->published()->create(['account_id' => $user->account_id]);
        StructureVersionSection::factory()->collapsed()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
            'position' => 1,
            'title_override' => 'Where they live',
        ]);

        $version->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);

        $draft = new CreateStructureVersionDraft(user: $user, structureVersion: $version)->execute();
        $row = $draft->assembly()->first();

        $this->assertEquals($sectionVersion->id, $row->section_version_id);
        $this->assertEquals(1, $row->position);
        $this->assertEquals('Where they live', $row->title_override);
        $this->assertTrue($row->is_collapsed_by_default);
    }

    #[Test]
    public function it_copies_the_pages_and_puts_each_section_back_on_its_own(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $general = $version->pages()->firstOrFail();
        $health = StructureVersionPage::factory()->create([
            'structure_version_id' => $version->id,
            'key' => 'health',
            'label' => 'Health',
            'position' => 2,
        ]);
        $sectionVersion = SectionVersion::factory()->published()->create(['account_id' => $user->account_id]);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'structure_version_page_id' => $health->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $version->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);

        $draft = new CreateStructureVersionDraft(user: $user, structureVersion: $version)->execute();
        $pages = $draft->pages;

        $this->assertCount(2, $pages);
        $this->assertEquals(['general', 'health'], $pages->pluck('key')->all());
        $this->assertEquals(['General', 'Health'], $pages->map(fn (StructureVersionPage $page): string => $page->label)->all());
        $this->assertNotEquals($general->id, $pages->first()->id);
        $this->assertEquals($pages->last()->id, $draft->assembly()->sole()->structure_version_page_id);
    }

    #[Test]
    public function it_counts_the_draft_as_one_more_structure_version_carrying_each_section(): void
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

        $version->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);

        new CreateStructureVersionDraft(user: $user, structureVersion: $version)->execute();

        $this->assertEquals(1, $sectionVersion->fresh()->usage_count);
    }

    #[Test]
    public function it_numbers_the_draft_after_the_highest_version_there_is(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $first = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);
        StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id, 'version_number' => 7]);

        $draft = new CreateStructureVersionDraft(user: $user, structureVersion: $first)->execute();

        $this->assertEquals(8, $draft->version_number);
    }

    #[Test]
    public function it_refuses_a_version_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $version = StructureVersion::factory()->published()->create();

        $this->expectException(ModelNotFoundException::class);

        new CreateStructureVersionDraft(user: $michael, structureVersion: $version)->execute();
    }

    #[Test]
    public function it_answers_with_the_draft_that_is_already_open_rather_than_starting_a_second(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $published = StructureVersion::factory()->published()->create([
            'structure_id' => $template->structure->id,
            'version_number' => 1,
        ]);

        $first = new CreateStructureVersionDraft(user: $user, structureVersion: $published)->execute();
        $second = new CreateStructureVersionDraft(user: $user, structureVersion: $published)->execute();

        $this->assertTrue($first->is($second));
        $this->assertEquals(2, $template->versions()->count());
        $this->assertEquals(1, $template->versions()->where('status', VersionStatusEnum::Draft)->count());
    }

    #[Test]
    public function it_does_not_record_a_second_drafting_when_a_draft_is_already_open(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $published = StructureVersion::factory()->published()->create([
            'structure_id' => $template->structure->id,
            'version_number' => 1,
        ]);

        new CreateStructureVersionDraft(user: $user, structureVersion: $published)->execute();
        new CreateStructureVersionDraft(user: $user, structureVersion: $published)->execute();

        Queue::assertPushed(LogUserAction::class, 1);
    }

    #[Test]
    public function it_starts_a_draft_again_once_the_open_one_has_been_published(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $published = StructureVersion::factory()->published()->create([
            'structure_id' => $template->structure->id,
            'version_number' => 1,
        ]);

        $first = new CreateStructureVersionDraft(user: $user, structureVersion: $published)->execute();
        $first->update(['status' => VersionStatusEnum::Published]);

        $second = new CreateStructureVersionDraft(user: $user, structureVersion: $first)->execute();

        $this->assertFalse($first->is($second));
        $this->assertEquals(3, $second->version_number);
    }
}
