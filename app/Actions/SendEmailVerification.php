<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EmailType;
use App\Jobs\SendEmail;
use App\Mail\VerifyEmail;
use App\Models\User;
use Illuminate\Support\Facades\URL;

/**
 * Send the email that asks somebody to confirm the address they signed up with.
 * The link is signed and expires. Every email the application sends goes
 * through the SendEmail job, so it is recorded in emails_sent and the company
 * can see what was sent, which is why the framework notification is not used.
 */
class SendEmailVerification
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function execute(): User
    {
        $this->send();

        return $this->user;
    }

    private function send(): void
    {
        SendEmail::dispatch(
            mailable: new VerifyEmail(url: $this->url()),
            company: $this->user->company,
            emailType: EmailType::EmailVerification,
            user: $this->user,
        )->onQueue('high');
    }

    private function url(): string
    {
        return URL::temporarySignedRoute(
            'auth.verification.verify',
            now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $this->user->id,
                'hash' => sha1($this->user->email),
            ],
        );
    }
}
