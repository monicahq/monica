<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RetireTemplateVersion;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Jobs\LogUserAction;
use App\Models\Template;
use App\Models\TemplateVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RetireTemplateVersionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_stops_a_version_being_offered(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);

        new RetireTemplateVersion(user: $user, templateVersion: $version)->execute();

        $version->refresh();

        $this->assertEquals(VersionStatusEnum::Retired, $version->status);
        $this->assertNotNull($version->retired_at);
    }

    /**
     * Retiring is neither deleting nor editing: every record already on the
     * version stays on it and renders what it always did.
     */
    #[Test]
    public function it_leaves_the_records_already_on_it_where_they_are(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->published()->used(214)->create(['template_id' => $template->id]);

        new RetireTemplateVersion(user: $user, templateVersion: $version)->execute();

        $this->assertEquals(214, $version->fresh()->record_count);
    }

    #[Test]
    public function it_stops_the_template_pointing_at_a_retired_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);
        $template->update(['published_version_id' => $version->id]);

        new RetireTemplateVersion(user: $user, templateVersion: $version)->execute();

        $this->assertNull($template->fresh()->published_version_id);
    }

    #[Test]
    public function it_leaves_another_version_as_the_one_the_template_offers(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $old = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);
        $current = TemplateVersion::factory()->published()->create(['template_id' => $template->id, 'version_number' => 2]);
        $template->update(['published_version_id' => $current->id]);

        new RetireTemplateVersion(user: $user, templateVersion: $old)->execute();

        $this->assertEquals($current->id, $template->fresh()->published_version_id);
    }

    #[Test]
    public function it_refuses_to_retire_a_draft(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id]);
        $version = TemplateVersion::factory()->create(['template_id' => $template->id]);

        $this->expectException(InvalidArgumentException::class);

        new RetireTemplateVersion(user: $user, templateVersion: $version)->execute();
    }

    #[Test]
    public function it_refuses_a_version_from_another_account(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $version = TemplateVersion::factory()->published()->create();

        $this->expectException(ModelNotFoundException::class);

        new RetireTemplateVersion(user: $michael, templateVersion: $version)->execute();
    }

    #[Test]
    public function it_writes_the_retirement_to_the_logs(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $template = Template::factory()->create(['account_id' => $user->account_id, 'name' => 'Contact']);
        $version = TemplateVersion::factory()->published()->create(['template_id' => $template->id]);

        new RetireTemplateVersion(user: $user, templateVersion: $version)->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::TemplateVersionRetired
                && $job->parameters === ['name' => 'Contact', 'version' => 1];
        });
    }
}
