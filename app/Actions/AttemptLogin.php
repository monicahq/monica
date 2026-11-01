<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\CheckLastLogin;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Notifications\LoginFailed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Sign somebody in with their email address and their password.
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
        private readonly bool $stateless = false,
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
        $candidate = User::query()->where('email', $this->email)->first();

        if ($candidate === null
            || ! $candidate->is_active
            || $candidate->password === null) {
            $this->fail($candidate);
        }

        if ($this->stateless) {
            if (! Auth::guard('web')->validate(['email' => $this->email, 'password' => $this->password])) {
                $this->fail($candidate);
            }

            RateLimiter::clear($this->throttleKey());

            $this->user = $candidate;

            return;
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            $this->fail($candidate);
        }

        RateLimiter::clear($this->throttleKey());

        $this->user = Auth::guard('web')->user();
    }

    private function fail(?User $candidate): void
    {
        RateLimiter::hit($this->throttleKey());

        if ($candidate !== null) {
            $candidate->notify(new LoginFailed);
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
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::UserLogin,
        );
    }

    private function throttleKey(): string
    {
        return Str::transliterate($this->email.'|'.($this->ip ?? ''));
    }
}
