<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateTimezone;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateTimezoneTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_updates_the_timezone_of_a_user(): void
    {
        Queue::fake();

        $user = User::factory()->create(['timezone' => 'UTC']);

        $result = new UpdateTimezone(
            user: $user,
            timezone: 'Europe/Lisbon',
        )->execute();

        $this->assertInstanceOf(User::class, $result);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'timezone' => 'Europe/Lisbon',
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::UserTimezoneUpdate
                && $job->account->id === $user->account_id
                && $job->user->id === $user->id
                && $job->parameters === ['name' => 'Europe/Lisbon'],
        );
    }
}
