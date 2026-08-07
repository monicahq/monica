<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ConfirmEmailAddress;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConfirmEmailAddressTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_confirms_the_email_address_of_a_user(): void
    {
        Queue::fake();

        $user = User::factory()->unverified()->create();

        $result = new ConfirmEmailAddress(user: $user)->execute();

        $this->assertInstanceOf(User::class, $result);
        $this->assertTrue($user->refresh()->hasConfirmedEmail());

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::EmailConfirmation
                && $job->user->id === $user->id
                && $job->company->id === $user->company_id,
        );
    }
}
