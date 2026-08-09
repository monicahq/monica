<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\PublishTemplateVersion;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\CannotPublishWhileASectionIsStillADraft;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Jobs\LogUserAction;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublishTemplateVersionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_freezes_a_draft(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);

        new PublishTemplateVersion(user: $user, templateVersion: $version)->execute();

        $version->refresh();

        $this->assertEquals(VersionStatusEnum::Published, $version->status);
        $this->assertNotNull($version->published_at);
    }

    #[Test]
    public function it_makes_the_version_the_one_the_template_offers(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);

        new PublishTemplateVersion(user: $user, templateVersion: $version)->execute();

        $this->assertEquals($version->id, $template->fresh()->published_version_id);
    }

    /**
     * The note is what somebody deciding whether to move their records reads,
     * and the first version has nothing to have changed from.
     */
    #[Test]
    public function it_asks_for_a_change_note_from_the_second_version_on(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $first = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);
        $second = TemplateVersion::factory()->create([
            'template_id' => $template->id,
            'version_number' => 2,
            'previous_version_id' => $first->id,
        ]);

        $this->expectException(InvalidArgumentException::class);

        new PublishTemplateVersion(user: $user, templateVersion: $second)->execute();
    }

    #[Test]
    public function it_records_what_changed(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $first = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);
        $second = TemplateVersion::factory()->create([
            'template_id' => $template->id,
            'version_number' => 2,
            'previous_version_id' => $first->id,
        ]);

        new PublishTemplateVersion(
            user: $user,
            templateVersion: $second,
            changeNote: 'Added the section about allergies',
        )->execute();

        $this->assertEquals('Added the section about allergies', $second->fresh()->change_note);
    }

    /**
     * Freezing a template version that still points at a section somebody is editing would
     * freeze nothing at all.
     */
    #[Test]
    public function it_refuses_while_the_template_version_carries_a_section_nobody_published(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $sectionVersion = SectionVersion::factory()->create(['account_id' => $user->account_id]);
        TemplateVersionSection::factory()->create([
            'template_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        $this->expectException(CannotPublishWhileASectionIsStillADraft::class);

        new PublishTemplateVersion(user: $user, templateVersion: $version)->execute();
    }

    #[Test]
    public function it_publishes_a_template_version_whose_sections_are_all_published(): void
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

        new PublishTemplateVersion(user: $user, templateVersion: $version)->execute();

        $this->assertEquals(VersionStatusEnum::Published, $version->fresh()->status);
    }

    #[Test]
    public function it_refuses_to_publish_something_that_is_not_a_draft(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);

        $this->expectException(PublishedVersionIsFrozen::class);

        new PublishTemplateVersion(user: $user, templateVersion: $version)->execute();
    }

    #[Test]
    public function it_refuses_a_version_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $version = TemplateVersion::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new PublishTemplateVersion(user: $michael, templateVersion: $version)->execute();
    }

    #[Test]
    public function it_writes_the_publication_to_the_logs(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Contact']);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);

        new PublishTemplateVersion(user: $user, templateVersion: $version)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::TemplateVersionPublished
                && $job->parameters === ['name' => 'Contact', 'version' => 1];
        });
    }
}
