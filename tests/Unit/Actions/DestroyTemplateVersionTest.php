<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyTemplateVersion;
use App\Enums\UserActionEnum;
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
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyTemplateVersionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_throws_away_a_draft(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);

        new DestroyTemplateVersion(user: $user, templateVersion: $version)->execute();

        $this->assertModelMissing($version);
    }

    #[Test]
    public function it_takes_the_assembly_with_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $row = TemplateVersionSection::factory()->create(['template_version_id' => $version->id]);

        new DestroyTemplateVersion(user: $user, templateVersion: $version)->execute();

        $this->assertModelMissing($row);
    }

    #[Test]
    public function it_leaves_each_section_in_one_template_version_fewer(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);
        $sectionVersion = SectionVersion::factory()->published()->used(3)->create(['account_id' => $user->account_id]);
        TemplateVersionSection::factory()->create([
            'template_version_id' => $version->id,
            'section_version_id' => $sectionVersion->id,
        ]);

        new DestroyTemplateVersion(user: $user, templateVersion: $version)->execute();

        $this->assertEquals(2, $sectionVersion->fresh()->usage_count);
    }

    /**
     * The column is unsigned, so a count that has drifted out of step must not
     * be taken below zero.
     */
    #[Test]
    public function it_never_takes_the_count_below_zero(): void
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

        new DestroyTemplateVersion(user: $user, templateVersion: $version)->execute();

        $this->assertEquals(0, $sectionVersion->fresh()->usage_count);
    }

    #[Test]
    public function it_refuses_to_throw_away_a_published_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);

        $this->expectException(PublishedVersionIsFrozen::class);

        new DestroyTemplateVersion(user: $user, templateVersion: $version)->execute();
    }

    #[Test]
    public function it_refuses_a_version_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $version = TemplateVersion::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DestroyTemplateVersion(user: $michael, templateVersion: $version)->execute();
    }

    #[Test]
    public function it_writes_the_deletion_to_the_logs(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Contact']);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);

        new DestroyTemplateVersion(user: $user, templateVersion: $version)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::TemplateVersionDeletion
                && $job->parameters === ['name' => 'Contact', 'version' => 1];
        });
    }
}
