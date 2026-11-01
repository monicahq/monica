<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\PasswordIsWrong;
use App\Helpers\DeviceDescription;
use App\Jobs\LogUserAction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Sign every device but the one in hand out of the account. The guard rehashes the password, which
 * every other session and remembered sign in carries a fingerprint of, and their session rows go too,
 * each journalled by its device as a single removal is.
 */
class DestroyOtherSessions
{
    /** @var list<string> */
    private array $devices = [];

    public function __construct(
        private readonly User $user,
        private readonly string $password,
        private readonly string $currentSessionId,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->signOutOtherDevices();
        $this->destroyOtherSessions();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->user->password === null) {
            throw new PasswordIsWrong;
        }

        if (! Hash::check($this->password, $this->user->password)) {
            throw new PasswordIsWrong;
        }
    }

    private function signOutOtherDevices(): void
    {
        Auth::logoutOtherDevices($this->password);
    }

    private function destroyOtherSessions(): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        $sessions = DB::table(config('session.table'))
            ->where('user_id', $this->user->id)
            ->where('id', '!=', $this->currentSessionId);

        $this->devices = $sessions->pluck('user_agent')
            ->map(fn (?string $userAgent): string => DeviceDescription::fromUserAgent($userAgent))
            ->all();

        $sessions->delete();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::OtherSessionsRemoved,
        );

        foreach ($this->devices as $device) {
            LogUserAction::dispatch(
                account: $this->user->account,
                user: $this->user,
                action: UserActionEnum::SessionRemoved,
                parameters: ['device' => $device],
            );
        }
    }
}
