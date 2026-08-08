<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyVault;
use App\Enums\UserActionEnum;
use App\Exceptions\CannotDeleteTheLastVault;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyVaultTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_the_vault_softly(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Vault::factory()->default()->create(['account_id' => $user->account_id]);

        new DestroyVault(user: $user, vault: $vault)->execute();

        $this->assertSoftDeleted('vaults', ['id' => $vault->id]);
    }

    #[Test]
    public function it_refuses_to_delete_the_last_vault_of_an_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->default()->create(['account_id' => $user->account_id]);
        Vault::factory()->create();

        $this->expectException(CannotDeleteTheLastVault::class);

        new DestroyVault(user: $user, vault: $vault)->execute();
    }

    #[Test]
    public function it_hands_the_default_over_to_the_first_vault_left(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->default()->create([
            'account_id' => $user->account_id,
            'position' => 1,
        ]);
        $next = Vault::factory()->create(['account_id' => $user->account_id, 'position' => 2]);
        $last = Vault::factory()->create(['account_id' => $user->account_id, 'position' => 3]);

        new DestroyVault(user: $user, vault: $vault)->execute();

        $this->assertFalse($vault->refresh()->is_default);
        $this->assertTrue($next->refresh()->is_default);
        $this->assertFalse($last->refresh()->is_default);
    }

    #[Test]
    public function it_leaves_the_default_where_it_is_when_another_vault_goes(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $default = Vault::factory()->default()->create(['account_id' => $user->account_id]);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        new DestroyVault(user: $user, vault: $vault)->execute();

        $this->assertTrue($default->refresh()->is_default);
    }

    #[Test]
    public function it_refuses_a_vault_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        Vault::factory()->create(['account_id' => $user->account_id]);
        $vault = Vault::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DestroyVault(user: $user, vault: $vault)->execute();
    }

    #[Test]
    public function it_logs_the_deletion_with_the_name_the_vault_had(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Schrute Farms',
        ]);
        Vault::factory()->default()->create(['account_id' => $user->account_id]);

        new DestroyVault(user: $user, vault: $vault)->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::VaultDeletion
                && $job->account->id === $user->account_id
                && $job->vault->id === $vault->id
                && $job->parameters === ['name' => 'Schrute Farms'],
        );
    }
}
