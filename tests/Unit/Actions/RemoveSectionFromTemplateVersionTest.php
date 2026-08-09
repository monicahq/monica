<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RemoveSectionFromTemplateVersion;
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

class RemoveSectionFromTemplateVersionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_takes_a_section_off_a_template_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $row = TemplateVersionSection::factory()->create(['account_id' => $user->account_id]);

        new RemoveSectionFromTemplateVersion(user: $user, row: $row)->execute();

        $this->assertModelMissing($row);
    }

    /**
     * The section is not owned by this template, and every other version
     * carrying it goes on as before.
     */
    #[Test]
    public function it_leaves_the_section_itself_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $sectionVersion = SectionVersion::factory()->published()->used(3)->create(['account_id' => $user->account_id]);
        $row = TemplateVersionSection::factory()->create([
            'account_id' => $user->account_id,
            'section_version_id' => $sectionVersion->id,
        ]);

        new RemoveSectionFromTemplateVersion(user: $user, row: $row)->execute();

        $this->assertModelExists($sectionVersion);
        $this->assertEquals(2, $sectionVersion->fresh()->usage_count);
    }

    /**
     * The column is unsigned, so a count that has drifted out of step must not
     * be taken below zero: mysql refuses the write outright, and the drivers
     * that allow it store a negative count nothing can read sensibly.
     */
    #[Test]
    public function it_never_takes_the_count_below_zero(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $sectionVersion = SectionVersion::factory()->published()->create(['account_id' => $user->account_id]);
        $row = TemplateVersionSection::factory()->create([
            'account_id' => $user->account_id,
            'section_version_id' => $sectionVersion->id,
        ]);

        new RemoveSectionFromTemplateVersion(user: $user, row: $row)->execute();

        $this->assertEquals(0, $sectionVersion->fresh()->usage_count);
    }

    /**
     * The refusal has to come before anything is written, not after a counter
     * has already moved and the transaction has to undo it.
     */
    #[Test]
    public function it_leaves_the_count_alone_when_it_refuses(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $templateVersion = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        $sectionVersion = SectionVersion::factory()->published()->used(3)->create(['account_id' => $user->account_id]);
        $row = TemplateVersionSection::factory()->create([
            'account_id' => $user->account_id,
            'template_version_id' => $templateVersion->id,
            'section_version_id' => $sectionVersion->id,
        ]);
        $templateVersion->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);

        try {
            new RemoveSectionFromTemplateVersion(user: $user, row: $row->fresh())->execute();
        } catch (PublishedVersionIsFrozen) {
            // the refusal is what the test beside this one asserts
        }

        $this->assertEquals(3, $sectionVersion->fresh()->usage_count);
    }

    #[Test]
    public function it_refuses_to_take_anything_off_a_published_template_version(): void
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

        new RemoveSectionFromTemplateVersion(user: $user, row: $row->fresh())->execute();
    }

    #[Test]
    public function it_refuses_an_assembly_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $row = TemplateVersionSection::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new RemoveSectionFromTemplateVersion(user: $michael, row: $row)->execute();
    }

    #[Test]
    public function it_writes_the_removal_to_the_logs(): void
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

        new RemoveSectionFromTemplateVersion(user: $user, row: $row)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::SectionRemovedFromTemplate
                && $job->parameters === ['section' => 'Address', 'name' => 'Contact'];
        });
    }
}
