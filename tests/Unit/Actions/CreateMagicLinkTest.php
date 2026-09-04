<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateMagicLink;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\MagicLink;
use App\Models\User;
use App\Notifications\MagicLinkCreated;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateMagicLinkTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_link_and_emails_it(): void
    {
        Queue::fake();
        Notification::fake();

        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        $result = new CreateMagicLink(email: 'michael.scott@dundermifflin.com')->execute();

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(1, MagicLink::query()->where('user_id', $user->id)->count());

        Notification::assertSentTo(
            $user,
            MagicLinkCreated::class,
            fn (MagicLinkCreated $notification): bool => $notification->queue === 'high',
        );

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::MagicLinkCreation,
        );
    }

    #[Test]
    public function it_never_stores_the_token_it_puts_in_the_email(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        new CreateMagicLink(email: 'michael.scott@dundermifflin.com')->execute();

        $stored = MagicLink::query()->firstOrFail();

        Notification::assertSentTo($user, MagicLinkCreated::class, function (MagicLinkCreated $notification) use ($stored): bool {
            $this->assertMatchesRegularExpression('#^[A-Za-z0-9]{64}$#', $notification->token);
            $this->assertNotEquals($notification->token, $stored->token);
            $this->assertEquals(hash('sha256', $notification->token), $stored->token);

            return true;
        });
    }

    #[Test]
    public function it_puts_the_token_and_nothing_else_in_the_link(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        new CreateMagicLink(email: 'michael.scott@dundermifflin.com')->execute();

        $stored = MagicLink::query()->firstOrFail();

        Notification::assertSentTo($user, MagicLinkCreated::class, function (MagicLinkCreated $notification) use ($user, $stored): bool {
            $url = (string) $notification->toMail($user)->actionUrl;

            $this->assertStringContainsString('/magic-link/'.$notification->token, $url);
            $this->assertStringNotContainsString($stored->token, $url);

            return true;
        });
    }

    #[Test]
    public function it_gives_the_link_a_short_life(): void
    {
        Queue::fake();
        config(['monica.magic_link_duration_minutes' => 5]);

        User::factory()->create(['email' => 'michael.scott@dundermifflin.com']);

        new CreateMagicLink(email: 'michael.scott@dundermifflin.com')->execute();

        $this->assertEqualsWithDelta(
            now()->addMinutes(5)->timestamp,
            MagicLink::query()->firstOrFail()->expires_at->timestamp,
            2,
        );
    }

    #[Test]
    public function it_refuses_an_address_that_has_no_account(): void
    {
        Queue::fake();

        $this->expectException(ModelNotFoundException::class);

        new CreateMagicLink(email: 'nobody@dundermifflin.com')->execute();
    }

    #[Test]
    public function it_refuses_a_suspended_user(): void
    {
        Queue::fake();

        User::factory()->inactive()->create(['email' => 'michael.scott@dundermifflin.com']);

        $this->expectException(ModelNotFoundException::class);

        new CreateMagicLink(email: 'michael.scott@dundermifflin.com')->execute();
    }
}
