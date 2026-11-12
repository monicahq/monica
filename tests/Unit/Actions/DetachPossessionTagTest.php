<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DetachPossessionTag;
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

class DetachPossessionTagTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_takes_a_tag_off_a_possession_and_keeps_the_tag(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $shredder = Possession::factory()->create([
            'vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id,
            'name' => 'Paper shredder',
        ]);
        $toSell = PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'To sell']);
        $shredder->tags()->attach($toSell->id);

        $possession = new DetachPossessionTag(user: $user, possession: $shredder, possessionTag: $toSell)->execute();

        $this->assertInstanceOf(Possession::class, $possession);
        $this->assertDatabaseMissing('possession_possession_tag', [
            'possession_id' => $shredder->id,
            'possession_tag_id' => $toSell->id,
        ]);
        $this->assertModelExists($toSell);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionUpdate
                && $job->parameters === ['name' => 'Paper shredder'],
        );
    }

    #[Test]
    public function it_changes_nothing_when_the_possession_does_not_carry_the_tag(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $shredder = Possession::factory()->create([
            'vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id,
        ]);
        $toSell = PossessionTag::factory()->create(['account_id' => $user->account_id]);

        new DetachPossessionTag(user: $user, possession: $shredder, possessionTag: $toSell)->execute();

        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_refuses_a_possession_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $user = User::factory()->create();
        $possession = Possession::factory()->create();
        $tag = PossessionTag::factory()->create(['account_id' => $user->account_id]);

        new DetachPossessionTag(user: $user, possession: $possession, possessionTag: $tag)->execute();
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

        new DetachPossessionTag(user: $user, possession: $possession, possessionTag: $tag)->execute();
    }
}
