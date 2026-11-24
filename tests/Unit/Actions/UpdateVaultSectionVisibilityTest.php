<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateVaultSectionVisibility;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateVaultSectionVisibilityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_hides_the_sections_it_is_told_to_hide(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin']);

        $result = new UpdateVaultSectionVisibility(
            user: $user,
            vault: $vault,
            showsPeople: true,
            showsThings: false,
            showsKnowledge: false,
        )->execute();

        $this->assertInstanceOf(Vault::class, $result);

        $this->assertDatabaseHas('vaults', [
            'id' => $vault->id,
            'shows_people' => true,
            'shows_things' => false,
            'shows_knowledge' => false,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::VaultSectionVisibilityUpdate
                && $job->vault->id === $vault->id
                && $job->parameters === ['name' => 'Dunder Mifflin']
                && $job->changes === [
                    'shows_things' => ['before' => true, 'after' => false],
                    'shows_knowledge' => ['before' => true, 'after' => false],
                ],
        );
    }

    #[Test]
    public function it_shows_a_hidden_section_again(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'shows_people' => false]);

        new UpdateVaultSectionVisibility(
            user: $user,
            vault: $vault->refresh(),
            showsPeople: true,
            showsThings: true,
            showsKnowledge: true,
        )->execute();

        $this->assertDatabaseHas('vaults', [
            'id' => $vault->id,
            'shows_people' => true,
        ]);
    }

    #[Test]
    public function it_refuses_a_vault_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdateVaultSectionVisibility(
            user: $user,
            vault: $vault,
            showsPeople: false,
            showsThings: false,
            showsKnowledge: false,
        )->execute();
    }
}
