<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\MagicLink;
use App\Models\User;
use App\Notifications\MagicLinkCreated;
use Illuminate\Support\Str;

/**
 * Create a link that signs somebody in without a password, and email it to them.
 */
class CreateMagicLink
{
    private User $user;

    private MagicLink $magicLink;

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

        $this->magicLink = MagicLink::query()->create([
            'user_id' => $this->user->id,
            'token' => hash('sha256', $this->token),
            'expires_at' => now()->addMinutes($this->minutes()),
        ]);
    }

    private function send(): void
    {
        $this->user->notify(new MagicLinkCreated(
            magicLink: $this->magicLink,
            token: $this->token,
            minutes: $this->minutes(),
        ));
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::MagicLinkCreation,
        );
    }

    private function minutes(): int
    {
        return (int) config('monica.magic_link_duration_minutes');
    }
}
