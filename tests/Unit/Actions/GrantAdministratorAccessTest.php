<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\GrantAdministratorAccess;
use App\Exceptions\AdministratorCapacityIsAlreadyHeld;
use App\Jobs\LogAdministratorAction;
use App\Models\Administrator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GrantAdministratorAccessTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lets_somebody_run_the_instance(): void
    {
        Queue::fake();

        $dwight = User::factory()->create();

        $capacity = new GrantAdministratorAccess(user: $dwight)->execute();

        $this->assertInstanceOf(Administrator::class, $capacity);
        $this->assertDatabaseHas('administrators', ['user_id' => $dwight->id]);
        $this->assertTrue($dwight->refresh()->isAdministrator());
    }

    #[Test]
    public function it_records_which_administrator_granted_it(): void
    {
        Queue::fake();

        $michael = User::factory()->create();
        $dwight = User::factory()->create();

        new GrantAdministratorAccess(user: $dwight, administrator: $michael)->execute();

        $this->assertDatabaseHas('administrators', [
            'user_id' => $dwight->id,
            'granted_by_user_id' => $michael->id,
        ]);
    }

    #[Test]
    public function it_writes_the_grant_down(): void
    {
        Queue::fake();

        $dwight = User::factory()->create();

        new GrantAdministratorAccess(user: $dwight)->execute();

        Queue::assertPushedOn('low', LogAdministratorAction::class);
    }

    #[Test]
    public function it_refuses_to_grant_a_capacity_already_held(): void
    {
        Queue::fake();

        $dwight = User::factory()->create();
        Administrator::factory()->create(['user_id' => $dwight->id]);

        $this->expectException(AdministratorCapacityIsAlreadyHeld::class);

        new GrantAdministratorAccess(user: $dwight)->execute();
    }

    #[Test]
    public function it_grants_it_to_somebody_who_cannot_sign_in_without_letting_them_in(): void
    {
        Queue::fake();

        $dwight = User::factory()->create(['is_active' => false]);

        new GrantAdministratorAccess(user: $dwight)->execute();

        $this->assertDatabaseHas('administrators', ['user_id' => $dwight->id]);
        $this->assertFalse($dwight->refresh()->isAdministrator());
    }
}
