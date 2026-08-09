<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateSection;
use App\Actions\DestroySection;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\CannotDeleteASectionInUse;
use App\Jobs\LogUserAction;
use App\Models\Section;
use App\Models\SectionVersion;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroySectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_section_no_published_template_version_carries(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id]);

        new DestroySection(user: $user, section: $section)->execute();

        $this->assertSoftDeleted($section);
    }

    /**
     * Taking a section out from under a published template version would change what it
     * renders, which is the one thing publication rules out.
     */
    #[Test]
    public function it_refuses_while_a_published_template_version_carries_one_of_its_versions(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id]);
        $sectionVersion = SectionVersion::factory()->published()->create(['section_id' => $section->id]);
        $templateVersion = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        TemplateVersionSection::factory()->create([
            'template_version_id' => $templateVersion->id,
            'section_version_id' => $sectionVersion->id,
        ]);
        $templateVersion->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);

        $this->expectException(CannotDeleteASectionInUse::class);

        new DestroySection(user: $user, section: $section)->execute();
    }

    /**
     * Nothing has been promised about a draft, so a section sitting only in one
     * goes freely.
     */
    #[Test]
    public function it_deletes_a_section_that_sits_only_in_a_draft(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id]);
        $sectionVersion = SectionVersion::factory()->create(['section_id' => $section->id]);
        $templateVersion = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        TemplateVersionSection::factory()->create([
            'template_version_id' => $templateVersion->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        new DestroySection(user: $user, section: $section)->execute();

        $this->assertSoftDeleted($section);
    }

    #[Test]
    public function it_does_not_release_the_slug_of_a_deleted_section(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = new CreateSection(user: $user, name: 'Address')->execute();

        new DestroySection(user: $user, section: $section)->execute();

        $again = new CreateSection(user: $user, name: 'Address')->execute();

        $this->assertEquals('address-2', $again->slug);
    }

    #[Test]
    public function it_refuses_a_section_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $section = Section::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DestroySection(user: $michael, section: $section)->execute();
    }

    #[Test]
    public function it_writes_the_deletion_to_the_logs(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $section = Section::factory()->create(['account_id' => $user->account_id, 'name' => 'Address']);

        new DestroySection(user: $user, section: $section)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::SectionDeletion
                && $job->parameters === ['name' => 'Address'];
        });
    }
}
