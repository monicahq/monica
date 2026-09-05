<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DeactivateUser;
use App\Exceptions\AdministratorCannotLockThemselvesOut;
use App\Jobs\LogAdministratorAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeactivateUserTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_stops_somebody_signing_in(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $todd = User::factory()->create(['is_active' => true]);

        new DeactivateUser(user: $todd, administrator: $michael)->execute();

        $this->assertFalse($todd->refresh()->is_active);
    }

    #[Test]
    public function it_deletes_nothing_the_person_owns(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $todd = User::factory()->create();
        $accountId = $todd->account_id;

        new DeactivateUser(user: $todd, administrator: $michael)->execute();

        $this->assertDatabaseHas('accounts', ['id' => $accountId]);
        $this->assertDatabaseHas('users', ['id' => $todd->id, 'deleted_at' => null]);
    }

    #[Test]
    public function it_writes_the_deactivation_down(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $todd = User::factory()->create();

        new DeactivateUser(user: $todd, administrator: $michael)->execute();

        Queue::assertPushedOn('low', LogAdministratorAction::class);
    }

    #[Test]
    public function it_refuses_to_let_an_administrator_lock_themselves_out(): void
    {
        Queue::fake();

        $michael = User::factory()->create();

        $this->expectException(AdministratorCannotLockThemselvesOut::class);

        new DeactivateUser(user: $michael, administrator: $michael)->execute();
    }

    #[Test]
    public function it_leaves_an_administrator_who_tried_to_deactivate_themselves_active(): void
    {
        Queue::fake();

        $michael = User::factory()->create();

        try {
            new DeactivateUser(user: $michael, administrator: $michael)->execute();
        } catch (AdministratorCannotLockThemselvesOut) {
        }

        $this->assertTrue($michael->refresh()->is_active);
    }
}
