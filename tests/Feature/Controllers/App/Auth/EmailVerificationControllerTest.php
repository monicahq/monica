<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmailVerificationControllerTest extends TestCase
{
    use RefreshDatabase;

    private function verificationUrl(User $user): string
    {
        return URL::temporarySignedRoute(
            'auth.verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => hash('sha256', (string) $user->email),
            ],
        );
    }

    #[Test]
    public function it_shows_the_notice_to_somebody_who_has_not_confirmed_yet(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get(route('auth.verification.notice'));

        $response->assertStatus(200);
        $response->assertSee($user->email);
    }

    #[Test]
    public function it_sends_somebody_who_has_already_confirmed_back_home(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('auth.verification.notice'));

        $response->assertRedirect(route('home.index'));
    }

    #[Test]
    public function it_confirms_the_email_address(): void
    {
        Queue::fake();

        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get($this->verificationUrl($user));

        $response->assertRedirect(route('home.index'));
        $this->assertTrue($user->refresh()->hasVerifiedEmail());

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::EmailConfirmation,
        );
    }

    #[Test]
    public function it_refuses_a_link_whose_hash_does_not_match(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute(
            'auth.verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => hash('sha256', 'dwight.schrute@dundermifflin.com'),
            ],
        );

        $response = $this->actingAs($user)->get($url);

        $response->assertStatus(403);
        $this->assertFalse($user->refresh()->hasVerifiedEmail());
    }

    #[Test]
    public function it_sends_the_email_again(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)
            ->from(route('auth.verification.notice'))
            ->post(route('auth.verification.send'));

        $response->assertRedirect(route('auth.verification.notice'));
        $response->assertSessionHas('status');

        $this->actingAs($user)
            ->get(route('auth.verification.notice'))
            ->assertSee('role="status"', escape: false);

        Notification::assertSentTo(
            $user,
            VerifyEmail::class,
            fn (VerifyEmail $notification): bool => $notification->queue === 'high',
        );
    }

    #[Test]
    public function it_does_not_send_the_email_again_to_somebody_who_has_already_confirmed(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('auth.verification.send'));

        $response->assertRedirect(route('home.index'));

        Notification::assertNothingSent();
    }
}
