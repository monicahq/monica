<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateStructureVersionPagePosition;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateStructureVersionPagePositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_page_to_the_position_asked_for(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        $general = $structureVersion->pages()->firstOrFail();
        $health = StructureVersionPage::factory()->create(['structure_version_id' => $structureVersion->id, 'label' => 'Health']);
        $work = StructureVersionPage::factory()->create(['structure_version_id' => $structureVersion->id, 'label' => 'Work']);

        new UpdateStructureVersionPagePosition(user: $user, page: $work, position: 1)->execute();

        $this->assertEquals(1, $work->refresh()->position);
        $this->assertEquals(2, $general->refresh()->position);
        $this->assertEquals(3, $health->refresh()->position);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::TemplatePagePositionUpdate
                && $job->parameters['label'] === 'Work',
        );
    }

    #[Test]
    public function it_puts_a_page_last_when_the_position_is_past_the_end(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        $general = $structureVersion->pages()->firstOrFail();
        $health = StructureVersionPage::factory()->create(['structure_version_id' => $structureVersion->id, 'label' => 'Health']);

        new UpdateStructureVersionPagePosition(user: $user, page: $general, position: 99)->execute();

        $this->assertEquals(1, $health->refresh()->position);
        $this->assertEquals(2, $general->refresh()->position);
    }

    #[Test]
    public function it_leaves_the_pages_of_another_version_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $structureVersion = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = StructureVersion::factory()->create(['account_id' => $user->account_id]);
        $untouched = StructureVersionPage::factory()->create(['structure_version_id' => $elsewhere->id, 'position' => 7]);
        $page = StructureVersionPage::factory()->create(['structure_version_id' => $structureVersion->id]);

        new UpdateStructureVersionPagePosition(user: $user, page: $page, position: 1)->execute();

        $this->assertEquals(7, $untouched->refresh()->position);
    }

    #[Test]
    public function it_refuses_a_page_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $page = StructureVersionPage::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdateStructureVersionPagePosition(user: $user, page: $page, position: 1)->execute();
    }
}
