<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateVault;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateVaultTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_updates_the_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
            'description' => 'Everybody at the Scranton branch',
            'colour' => '#aa151b',
        ]);

        $result = new UpdateVault(
            user: $user,
            vault: $vault,
            name: 'Schrute Farms',
            description: 'Beets, bears, bed and breakfast',
            colour: '#012169',
        )->execute();

        $this->assertInstanceOf(Vault::class, $result);

        $vault->refresh();

        $this->assertEquals('Schrute Farms', $vault->name);
        $this->assertEquals('Beets, bears, bed and breakfast', $vault->description);
        $this->assertEquals('#012169', $vault->colour);
    }

    #[Test]
    public function it_leaves_the_place_in_the_list_and_the_default_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->default()->create([
            'account_id' => $user->account_id,
            'position' => 3,
        ]);

        new UpdateVault(user: $user, vault: $vault, name: 'Schrute Farms')->execute();

        $vault->refresh();

        $this->assertSame(3, $vault->position);
        $this->assertTrue($vault->is_default);
    }

    #[Test]
    public function it_strips_the_markup_out_of_what_it_is_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        new UpdateVault(
            user: $user,
            vault: $vault,
            name: '<script>alert("Dwight")</script>Schrute Farms',
        )->execute();

        $this->assertEquals('Schrute Farms', $vault->refresh()->name);
    }

    #[Test]
    public function it_refuses_a_vault_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdateVault(user: $user, vault: $vault, name: 'Schrute Farms')->execute();
    }

    #[Test]
    public function it_logs_what_changed_and_what_it_changed_from(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
            'description' => 'Everybody at the Scranton branch',
            'colour' => '#aa151b',
        ]);

        new UpdateVault(
            user: $user,
            vault: $vault,
            name: 'Schrute Farms',
            description: 'Everybody at the Scranton branch',
            colour: '#aa151b',
        )->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::VaultUpdate
                && $job->account->id === $user->account_id
                && $job->vault->id === $vault->id
                && $job->loggable->is($vault)
                && $job->parameters === ['name' => 'Schrute Farms']
                && $job->changes === [
                    'name' => ['before' => 'Dunder Mifflin', 'after' => 'Schrute Farms'],
                ],
        );
    }

    #[Test]
    public function it_logs_nothing_changed_when_nothing_did(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
            'description' => null,
            'colour' => '#aa151b',
        ]);

        new UpdateVault(
            user: $user,
            vault: $vault,
            name: 'Dunder Mifflin',
            colour: '#aa151b',
        )->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->changes === [],
        );
    }
}
