<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyPossessionTag;
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

class DestroyPossessionTagTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_tag(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $possessionTag = PossessionTag::factory()->create(['account_id' => $user->account_id, 'name' => 'Needs repair']);

        new DestroyPossessionTag(user: $user, possessionTag: $possessionTag)->execute();

        $this->assertModelMissing($possessionTag);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionTagDeletion
                && $job->parameters === ['name' => 'Needs repair'],
        );
    }

    #[Test]
    public function it_refuses_a_tag_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new DestroyPossessionTag(
            user: User::factory()->create(),
            possessionTag: PossessionTag::factory()->create(),
        )->execute();
    }

    #[Test]
    public function the_possessions_carrying_it_lose_it_and_nothing_else(): void
    {
        $user = User::factory()->create();
        $possessionTag = PossessionTag::factory()->create(['account_id' => $user->account_id]);
        $possession = Possession::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);
        $possession->tags()->attach($possessionTag);

        new DestroyPossessionTag(user: $user, possessionTag: $possessionTag)->execute();

        $this->assertModelExists($possession);
        $this->assertCount(0, $possession->fresh()->tags);
    }
}
