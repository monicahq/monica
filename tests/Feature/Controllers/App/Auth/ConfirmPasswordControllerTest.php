<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Auth;

use App\Enums\UserActionEnum;
use App\Http\Responses\PasskeyConfirmationResponse;
use App\Jobs\LogUserAction;
use App\Models\Passkey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConfirmPasswordControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_shows_the_screen_to_somebody_signed_in(): void
    {
        $user = User::factory()->create(['password' => Hash::make('dundermifflin')]);

        $response = $this->actingAs($user)->get(route('auth.confirmPassword.new'));

        $response->assertStatus(200);
        $response->assertSee('Confirm it is you');
        $response->assertSee('Your password');
    }

    #[Test]
    public function it_sends_a_visitor_to_sign_in(): void
    {
        $response = $this->get(route('auth.confirmPassword.new'));

        $response->assertRedirect(route('auth.login.new'));
    }

    #[Test]
    public function it_confirms_the_right_password(): void
    {
        Queue::fake();

        $user = User::factory()->create(['password' => Hash::make('dundermifflin')]);

        $response = $this->actingAs($user)->post(route('auth.confirmPassword.create'), [
            'password' => 'dundermifflin',
        ]);

        $response->assertRedirect(route('settings.security.index'));
        $this->assertNotNull(session('auth.password_confirmed_at'));

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::PasswordConfirmed);
    }

    #[Test]
    public function it_returns_to_the_action_that_was_asked_for(): void
    {
        $user = User::factory()->create(['password' => Hash::make('dundermifflin')]);

        $this->actingAs($user)->withSession(['url.intended' => route('settings.twoFactor.new')])
            ->post(route('auth.confirmPassword.create'), ['password' => 'dundermifflin'])
            ->assertRedirect(route('settings.twoFactor.new'));
    }

    #[Test]
    public function it_refuses_the_wrong_password(): void
    {
        Queue::fake();

        $user = User::factory()->create(['password' => Hash::make('dundermifflin')]);

        $response = $this->actingAs($user)->post(route('auth.confirmPassword.create'), [
            'password' => 'scranton',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertNull(session('auth.password_confirmed_at'));

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::PasswordConfirmationRefused);
    }

    #[Test]
    public function it_rate_limits_the_attempts(): void
    {
        Queue::fake();

        $user = User::factory()->create(['password' => Hash::make('dundermifflin')]);

        foreach (range(1, 5) as $ignored) {
            $this->actingAs($user)->post(route('auth.confirmPassword.create'), ['password' => 'scranton']);
        }

        $response = $this->actingAs($user)->post(route('auth.confirmPassword.create'), ['password' => 'scranton']);

        $response->assertStatus(429);
    }

    #[Test]
    public function it_offers_the_passkey_alone_to_a_passwordless_account(): void
    {
        URL::forceScheme('https');

        $user = User::factory()->create(['password' => null]);
        Passkey::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('auth.confirmPassword.new'));

        $response->assertStatus(200);
        $response->assertSee('Use a passkey');
        $response->assertDontSee('Your password');
    }

    #[Test]
    public function it_explains_when_the_account_holds_neither_credential(): void
    {
        $user = User::factory()->create(['password' => null]);

        $response = $this->actingAs($user)->get(route('auth.confirmPassword.new'));

        $response->assertStatus(200);
        $response->assertSee('This account holds neither a password nor a passkey, so there is nothing to confirm with.');
        $response->assertSee(route('auth.password.new'));
        $response->assertDontSee('Your password');
    }

    #[Test]
    public function one_confirmation_covers_a_run_of_sensitive_actions(): void
    {
        Queue::fake();

        $user = User::factory()->create(['password' => Hash::make('dundermifflin')]);

        $this->actingAs($user)->post(route('auth.confirmPassword.create'), ['password' => 'dundermifflin']);

        $this->actingAs($user)->post(route('settings.apiKeys.create'), ['name' => 'Sabre'])
            ->assertRedirect(route('settings.security.index'));

        $this->actingAs($user)->post(route('settings.apiKeys.create'), ['name' => 'Athlead'])
            ->assertRedirect(route('settings.security.index'));

        $this->assertSame(2, $user->tokens()->count());
    }

    #[Test]
    public function it_asks_again_once_the_window_has_passed(): void
    {
        $user = User::factory()->create(['password' => Hash::make('dundermifflin')]);

        $this->actingAs($user)->post(route('auth.confirmPassword.create'), ['password' => 'dundermifflin']);

        $this->travel(3600)->minutes();

        $this->actingAs($user)->post(route('settings.apiKeys.create'), ['name' => 'Sabre'])
            ->assertRedirect(route('auth.confirmPassword.new'));

        $this->assertSame(0, $user->tokens()->count());
    }

    #[Test]
    public function it_forgets_the_confirmation_on_sign_out(): void
    {
        $user = User::factory()->create(['password' => Hash::make('dundermifflin')]);

        $this->actingAs($user)->post(route('auth.confirmPassword.create'), ['password' => 'dundermifflin']);
        $this->actingAs($user)->post(route('auth.login.destroy'));

        $this->assertNull(session('auth.password_confirmed_at'));
    }

    #[Test]
    public function a_passkey_confirmation_lands_on_the_action_that_was_asked_for(): void
    {
        session()->put('url.intended', route('settings.twoFactor.new'));

        $request = Request::create('/passkeys/confirm');
        $request->setLaravelSession(app('session.store'));

        $response = app(PasskeyConfirmationResponse::class)->toResponse($request);

        $this->assertSame(route('settings.twoFactor.new'), $response->getTargetUrl());
    }

    #[Test]
    public function a_passkey_confirmation_lands_on_the_security_screen_when_nothing_was_asked_for(): void
    {
        $request = Request::create('/passkeys/confirm');
        $request->setLaravelSession(app('session.store'));

        $response = app(PasskeyConfirmationResponse::class)->toResponse($request);

        $this->assertSame(route('settings.security.index'), $response->getTargetUrl());
    }
}
