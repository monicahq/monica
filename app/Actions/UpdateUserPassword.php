<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\User;
use App\Notifications\PasswordChanged;
use App\Notifications\PasswordSet;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Set or change the password of a user.
 */
class UpdateUserPassword
{
    private bool $wasSetForTheFirstTime;

    public function __construct(
        private readonly User $user,
        private readonly string $password,
    ) {}

    public function execute(): User
    {
        $this->remember();
        $this->update();
        $this->log();
        $this->notify();

        return $this->user;
    }

    private function remember(): void
    {
        $this->wasSetForTheFirstTime = $this->user->password === null;
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: $this->wasSetForTheFirstTime
                ? UserActionEnum::UserPasswordSet
                : UserActionEnum::UserPasswordUpdate,
        )->onQueue('low');
    }

    private function notify(): void
    {
        $this->user->notify($this->wasSetForTheFirstTime ? new PasswordSet : new PasswordChanged);
    }

    private function update(): void
    {
        $this->user->password = Hash::make($this->password);
        $this->user->password_changed_at = now();
        $this->user->setRememberToken(Str::random(60));
        $this->user->save();
    }
}
