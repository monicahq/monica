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

        Queue::assertPushedOn('low', LogAdministratorAction::class);
    }

    #[Test]
    public function it_refuses_to_let_an_administrator_lock_themselves_out(): void
    {
        Queue::fake();

        $michael = User::factory()->create();

        $refused = false;

        try {
            new DeactivateUser(user: $michael, administrator: $michael)->execute();
        } catch (AdministratorCannotLockThemselvesOut) {
            $refused = true;
        }

        $this->assertTrue($refused);
        $this->assertTrue($michael->refresh()->is_active);
    }
}
