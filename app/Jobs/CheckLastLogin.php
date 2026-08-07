<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\EmailType;
use App\Mail\UserIpChanged;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Compare where the user just signed in from with where they signed in last
 * time, and warn them when it moved. The very first sign-in has nothing to
 * compare against, so it records the address and says nothing.
 */
class CheckLastLogin implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $user,
        public string $ip,
    ) {}

    public function handle(): void
    {
        $previous = $this->user->last_login_ip;

        $this->user->last_login_ip = $this->ip;
        $this->user->save();

        if ($previous === null || $previous === $this->ip) {
            return;
        }

        SendEmail::dispatch(
            mailable: new UserIpChanged(
                email: $this->user->email,
                ip: $this->ip,
            ),
            company: $this->user->company,
            emailType: EmailType::UserIpChanged,
            user: $this->user,
        )->onQueue('high');
    }
}
