<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateTemplateVersionPagePosition;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\TemplateVersion;
use App\Models\TemplateVersionPage;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateTemplateVersionPagePositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_page_to_the_position_asked_for(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $templateVersion = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        $general = $templateVersion->pages()->firstOrFail();
        $health = TemplateVersionPage::factory()->create(['template_version_id' => $templateVersion->id, 'label' => 'Health']);
        $work = TemplateVersionPage::factory()->create(['template_version_id' => $templateVersion->id, 'label' => 'Work']);

        new UpdateTemplateVersionPagePosition(user: $user, page: $work, position: 1)->execute();

        $this->assertEquals(1, $work->refresh()->position);
        $this->assertEquals(2, $general->refresh()->position);
        $this->assertEquals(3, $health->refresh()->position);
    }

    #[Test]
    public function it_puts_a_page_last_when_the_position_is_past_the_end(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $templateVersion = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        $general = $templateVersion->pages()->firstOrFail();
        $health = TemplateVersionPage::factory()->create(['template_version_id' => $templateVersion->id, 'label' => 'Health']);

        new UpdateTemplateVersionPagePosition(user: $user, page: $general, position: 99)->execute();

        $this->assertEquals(1, $health->refresh()->position);
        $this->assertEquals(2, $general->refresh()->position);
    }

    #[Test]
    public function it_leaves_the_pages_of_another_version_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $templateVersion = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        $untouched = TemplateVersionPage::factory()->create(['template_version_id' => $elsewhere->id, 'position' => 7]);
        $page = TemplateVersionPage::factory()->create(['template_version_id' => $templateVersion->id]);

        new UpdateTemplateVersionPagePosition(user: $user, page: $page, position: 1)->execute();

        $this->assertEquals(7, $untouched->refresh()->position);
    }

    #[Test]
    public function it_refuses_a_page_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $page = TemplateVersionPage::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdateTemplateVersionPagePosition(user: $user, page: $page, position: 1)->execute();
    }

    #[Test]
    public function it_logs_the_move(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $templateVersion = TemplateVersion::factory()->create(['account_id' => $user->account_id]);
        $page = TemplateVersionPage::factory()->create(['template_version_id' => $templateVersion->id, 'label' => 'Health']);

        new UpdateTemplateVersionPagePosition(user: $user, page: $page, position: 1)->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::TemplatePagePositionUpdate
                && $job->parameters['label'] === 'Health',
        );
    }
}
