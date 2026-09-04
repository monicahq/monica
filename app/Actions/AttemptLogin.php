<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\CheckLastLogin;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Notifications\LoginFailed;
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
 *
 * The API signs nobody in, it hands out a token, so it asks for the same rules
 * without the session: `stateless` checks the credentials and stops there. The
 * rate limit, the warning email, the identical refusal, the stamp and the
 * journal entry are the same either way, which is the point of asking here
 * rather than writing a second way to sign in.
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
        $candidate = User::query()->where('email', $this->email)->first();

        // A null password used to mean one thing, an SSO account. It now also
        // means somebody gave their password up in favour of passkeys, and
        // either way there is nothing here to check: handing a null hash to the
        // guard is not a comparison, it is an error. It sits in the shared
        // refusal so that the stateless path below is covered too.
        if ($candidate === null
            || ! $candidate->is_active
            || $candidate->usesSingleSignOn()
            || $candidate->password === null) {
            $this->fail($candidate);
        }

        // A token is not a session. The API wants the credentials checked and
        // nothing else begun, so it validates them and keeps the user it has
        // already looked up, rather than signing anybody in.
        if ($this->stateless) {
            if (! Auth::guard('web')->validate(['email' => $this->email, 'password' => $this->password])) {
                $this->fail($candidate);
            }

            RateLimiter::clear($this->throttleKey());

            $this->user = $candidate;

            return;
        }

        $guard = Auth::guard('web');

        // The screen promises a number of days, so the guard is told to honour
        // exactly that rather than the five years Laravel defaults to. Only the
        // session guard knows how to be told, which is the only one we use.
        if ($guard instanceof SessionGuard) {
            $guard->setRememberDuration((int) config('monica.remember_duration_days') * 24 * 60);
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
        )->onQueue('low');
    }

    private function throttleKey(): string
    {
        return Str::transliterate($this->email.'|'.($this->ip ?? ''));
    }
}
