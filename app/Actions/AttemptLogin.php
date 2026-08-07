<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EmailType;
use App\Enums\UserActionEnum;
use App\Jobs\CheckLastLogin;
use App\Jobs\LogUserAction;
use App\Jobs\SendEmail;
use App\Mail\LoginFailed;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Sign somebody in with their email address and their password.
 *
 * Every refusal reports the same message. Telling an attacker the difference
 * between a wrong password, a suspended account and an address that does not
 * exist hands them a way to find out who has an account here.
 */
class AttemptLogin
{
    private const MAX_ATTEMPTS = 5;

    private User $user;

    public function __construct(
        private string $email,
        private readonly string $password,
        private readonly bool $remember = false,
        private readonly ?string $ip = null,
    ) {}

    public function execute(): User
    {
        $this->sanitize();
        $this->validate();
        $this->attempt();
        $this->stamp();
        $this->log();

        return $this->user;
    }

    private function sanitize(): void
    {
        $this->email = mb_strtolower($this->email);
    }

    /**
     * Refuse to even try once too many attempts came from this address and this
     * place, so a password cannot be guessed one request at a time.
     */
    private function validate(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('Too many sign-in attempts. Please try again in :seconds seconds.', [
                'seconds' => $seconds,
            ]),
        ]);
    }

    private function attempt(): void
    {
        $guard = Auth::guard('web');

        // The screen promises a number of days, so the guard is told to honour
        // exactly that rather than the five years Laravel defaults to. Only the
        // session guard knows how to be told, which is the only one we use.
        if ($guard instanceof SessionGuard) {
            $guard->setRememberDuration((int) config('officelife.remember_duration_days') * 24 * 60);
        }

        $candidate = User::query()->where('email', $this->email)->first();

        if ($candidate === null || ! $candidate->is_active || $candidate->usesSingleSignOn()) {
            $this->fail($candidate);
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            $this->fail($candidate);
        }

        RateLimiter::clear($this->throttleKey());

        $this->user = Auth::guard('web')->user();
    }

    /**
     * Count the attempt, warn the owner of the address when there is one, and
     * report the same thing whatever went wrong.
     */
    private function fail(?User $candidate): void
    {
        RateLimiter::hit($this->throttleKey());

        if ($candidate !== null) {
            SendEmail::dispatch(
                mailable: new LoginFailed,
                company: $candidate->company,
                emailType: EmailType::LoginFailed,
                user: $candidate,
            )->onQueue('high');
        }

        throw ValidationException::withMessages([
            'email' => __('These credentials do not match our records.'),
        ]);
    }

    private function stamp(): void
    {
        $this->user->last_login_at = now();
        $this->user->save();

        CheckLastLogin::dispatch(
            user: $this->user,
            ip: $this->ip ?? '',
        )->onQueue('low');
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->user->company,
            user: $this->user,
            action: UserActionEnum::UserLogin,
        )->onQueue('low');
    }

    private function throttleKey(): string
    {
        return Str::transliterate($this->email.'|'.($this->ip ?? ''));
    }
}
