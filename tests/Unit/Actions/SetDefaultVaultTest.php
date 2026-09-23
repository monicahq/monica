<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\SetDefaultVault;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SetDefaultVaultTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_makes_the_vault_the_one_records_land_in(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $held = Vault::factory()->default()->create(['account_id' => $user->account_id]);
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Schrute Farms',
        ]);

        $result = new SetDefaultVault(user: $user, vault: $vault)->execute();

        $this->assertInstanceOf(Vault::class, $result);
        $this->assertTrue($vault->refresh()->is_default);
        $this->assertFalse($held->refresh()->is_default);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::VaultDefaultSet
                && $job->account->id === $user->account_id
                && $job->vault->id === $vault->id
                && $job->parameters === ['name' => 'Schrute Farms'],
        );
    }

    #[Test]
    public function it_leaves_the_vaults_of_other_accounts_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $elsewhere = Vault::factory()->default()->create();

        new SetDefaultVault(user: $user, vault: $vault)->execute();

        $this->assertTrue($elsewhere->refresh()->is_default);
    }

    #[Test]
    public function it_refuses_a_vault_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new SetDefaultVault(user: $user, vault: $vault)->execute();
    }

    #[Test]
    public function it_refuses_a_deleted_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $vault->delete();

        $this->expectException(ModelNotFoundException::class);

        new SetDefaultVault(user: $user, vault: $vault)->execute();
    }
}
