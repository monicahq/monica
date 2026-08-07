<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateUserPassword;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateUserPasswordTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_updates_the_password(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'password_hash' => Hash::make('beets'),
            'password_changed_at' => null,
        ]);

        $result = new UpdateUserPassword(
            user: $user,
            password: 'bearsbeatsbattlestar',
        )->execute();

        $this->assertInstanceOf(User::class, $result);
        $this->assertTrue(Hash::check('bearsbeatsbattlestar', $user->refresh()->password_hash));

        $this->assertEqualsWithDelta(
            now()->timestamp,
            $user->refresh()->password_changed_at?->timestamp,
            2,
        );

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::UserPasswordUpdate
                && $job->company->id === $user->company_id
                && $job->user->id === $user->id,
        );
    }

    #[Test]
    public function it_throws_when_the_user_signs_in_through_sso(): void
    {
        $this->expectException(ValidationException::class);

        $user = User::factory()->singleSignOn()->create();

        new UpdateUserPassword(
            user: $user,
            password: 'bearsbeatsbattlestar',
        )->execute();
    }
}
