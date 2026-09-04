<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\CheckLastLogin;
use App\Jobs\LogUserAction;
use App\Models\MagicLink;
use App\Models\User;
use App\Notifications\NewLoginDetected;
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
            ->firstOrFail();

        if (! $magicLink->isUsable()
            || ! $magicLink->user->is_active
            || ! hash_equals($magicLink->token, hash('sha256', $this->token))) {
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
        $this->user->notify(new NewLoginDetected(ip: $this->ip ?? ''));
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::UserLogin,
        )->onQueue('low');
    }
}
