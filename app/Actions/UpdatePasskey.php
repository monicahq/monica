<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Passkey;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Rename a passkey. Only the person it belongs to may do so.
 *
 * A rename sends no email. The list of ways into the account has not changed,
 * which is what the other two notifications are about, and an email for every
 * relabelling would teach people to ignore the ones that matter.
 */
class UpdatePasskey
{
    public function __construct(
        private readonly User $user,
        private readonly Passkey $passkey,
        private readonly string $name,
    ) {}

    public function execute(): Passkey
    {
        $this->validate();
        $this->update();
        $this->log();

        return $this->passkey;
    }

    private function validate(): void
    {
        if ($this->passkey->user_id !== $this->user->id) {
            throw new ModelNotFoundException('Passkey not found');
        }
    }

    private function update(): void
    {
        $this->passkey->name = $this->name;
        $this->passkey->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PasskeyRenamed,
            parameters: ['name' => $this->passkey->name],
            loggable: $this->passkey,
        )->onQueue('low');
    }
}
