<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateTemplateVersionPage;
use App\Enums\UserActionEnum;
use App\Enums\VersionStatusEnum;
use App\Exceptions\PageLabelIsAlreadyUsed;
use App\Exceptions\PublishedVersionIsFrozen;
use App\Jobs\LogUserAction;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateTemplateVersionPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_renames_a_page(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $templateVersion = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        $page = TemplateVersionPage::factory()->create([
            'template_version_id' => $templateVersion->id,
            'key' => 'health',
            'label' => 'Health',
        ]);

        $result = new UpdateTemplateVersionPage(
            user: $user,
            page: $page,
            label: 'Medical',
        )->execute();

        $this->assertInstanceOf(TemplateVersionPage::class, $result);

        $this->assertDatabaseHas('template_version_pages', [
            'id' => $page->id,
            'key' => 'health',
            'label' => 'Medical',
        ]);
    }

    #[Test]
    public function it_gives_a_supplied_page_the_words_of_whoever_renamed_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $templateVersion = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        $page = $templateVersion->pages()->firstOrFail();

        new UpdateTemplateVersionPage(user: $user, page: $page, label: 'Everything else')->execute();

        $this->assertDatabaseHas('template_version_pages', [
            'id' => $page->id,
            'label' => 'Everything else',
            'label_translation_key' => null,
        ]);
    }

    #[Test]
    public function it_refuses_a_label_another_page_of_the_version_already_reads(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $templateVersion = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        $page = TemplateVersionPage::factory()->create([
            'template_version_id' => $templateVersion->id,
            'label' => 'Health',
        ]);

        $this->expectException(PageLabelIsAlreadyUsed::class);

        new UpdateTemplateVersionPage(user: $user, page: $page, label: 'General')->execute();
    }

    #[Test]
    public function it_lets_a_page_keep_its_own_label(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $templateVersion = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        $page = TemplateVersionPage::factory()->create([
            'template_version_id' => $templateVersion->id,
            'label' => 'Health',
        ]);

        $result = new UpdateTemplateVersionPage(user: $user, page: $page, label: 'Health')->execute();

        $this->assertEquals('Health', $result->label);
    }

    #[Test]
    public function it_refuses_a_page_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $page = TemplateVersionPage::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdateTemplateVersionPage(user: $user, page: $page, label: 'Health')->execute();
    }

    #[Test]
    public function it_refuses_to_rename_a_page_of_a_published_template_version(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $templateVersion = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        $page = $templateVersion->pages()->firstOrFail();
        $templateVersion->update(['status' => VersionStatusEnum::Published, 'published_at' => now()]);
        $page->refresh();

        $this->expectException(PublishedVersionIsFrozen::class);

        new UpdateTemplateVersionPage(user: $user, page: $page, label: 'Health')->execute();
    }

    #[Test]
    public function it_logs_the_rename(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $templateVersion = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        $page = $templateVersion->pages()->firstOrFail();

        new UpdateTemplateVersionPage(user: $user, page: $page, label: 'Medical')->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::TemplatePageUpdate
                && $job->account->id === $user->account_id
                && $job->parameters['label'] === 'Medical',
        );
    }
}
