<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Features;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function email_verification_screen_can_be_rendered()
    {
        $this->withoutVite();

        if (! Features::enabled(Features::emailVerification())) {
            return $this->markTestSkipped('Email verification not enabled.');
        }

        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertStatus(200);
    }

    #[Test]
    public function email_can_be_verified()
    {
        if (! Features::enabled(Features::emailVerification())) {
            return $this->markTestSkipped('Email verification not enabled.');
        }

        Event::fake();

        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect('/vaults?verified=1');
    }

    #[Test]
    public function email_can_not_verified_with_invalid_hash()
    {
        if (! Features::enabled(Features::emailVerification())) {
            return $this->markTestSkipped('Email verification not enabled.');
        }

        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
