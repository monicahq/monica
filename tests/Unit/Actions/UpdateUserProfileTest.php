<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateUserProfile;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateUserProfileTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_updates_the_name_of_the_user_signed_in(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'first_name' => 'Dwight',
            'last_name' => 'Schrute',
            'email' => 'dwight.schrute@dundermifflin.com',
        ]);

        $result = new UpdateUserProfile(
            user: $user,
            firstName: 'Dwight',
            lastName: 'Schrute-Bratton',
            email: 'Dwight.Schrute@SchruteFarms.com',
        )->execute();

        $this->assertInstanceOf(User::class, $result);

        $user->refresh();

        $this->assertEquals('Dwight', $user->first_name);
        $this->assertEquals('Schrute-Bratton', $user->last_name);
        $this->assertEquals('Dwight Schrute-Bratton', $user->name);
        $this->assertEquals('dwight.schrute@schrutefarms.com', $user->email);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::UserProfileUpdate
                && $job->account->id === $user->account_id
                && $job->user->id === $user->id,
        );
    }

    #[Test]
    public function it_stamps_when_the_profile_was_last_saved(): void
    {
        Queue::fake();

        $user = User::factory()->create(['last_saved_at' => null]);

        new UpdateUserProfile(
            user: $user,
            firstName: 'Michael',
            lastName: 'Scott',
            email: $user->email,
        )->execute();

        $this->assertEqualsWithDelta(
            now()->timestamp,
            $user->refresh()->last_saved_at?->timestamp,
            2,
        );
    }

    #[Test]
    public function it_unconfirms_the_address_when_it_changes(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'email' => 'pam.beesly@dundermifflin.com',
            'email_verified_at' => now(),
        ]);

        new UpdateUserProfile(
            user: $user,
            firstName: 'Pam',
            lastName: 'Halpert',
            email: 'pam.halpert@dundermifflin.com',
        )->execute();

        $this->assertNull($user->refresh()->email_verified_at);
    }

    #[Test]
    public function it_keeps_the_address_confirmed_when_it_does_not_change(): void
    {
        Queue::fake();

        $user = User::factory()->create([
            'email' => 'pam.beesly@dundermifflin.com',
            'email_verified_at' => now(),
        ]);

        new UpdateUserProfile(
            user: $user,
            firstName: 'Pam',
            lastName: 'Beesly',
            email: 'pam.beesly@dundermifflin.com',
        )->execute();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }
}
