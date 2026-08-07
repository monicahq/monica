<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Enums\EmailType;
use App\Jobs\CheckLastLogin;
use App\Jobs\SendEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CheckLastLoginTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_says_nothing_the_first_time_somebody_signs_in(): void
    {
        Queue::fake();

        $user = User::factory()->create(['last_login_ip' => null]);

        new CheckLastLogin(user: $user, ip: '10.0.0.1')->handle();

        $this->assertEquals('10.0.0.1', $user->refresh()->last_login_ip);

        Queue::assertNotPushed(SendEmail::class);
    }

    #[Test]
    public function it_says_nothing_when_the_address_did_not_move(): void
    {
        Queue::fake();

        $user = User::factory()->create(['last_login_ip' => '10.0.0.1']);

        new CheckLastLogin(user: $user, ip: '10.0.0.1')->handle();

        Queue::assertNotPushed(SendEmail::class);
    }

    #[Test]
    public function it_warns_the_user_when_the_address_moved(): void
    {
        Queue::fake();

        $user = User::factory()->create(['last_login_ip' => '10.0.0.1']);

        new CheckLastLogin(user: $user, ip: '192.168.1.1')->handle();

        $this->assertEquals('192.168.1.1', $user->refresh()->last_login_ip);

        Queue::assertPushedOn(
            queue: 'high',
            job: SendEmail::class,
            callback: fn (SendEmail $job): bool => $job->emailType === EmailType::UserIpChanged
                && $job->user->id === $user->id,
        );
    }
}
