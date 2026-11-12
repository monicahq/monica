<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\WithdrawAdministratorAccess;
use App\Exceptions\AdministratorCannotLockThemselvesOut;
use App\Exceptions\InstanceMustKeepAnAdministrator;
use App\Jobs\LogAdministratorAction;
use App\Models\Administrator;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WithdrawAdministratorAccessTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_closes_the_admin_space_to_somebody(): void
    {
        Queue::fake();

        $michael = $this->administrator();
        $dwight = $this->administrator();

        new WithdrawAdministratorAccess(user: $dwight, administrator: $michael)->execute();

        $this->assertDatabaseMissing('administrators', ['user_id' => $dwight->id]);
        $this->assertFalse($dwight->refresh()->isAdministrator());

        Queue::assertPushedOn('low', LogAdministratorAction::class);
    }

    #[Test]
    public function it_leaves_the_account_of_the_person_it_was_taken_from_alone(): void
    {
        Queue::fake();

        $michael = $this->administrator();
        $dwight = $this->administrator();

        new WithdrawAdministratorAccess(user: $dwight, administrator: $michael)->execute();

        $this->assertDatabaseHas('users', ['id' => $dwight->id, 'is_active' => true]);
        $this->assertDatabaseHas('accounts', ['id' => $dwight->account_id]);
    }

    #[Test]
    public function it_refuses_to_let_an_administrator_take_it_off_themselves(): void
    {
        Queue::fake();

        $michael = $this->administrator();
        $this->administrator();

        $refused = false;

        try {
            new WithdrawAdministratorAccess(user: $michael, administrator: $michael)->execute();
        } catch (AdministratorCannotLockThemselvesOut) {
            $refused = true;
        }

        $this->assertTrue($refused);
        $this->assertTrue($michael->refresh()->isAdministrator());
    }

    #[Test]
    public function it_refuses_to_leave_the_instance_with_nobody(): void
    {
        Queue::fake();

        $michael = $this->administrator();
        $dwight = $this->administrator();

        new WithdrawAdministratorAccess(user: $dwight, administrator: $michael)->execute();

        $jim = User::factory()->create();

        $refused = false;

        try {
            new WithdrawAdministratorAccess(user: $michael, administrator: $jim)->execute();
        } catch (InstanceMustKeepAnAdministrator) {
            $refused = true;
        }

        $this->assertTrue($refused);
        $this->assertDatabaseCount('administrators', 1);
    }

    #[Test]
    public function it_has_nothing_to_withdraw_from_somebody_who_never_had_it(): void
    {
        Queue::fake();

        $michael = $this->administrator();
        $this->administrator();
        $jim = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new WithdrawAdministratorAccess(user: $jim, administrator: $michael)->execute();
    }

    private function administrator(): User
    {
        $user = User::factory()->create();
        Administrator::factory()->create(['user_id' => $user->id]);

        return $user;
    }
}
