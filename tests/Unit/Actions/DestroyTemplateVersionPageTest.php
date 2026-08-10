<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyTemplateVersionPage;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\CannotDeleteAPageInUse;
use App\Exceptions\CannotDeleteTheLastPage;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Jobs\LogUserAction;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use App\Models\TemplateVersionSection;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyTemplateVersionPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_takes_a_page_off_a_template_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $templateVersion = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        $page = TemplateVersionPage::factory()->create(['template_version_id' => $templateVersion->id]);

        new DestroyTemplateVersionPage(user: $user, page: $page)->execute();

        $this->assertModelMissing($page);
    }

    #[Test]
    public function it_numbers_the_pages_left_from_one_again(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $templateVersion = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        $general = $templateVersion->pages()->firstOrFail();
        $health = TemplateVersionPage::factory()->create(['template_version_id' => $templateVersion->id]);
        $work = TemplateVersionPage::factory()->create(['template_version_id' => $templateVersion->id]);

        new DestroyTemplateVersionPage(user: $user, page: $health)->execute();

        $this->assertEquals(1, $general->refresh()->position);
        $this->assertEquals(2, $work->refresh()->position);
    }

    #[Test]
    public function it_refuses_to_take_the_last_page_of_a_template_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $templateVersion = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        $page = $templateVersion->pages()->firstOrFail();

        $this->expectException(CannotDeleteTheLastPage::class);

        new DestroyTemplateVersionPage(user: $user, page: $page)->execute();
    }

    #[Test]
    public function it_refuses_to_take_a_page_that_still_holds_sections(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $templateVersion = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        $page = TemplateVersionPage::factory()->create(['template_version_id' => $templateVersion->id]);
        TemplateVersionSection::factory()->create([
            'template_version_id' => $templateVersion->id,
            'template_version_page_id' => $page->id,
        ]);

        $this->expectException(CannotDeleteAPageInUse::class);

        new DestroyTemplateVersionPage(user: $user, page: $page)->execute();
    }

    #[Test]
    public function it_refuses_a_page_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $page = TemplateVersionPage::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DestroyTemplateVersionPage(user: $user, page: $page)->execute();
    }

    #[Test]
    public function it_refuses_to_take_a_page_off_a_published_template_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $templateVersion = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        $page = TemplateVersionPage::factory()->create(['template_version_id' => $templateVersion->id]);
        $templateVersion->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);
        $page->refresh();

        $this->expectException(PublishedVersionIsFrozen::class);

        new DestroyTemplateVersionPage(user: $user, page: $page)->execute();
    }

    #[Test]
    public function it_logs_the_page_being_taken_off(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $templateVersion = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        $page = TemplateVersionPage::factory()->create([
            'template_version_id' => $templateVersion->id,
            'label' => 'Health',
        ]);

        new DestroyTemplateVersionPage(user: $user, page: $page)->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::TemplatePageDeletion
                && $job->parameters['label'] === 'Health',
        );
    }
}
