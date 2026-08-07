<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EmailType;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Jobs\SendEmail;
use App\Mail\MagicLinkCreated;
use App\Models\MagicLink;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Create a link that signs somebody in without a password, and email it to
 * them.
 *
 * Only the hash of the token reaches the database, so somebody who can read the
 * table still cannot sign in as anybody. The plain token exists just long
 * enough to be put in the email.
 *
 * An address with no active account behind it raises ModelNotFoundException,
 * which the caller is expected to swallow: the screen must look the same either
 * way, or this form becomes a way to find out who has an account here.
 */
class CreateMagicLink
{
    private User $user;

    private string $token;

    public function __construct(
        private string $email,
    ) {}

    public function execute(): User
    {
        $this->sanitize();
        $this->validate();
        $this->create();
        $this->send();
        $this->log();

        return $this->user;
    }

    private function sanitize(): void
    {
        $this->email = mb_strtolower($this->email);
    }

    private function validate(): void
    {
        $this->user = User::query()
            ->where('email', $this->email)
            ->where('is_active', true)
            ->firstOrFail();
    }

    private function create(): void
    {
        $this->token = Str::random(64);

        MagicLink::query()->create([
            'user_id' => $this->user->id,
            'token' => hash('sha256', $this->token),
            'expires_at' => now()->addMinutes($this->minutes()),
        ]);
    }

    private function send(): void
    {
        SendEmail::dispatch(
            mailable: new MagicLinkCreated(
                url: route('auth.magicLink.show', ['token' => $this->token]),
                minutes: $this->minutes(),
            ),
            company: $this->user->company,
            emailType: EmailType::MagicLinkCreated,
            user: $this->user,
        )->onQueue('high');
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->user->company,
            user: $this->user,
            action: UserActionEnum::MagicLinkCreation,
        )->onQueue('low');
    }

    private function minutes(): int
    {
        return (int) config('officelife.magic_link_duration_minutes');
    }
}
