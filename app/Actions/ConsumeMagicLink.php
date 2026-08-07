<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EmailType;
use App\Enums\UserActionEnum;
use App\Jobs\CheckLastLogin;
use App\Jobs\LogUserAction;
use App\Jobs\SendEmail;
use App\Mail\NewLoginDetected;
use App\Models\MagicLink;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Spend a magic link and sign its owner in.
 *
 * The link burns on first use. An unknown, expired, already spent or suspended
 * link all raise the same exception, so the screen has nothing to report back
 * beyond "this link no longer works".
 */
class ConsumeMagicLink
{
    private MagicLink $magicLink;

    private User $user;

    public function __construct(
        private readonly string $token,
        private readonly ?string $ip = null,
    ) {}

    public function execute(): User
    {
        $this->validate();
        $this->burn();
        $this->stamp();
        $this->notify();
        $this->log();

        return $this->user;
    }

    private function validate(): void
    {
        $magicLink = MagicLink::query()
            ->with('user')
            ->where('token', hash('sha256', $this->token))
            ->first();

        if ($magicLink === null || ! $magicLink->isUsable() || ! $magicLink->user->is_active) {
            throw new ModelNotFoundException('Magic link not found');
        }

        $this->magicLink = $magicLink;
        $this->user = $magicLink->user;
    }

    private function burn(): void
    {
        $this->magicLink->used_at = now();
        $this->magicLink->save();
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

    /**
     * A password sign-in is not worth an email every time, but getting in
     * without one is, so this is the path that sends it.
     */
    private function notify(): void
    {
        SendEmail::dispatch(
            mailable: new NewLoginDetected(ip: $this->ip ?? ''),
            company: $this->user->company,
            emailType: EmailType::NewLogin,
            user: $this->user,
        )->onQueue('high');
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->user->company,
            user: $this->user,
            action: UserActionEnum::UserLogin,
        )->onQueue('low');
    }
}
