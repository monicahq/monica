<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\StartSearchIndexRebuild;
use App\Enums\AdministratorActionEnum;
use App\Jobs\LogAdministratorAction;
use App\Jobs\RebuildSearchIndex;
use App\Models\LifeEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StartSearchIndexRebuildTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_queues_the_rebuild_of_the_index_and_writes_it_down(): void
    {
        Queue::fake();
        $michael = User::factory()->create();

        new StartSearchIndexRebuild(
            kind: 'life-events',
            administrator: $michael,
        )->execute();

        Queue::assertPushedOn('low', RebuildSearchIndex::class, fn (RebuildSearchIndex $job): bool => $job->model === LifeEvent::class);
        Queue::assertPushedOn('low', LogAdministratorAction::class, fn (LogAdministratorAction $job): bool => $job->action === AdministratorActionEnum::SearchIndexRebuildStarted
            && $job->administrator?->is($michael) === true
            && $job->parameters === ['index' => 'life_events']);
    }

    #[Test]
    public function it_refuses_an_index_that_does_not_exist(): void
    {
        Queue::fake();

        $this->expectException(ModelNotFoundException::class);

        try {
            new StartSearchIndexRebuild(kind: 'dundie-awards')->execute();
        } finally {
            Queue::assertNothingPushed();
        }
    }
}
