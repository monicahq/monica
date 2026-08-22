<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ActivateUser;
use App\Jobs\LogAdministratorAction;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivateUserTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lets_somebody_sign_in_again(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $todd = User::factory()->create(['is_active' => false]);

        new ActivateUser(user: $todd, administrator: $michael)->execute();

        $this->assertTrue($todd->refresh()->is_active);
    }

    #[Test]
    public function it_leaves_everything_they_owned_where_it_was(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $todd = User::factory()->create(['is_active' => false]);
        $vault = Vault::factory()->create(['account_id' => $todd->account_id, 'name' => 'Schrute Farms']);

        new ActivateUser(user: $todd, administrator: $michael)->execute();

        $this->assertDatabaseHas('vaults', ['id' => $vault->id, 'name' => 'Schrute Farms']);
    }

    #[Test]
    public function it_writes_the_reactivation_down(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $todd = User::factory()->create(['is_active' => false]);

        new ActivateUser(user: $todd, administrator: $michael)->execute();

        Queue::assertPushedOn('low', LogAdministratorAction::class);
    }

    #[Test]
    public function it_lets_an_administrator_reactivate_themselves_since_that_locks_nobody_out(): void
    {
        Queue::fake();

        $michael = User::factory()->create(['is_active' => false]);

        new ActivateUser(user: $michael, administrator: $michael)->execute();

        $this->assertTrue($michael->refresh()->is_active);
    }
}
