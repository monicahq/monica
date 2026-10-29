<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\CannotDeleteTheCurrentSession;
use App\Helpers\DeviceDescription;
use App\Jobs\LogUserAction;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Sign another device out of the account: its session ends, and the remembered sign in of every
 * device stops working, since an account keeps a single remember token.
 */
class DestroySession
{
    private string $device;

    public function __construct(
        private readonly User $user,
        private readonly string $sessionId,
        private readonly string $currentSessionId,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->forgetRememberedDevices();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->sessionId === $this->currentSessionId) {
            throw new CannotDeleteTheCurrentSession;
        }
    }

    private function destroy(): void
    {
        $session = DB::table(config('session.table'))
            ->where('id', $this->sessionId)
            ->where('user_id', $this->user->id)
            ->first();

        if ($session === null) {
            throw new ModelNotFoundException;
        }

        $this->device = DeviceDescription::fromUserAgent($session->user_agent);

        DB::table(config('session.table'))
            ->where('id', $this->sessionId)
            ->where('user_id', $this->user->id)
            ->delete();
    }

    private function forgetRememberedDevices(): void
    {
        $this->user->setRememberToken(Str::random(60));
        $this->user->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::SessionRemoved,
            parameters: ['device' => $this->device],
        )->onQueue('low');
    }
}
