<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateLifeEventType;
use App\Actions\CreateStructureVersionDraft;
use App\Actions\PublishStructureVersion;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\CannotPublishAnEmptyPage;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Jobs\LogUserAction;
use App\Models\LifeEventCategory;
use App\Models\SectionVersion;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublishStructureVersionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_freezes_a_draft(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Contact']);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        new PublishStructureVersion(user: $user, structureVersion: $version)->execute();

        $version->refresh();

        $this->assertEquals(VersionStatusEnum::Published, $version->status);
        $this->assertNotNull($version->published_at);

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::TemplateVersionPublished
                && $job->parameters === ['name' => 'Contact', 'version' => 1];
        });
    }

    #[Test]
    public function it_makes_the_version_the_one_the_template_offers(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        new PublishStructureVersion(user: $user, structureVersion: $version)->execute();

        $this->assertEquals($version->id, $template->structure->fresh()->published_version_id);
    }

    #[Test]
    public function it_asks_for_a_change_note_from_the_second_version_on(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $first = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);
        $second = StructureVersion::factory()->create([
            'structure_id' => $template->structure->id,
            'version_number' => 2,
            'previous_version_id' => $first->id,
        ]);

        $this->expectException(InvalidArgumentException::class);

        new PublishStructureVersion(user: $user, structureVersion: $second)->execute();
    }

    #[Test]
    public function it_records_what_changed(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $first = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);
        $second = StructureVersion::factory()->create([
            'structure_id' => $template->structure->id,
            'version_number' => 2,
            'previous_version_id' => $first->id,
        ]);

        new PublishStructureVersion(
            user: $user,
            structureVersion: $second,
            changeNote: 'Added the section about allergies',
        )->execute();

        $this->assertEquals('Added the section about allergies', $second->fresh()->change_note);
    }

    #[Test]
    public function it_publishes_the_draft_sections_it_carries(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $sectionVersion = SectionVersion::factory()->create(['account_id' => $user->account_id]);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        new PublishStructureVersion(user: $user, structureVersion: $version)->execute();

        $this->assertEquals(VersionStatusEnum::Published, $sectionVersion->fresh()->status);
        $this->assertEquals(VersionStatusEnum::Published, $version->fresh()->status);
    }

    #[Test]
    public function it_says_a_section_it_published_went_out_with_the_template(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $first = SectionVersion::factory()->published()->create(['account_id' => $user->account_id]);
        $second = SectionVersion::factory()->create([
            'account_id' => $user->account_id,
            'section_id' => $first->section_id,
            'version_number' => 2,
            'previous_version_id' => $first->id,
        ]);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => $second->id,
        ]);

        new PublishStructureVersion(user: $user, structureVersion: $version)->execute();

        $this->assertEquals('Published with the template.', $second->fresh()->change_note);
    }

    #[Test]
    public function it_leaves_the_sections_of_another_account_alone_when_it_refuses_the_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $somebodyElse = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $somebodyElse->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $sectionVersion = SectionVersion::factory()->create(['account_id' => $somebodyElse->account_id]);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        try {
            new PublishStructureVersion(user: $user, structureVersion: $version)->execute();
            $this->fail('The version of another account was published.');
        } catch (ModelNotFoundException) {
            //
        }

        $this->assertEquals(VersionStatusEnum::Draft, $sectionVersion->fresh()->status);
    }

    #[Test]
    public function it_publishes_a_structure_version_whose_sections_are_all_published(): void
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

        new PublishStructureVersion(user: $user, structureVersion: $version)->execute();

        $this->assertEquals(VersionStatusEnum::Published, $version->fresh()->status);
    }

    #[Test]
    public function it_refuses_while_one_of_several_pages_has_nothing_on_it(): void
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
        StructureVersionPage::factory()->create(['structure_version_id' => $version->id]);

        $this->expectException(CannotPublishAnEmptyPage::class);

        new PublishStructureVersion(user: $user, structureVersion: $version)->execute();
    }

    #[Test]
    public function it_publishes_a_version_whose_single_page_is_empty(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);

        new PublishStructureVersion(user: $user, structureVersion: $version)->execute();

        $this->assertEquals(VersionStatusEnum::Published, $version->fresh()->status);
    }

    #[Test]
    public function it_refuses_a_version_with_no_page_at_all(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->create(['structure_id' => $template->structure->id]);
        $version->pages()->each(fn (StructureVersionPage $page): ?bool => $page->delete());

        $this->expectException(CannotPublishAnEmptyPage::class);

        new PublishStructureVersion(user: $user, structureVersion: $version->refresh())->execute();
    }

    #[Test]
    public function it_refuses_to_publish_something_that_is_not_a_draft(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = StructureVersion::factory()->published()->create(['structure_id' => $template->structure->id]);

        $this->expectException(PublishedVersionIsFrozen::class);

        new PublishStructureVersion(user: $user, structureVersion: $version)->execute();
    }

    #[Test]
    public function it_refuses_a_version_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $version = StructureVersion::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new PublishStructureVersion(user: $michael, structureVersion: $version)->execute();
    }

    #[Test]
    public function it_says_a_section_it_published_went_out_with_the_life_event_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $draft = $this->lifeEventTypeDraft($user);
        $first = SectionVersion::factory()->published()->create(['account_id' => $user->account_id]);
        $second = SectionVersion::factory()->create([
            'account_id' => $user->account_id,
            'section_id' => $first->section_id,
            'version_number' => 2,
            'previous_version_id' => $first->id,
        ]);
        StructureVersionSection::factory()->create([
            'structure_version_id' => $draft->id,
            'section_version_id' => $second->id,
        ]);

        new PublishStructureVersion(user: $user, structureVersion: $draft, changeNote: 'Where we went.')->execute();

        $this->assertEquals('Published with the life event type.', $second->fresh()->change_note);
    }

    #[Test]
    public function it_writes_the_publication_of_custom_fields_to_the_logs_as_the_life_event_type_it_is(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $draft = $this->lifeEventTypeDraft($user);

        new PublishStructureVersion(user: $user, structureVersion: $draft, changeNote: 'Where we went.')->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::LifeEventTypeFieldsPublished
                && $job->parameters === ['name' => 'Restaurant', 'version' => 2];
        });
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
