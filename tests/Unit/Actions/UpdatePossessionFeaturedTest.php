<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdatePossessionFeatured;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Possession;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdatePossessionFeaturedTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_features_a_possession_last_among_the_featured_ones(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Possession::factory()->featured(1)->create(['vault_id' => $vault->id]);
        Possession::factory()->featured(2)->create(['vault_id' => $vault->id]);
        $dundie = Possession::factory()->create(['vault_id' => $vault->id, 'name' => 'Dundie award']);

        new UpdatePossessionFeatured(user: $user, possession: $dundie, isFeatured: true)->execute();

        $this->assertDatabaseHas('possessions', ['id' => $dundie->id, 'is_featured' => true, 'position' => 3]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionUpdate
                && $job->parameters === ['name' => 'Dundie award'],
        );
    }

    #[Test]
    public function it_stops_featuring_a_possession_and_closes_the_order(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dundie = Possession::factory()->featured(1)->create(['vault_id' => $vault->id]);
        $mug = Possession::factory()->featured(2)->create(['vault_id' => $vault->id]);

        new UpdatePossessionFeatured(user: $user, possession: $dundie, isFeatured: false)->execute();

        $this->assertDatabaseHas('possessions', ['id' => $dundie->id, 'is_featured' => false, 'position' => null]);
        $this->assertSame(1, $mug->refresh()->position);
    }

    #[Test]
    public function it_changes_nothing_when_the_possession_is_already_as_asked(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $dundie = Possession::factory()->featured(1)->create([
            'vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id,
        ]);

        new UpdatePossessionFeatured(user: $user, possession: $dundie, isFeatured: true)->execute();

        $this->assertSame(1, $dundie->refresh()->position);
        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_refuses_a_possession_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $user = User::factory()->create();
        $possession = Possession::factory()->create();

        new UpdatePossessionFeatured(user: $user, possession: $possession, isFeatured: true)->execute();
    }
}
