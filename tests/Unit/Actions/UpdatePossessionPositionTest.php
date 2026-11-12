<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdatePossessionPosition;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Possession;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdatePossessionPositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_a_featured_possession_and_numbers_the_others_again(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $sebring = Possession::factory()->featured(1)->create(['vault_id' => $vault->id]);
        $shredder = Possession::factory()->featured(2)->create(['vault_id' => $vault->id]);
        $mug = Possession::factory()->featured(3)->create(['vault_id' => $vault->id, 'name' => 'World’s Best Boss mug']);

        new UpdatePossessionPosition(user: $user, possession: $mug, position: 1)->execute();

        $this->assertSame(1, $mug->refresh()->position);
        $this->assertSame(2, $sebring->refresh()->position);
        $this->assertSame(3, $shredder->refresh()->position);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PossessionUpdate
                && $job->parameters === ['name' => 'World’s Best Boss mug'],
        );
    }

    #[Test]
    public function it_places_a_possession_asked_beyond_the_end_last(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $sebring = Possession::factory()->featured(1)->create(['vault_id' => $vault->id]);
        Possession::factory()->featured(2)->create(['vault_id' => $vault->id]);

        new UpdatePossessionPosition(user: $user, possession: $sebring, position: 40)->execute();

        $this->assertSame(2, $sebring->refresh()->position);
    }

    #[Test]
    public function it_refuses_a_possession_that_is_not_featured(): void
    {
        $user = User::factory()->create();
        $possession = Possession::factory()->create([
            'vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id,
        ]);

        $this->expectException(ValidationException::class);

        new UpdatePossessionPosition(user: $user, possession: $possession, position: 1)->execute();
    }

    #[Test]
    public function it_refuses_a_possession_of_another_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        new UpdatePossessionPosition(
            user: User::factory()->create(),
            possession: Possession::factory()->featured(1)->create(),
            position: 1,
        )->execute();
    }
}
