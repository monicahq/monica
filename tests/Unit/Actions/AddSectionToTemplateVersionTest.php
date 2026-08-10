<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\AddSectionToTemplateVersion;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Exceptions\SectionIsAlreadyInTheTemplateVersion;
use App\Jobs\LogUserAction;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AddSectionToTemplateVersionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_puts_a_section_on_a_template_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $templateVersion = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        $sectionVersion = SectionVersion::factory()->published()->create(['account_id' => $user->account_id]);

        $row = new AddSectionToTemplateVersion(
            user: $user,
            templateVersion: $templateVersion,
            sectionVersion: $sectionVersion,
            titleOverride: 'Where they live',
            isCollapsedByDefault: true,
        )->execute();

        $this->assertInstanceOf(TemplateVersionSection::class, $row);

        $this->assertDatabaseHas('template_version_sections', [
            'account_id' => $user->account_id,
            'template_version_id' => $templateVersion->id,
            'section_version_id' => $sectionVersion->id,
            'position' => 1,
            'title_override' => 'Where they live',
            'is_collapsed_by_default' => true,
        ]);
    }

    /**
     * Somebody who does not say which page means the first one, which is the
     * whole template until a second page exists.
     */
    #[Test]
    public function it_puts_a_section_on_the_first_page_when_nobody_says_which(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $templateVersion = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        $sectionVersion = SectionVersion::factory()->published()->create(['account_id' => $user->account_id]);

        $row = new AddSectionToTemplateVersion(
            user: $user,
            templateVersion: $templateVersion,
            sectionVersion: $sectionVersion,
        )->execute();

        $this->assertEquals($templateVersion->pages()->firstOrFail()->id, $row->template_version_page_id);
    }

    #[Test]
    public function it_puts_a_section_on_the_page_it_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $templateVersion = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        $health = TemplateVersionPage::factory()->create(['template_version_id' => $templateVersion->id]);
        $sectionVersion = SectionVersion::factory()->published()->create(['account_id' => $user->account_id]);

        $row = new AddSectionToTemplateVersion(
            user: $user,
            templateVersion: $templateVersion,
            sectionVersion: $sectionVersion,
            templateVersionPage: $health,
        )->execute();

        $this->assertEquals($health->id, $row->template_version_page_id);
        $this->assertEquals(1, $row->position);
    }

    #[Test]
    public function it_counts_the_position_within_the_page_rather_than_the_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $templateVersion = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        $health = TemplateVersionPage::factory()->create(['template_version_id' => $templateVersion->id]);
        TemplateVersionSection::factory()->create([
            'template_version_id' => $templateVersion->id,
            'template_version_page_id' => $templateVersion->pages()->firstOrFail()->id,
            'position' => 4,
        ]);
        $sectionVersion = SectionVersion::factory()->published()->create(['account_id' => $user->account_id]);

        $row = new AddSectionToTemplateVersion(
            user: $user,
            templateVersion: $templateVersion,
            sectionVersion: $sectionVersion,
            templateVersionPage: $health,
        )->execute();

        $this->assertEquals(1, $row->position);
    }

    #[Test]
    public function it_refuses_a_page_on_another_template_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $templateVersion = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        $sectionVersion = SectionVersion::factory()->published()->create(['account_id' => $user->account_id]);

        $this->expectException(ModelNotFoundException::class);

        new AddSectionToTemplateVersion(
            user: $user,
            templateVersion: $templateVersion,
            sectionVersion: $sectionVersion,
            templateVersionPage: $elsewhere->pages()->firstOrFail(),
        )->execute();
    }

    #[Test]
    public function it_puts_a_new_section_at_the_end_of_the_template_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $templateVersion = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        TemplateVersionSection::factory()->create([
            'template_version_id' => $templateVersion->id,
            'position' => 3,
        ]);
        $sectionVersion = SectionVersion::factory()->published()->create(['account_id' => $user->account_id]);

        $row = new AddSectionToTemplateVersion(
            user: $user,
            templateVersion: $templateVersion,
            sectionVersion: $sectionVersion,
        )->execute();

        $this->assertEquals(4, $row->position);
    }

    #[Test]
    public function it_counts_the_template_version_as_one_more_carrying_the_section(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $templateVersion = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        $sectionVersion = SectionVersion::factory()->published()->create(['account_id' => $user->account_id]);

        new AddSectionToTemplateVersion(
            user: $user,
            templateVersion: $templateVersion,
            sectionVersion: $sectionVersion,
        )->execute();

        $this->assertEquals(1, $sectionVersion->fresh()->usage_count);
    }

    /**
     * Two copies of one section would give two fields the same identity, and a
     * value written into either would have nowhere to go.
     */
    #[Test]
    public function it_refuses_the_same_section_twice_on_one_template_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $templateVersion = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        $sectionVersion = SectionVersion::factory()->published()->create(['account_id' => $user->account_id]);

        new AddSectionToTemplateVersion(
            user: $user,
            templateVersion: $templateVersion,
            sectionVersion: $sectionVersion,
        )->execute();

        $this->expectException(SectionIsAlreadyInTheTemplateVersion::class);

        new AddSectionToTemplateVersion(
            user: $user,
            templateVersion: $templateVersion,
            sectionVersion: $sectionVersion,
        )->execute();
    }

    #[Test]
    public function it_refuses_to_add_anything_to_a_published_template_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $templateVersion = TemplateVersion::factory()->published()->create(['account_id' => $user->account_id]);
        $sectionVersion = SectionVersion::factory()->published()->create(['account_id' => $user->account_id]);

        $this->expectException(PublishedVersionIsFrozen::class);

        new AddSectionToTemplateVersion(
            user: $user,
            templateVersion: $templateVersion,
            sectionVersion: $sectionVersion,
        )->execute();
    }

    #[Test]
    public function it_refuses_a_section_that_is_no_longer_offered(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $templateVersion = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        $sectionVersion = SectionVersion::factory()->retired()->create(['account_id' => $user->account_id]);

        $this->expectException(InvalidArgumentException::class);

        new AddSectionToTemplateVersion(
            user: $user,
            templateVersion: $templateVersion,
            sectionVersion: $sectionVersion,
        )->execute();
    }

    /**
     * A draft section on a draft template version is how somebody assembles one. The refusal
     * comes later, when the template version is published.
     */
    #[Test]
    public function it_accepts_a_section_that_is_still_a_draft(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $templateVersion = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        $sectionVersion = SectionVersion::factory()->create(['account_id' => $user->account_id]);

        $row = new AddSectionToTemplateVersion(
            user: $user,
            templateVersion: $templateVersion,
            sectionVersion: $sectionVersion,
        )->execute();

        $this->assertEquals(VersionStatusEnum::Draft, $row->sectionVersion->status);
    }

    #[Test]
    public function it_refuses_a_template_version_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $templateVersion = TemplateVersion::factory()->create();
        $sectionVersion = SectionVersion::factory()->published()->create(['account_id' => $michael->account_id]);

        $this->expectException(ModelNotFoundException::class);

        new AddSectionToTemplateVersion(
            user: $michael,
            templateVersion: $templateVersion,
            sectionVersion: $sectionVersion,
        )->execute();
    }

    #[Test]
    public function it_refuses_a_section_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $templateVersion = TemplateVersion::factory()->create(['account_id' => $michael->account_id]);
        $sectionVersion = SectionVersion::factory()->published()->create();

        $this->expectException(ModelNotFoundException::class);

        new AddSectionToTemplateVersion(
            user: $michael,
            templateVersion: $templateVersion,
            sectionVersion: $sectionVersion,
        )->execute();
    }

    #[Test]
    public function it_writes_the_addition_to_the_logs(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Contact']);
        $templateVersion = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Address']);
        $sectionVersion = SectionVersion::factory()->published()->create(['section_id' => $section->id]);

        new AddSectionToTemplateVersion(
            user: $user,
            templateVersion: $templateVersion,
            sectionVersion: $sectionVersion,
        )->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::SectionAddedToTemplate
                && $job->parameters === ['section' => 'Address', 'name' => 'Contact'];
        });
    }
}
