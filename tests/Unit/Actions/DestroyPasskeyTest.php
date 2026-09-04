<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyPasskey;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Models\WebauthnKey;
use App\Notifications\PasskeyRemoved;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyPasskeyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_removes_the_passkey(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $passkey = WebauthnKey::factory()->create(['user_id' => $user->id]);

        new DestroyPasskey(user: $user, passkey: $passkey)->execute();

        $this->assertModelMissing($passkey);
    }

    #[Test]
    public function it_records_what_it_removed_before_the_row_is_gone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $passkey = WebauthnKey::factory()->create(['user_id' => $user->id, 'name' => 'Yubikey in a drawer']);

        new DestroyPasskey(user: $user, passkey: $passkey)->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::PasskeyRemoved
                && $job->parameters === ['name' => 'Yubikey in a drawer']
                && $job->isAbout($passkey),
        );
    }

    #[Test]
    public function it_tells_the_owner_of_the_address(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $passkey = WebauthnKey::factory()->create(['user_id' => $user->id]);

        new DestroyPasskey(user: $user, passkey: $passkey)->execute();

        Notification::assertSentTo(
            $user,
            PasskeyRemoved::class,
            fn (PasskeyRemoved $notification): bool => $notification->queue === 'high',
        );
    }

    #[Test]
    public function it_refuses_a_passkey_belonging_to_another_account(): void
    {
        Queue::fake();
        Notification::fake();

        $user = User::factory()->create();
        $somebodyElse = WebauthnKey::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        try {
            new DestroyPasskey(user: $user, passkey: $somebodyElse)->execute();
        } finally {
            $this->assertModelExists($somebodyElse);
            Queue::assertNothingPushed();
            Notification::assertNothingSent();
        }
    }
}
