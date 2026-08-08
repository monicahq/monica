<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateVaultPosition;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateVaultPositionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_the_vault_to_the_position_it_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $first = Vault::factory()->create(['account_id' => $user->account_id, 'position' => 1]);
        $second = Vault::factory()->create(['account_id' => $user->account_id, 'position' => 2]);
        $third = Vault::factory()->create(['account_id' => $user->account_id, 'position' => 3]);

        $result = new UpdateVaultPosition(user: $user, vault: $third, position: 1)->execute();

        $this->assertInstanceOf(Vault::class, $result);
        $this->assertSame(1, $third->refresh()->position);
        $this->assertSame(2, $first->refresh()->position);
        $this->assertSame(3, $second->refresh()->position);
    }

    #[Test]
    public function it_puts_the_vault_last_when_the_position_is_past_the_end(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $first = Vault::factory()->create(['account_id' => $user->account_id, 'position' => 1]);
        $second = Vault::factory()->create(['account_id' => $user->account_id, 'position' => 2]);

        new UpdateVaultPosition(user: $user, vault: $first, position: 12)->execute();

        $this->assertSame(1, $second->refresh()->position);
        $this->assertSame(2, $first->refresh()->position);
    }

    #[Test]
    public function it_numbers_every_vault_of_the_account_from_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $first = Vault::factory()->create(['account_id' => $user->account_id, 'position' => 4]);
        $second = Vault::factory()->create(['account_id' => $user->account_id, 'position' => 9]);

        new UpdateVaultPosition(user: $user, vault: $second, position: 1)->execute();

        $this->assertSame(1, $second->refresh()->position);
        $this->assertSame(2, $first->refresh()->position);
    }

    #[Test]
    public function it_leaves_the_vaults_of_other_accounts_where_they_are(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'position' => 1]);
        $elsewhere = Vault::factory()->create(['position' => 7]);

        new UpdateVaultPosition(user: $user, vault: $vault, position: 1)->execute();

        $this->assertSame(7, $elsewhere->refresh()->position);
    }

    #[Test]
    public function it_refuses_a_vault_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdateVaultPosition(user: $user, vault: $vault, position: 1)->execute();
    }

    #[Test]
    public function it_logs_the_move(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Schrute Farms',
        ]);

        new UpdateVaultPosition(user: $user, vault: $vault, position: 1)->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::VaultPositionUpdate
                && $job->account->id === $user->account_id
                && $job->vault->id === $vault->id
                && $job->parameters === ['name' => 'Schrute Farms'],
        );
    }
}
