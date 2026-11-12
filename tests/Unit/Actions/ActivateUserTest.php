<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ActivateUser;
use App\Jobs\LogAdministratorAction;
use App\Models\User;
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
