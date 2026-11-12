<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\AttachPossessionTag;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Possession;
use App\Models\PossessionTag;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AttachPossessionTagTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_puts_a_tag_on_a_possession(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $dundie = Possession::factory()->create([
            'vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id,
            'name' => 'Dundie award',
        ]);
        $fragile = PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'Fragile']);

        $possession = new AttachPossessionTag(user: $user, possession: $dundie, possessionTag: $fragile)->execute();

        $this->assertInstanceOf(Possession::class, $possession);
        $this->assertDatabaseHas('possession_possession_tag', [
            'possession_id' => $dundie->id,
            'possession_tag_id' => $fragile->id,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionUpdate
                && $job->parameters === ['name' => 'Dundie award'],
        );
    }

    #[Test]
    public function it_changes_nothing_when_the_possession_already_carries_the_tag(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $dundie = Possession::factory()->create([
            'vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id,
        ]);
        $fragile = PossessionTag::factory()->create(['account_id' => $user->account_id]);
        $dundie->tags()->attach($fragile->id);

        new AttachPossessionTag(user: $user, possession: $dundie, possessionTag: $fragile)->execute();

        $this->assertSame(1, $dundie->tags()->count());
        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_refuses_a_possession_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $user = User::factory()->create();
        $possession = Possession::factory()->create();
        $tag = PossessionTag::factory()->create(['account_id' => $user->account_id]);

        new AttachPossessionTag(user: $user, possession: $possession, possessionTag: $tag)->execute();
    }

    #[Test]
    public function it_refuses_a_tag_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $user = User::factory()->create();
        $possession = Possession::factory()->create([
            'vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id,
        ]);
        $tag = PossessionTag::factory()->create();

        new AttachPossessionTag(user: $user, possession: $possession, possessionTag: $tag)->execute();
    }
}
