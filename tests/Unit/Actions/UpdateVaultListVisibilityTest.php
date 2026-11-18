<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateVaultListVisibility;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateVaultListVisibilityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_hides_the_list_of_vaults(): void
    {
        Queue::fake();

        $user = User::factory()->create(['first_name' => 'Michael', 'shows_vault_list' => true]);

        $result = new UpdateVaultListVisibility(
            user: $user,
            showsVaultList: false,
        )->execute();

        $this->assertInstanceOf(User::class, $result);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'shows_vault_list' => false,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::VaultListVisibilityUpdate
                && $job->account->id === $user->account_id
                && $job->user->id === $user->id,
        );
    }

    #[Test]
    public function it_shows_the_list_of_vaults_again(): void
    {
        Queue::fake();

        $user = User::factory()->create(['first_name' => 'Michael', 'shows_vault_list' => false]);

        new UpdateVaultListVisibility(
            user: $user,
            showsVaultList: true,
        )->execute();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'shows_vault_list' => true,
        ]);
    }
}
