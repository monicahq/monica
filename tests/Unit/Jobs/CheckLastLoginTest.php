<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Jobs\CheckLastLogin;
use App\Models\User;
use App\Notifications\UserIpChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CheckLastLoginTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_says_nothing_the_first_time_somebody_signs_in(): void
    {
        Notification::fake();

        $user = User::factory()->create(['last_login_ip' => null]);

        new CheckLastLogin(user: $user, ip: '10.0.0.1')->handle();

        $this->assertEquals('10.0.0.1', $user->refresh()->last_login_ip);

        Notification::assertNothingSent();
    }

    #[Test]
    public function it_says_nothing_when_the_address_did_not_move(): void
    {
        Notification::fake();

        $user = User::factory()->create(['last_login_ip' => '10.0.0.1']);

        new CheckLastLogin(user: $user, ip: '10.0.0.1')->handle();

        Notification::assertNothingSent();
    }

    #[Test]
    public function it_warns_the_user_when_the_address_moved(): void
    {
        Notification::fake();

        $user = User::factory()->create(['last_login_ip' => '10.0.0.1']);

        new CheckLastLogin(user: $user, ip: '192.168.1.1')->handle();

        $this->assertEquals('192.168.1.1', $user->refresh()->last_login_ip);

        Notification::assertSentTo(
            $user,
            UserIpChanged::class,
            fn (UserIpChanged $notification): bool => $notification->ip === '192.168.1.1'
                && $notification->queue === 'high',
        );
    }
}
