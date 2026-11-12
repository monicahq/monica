<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\PossessionLocationStay;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Delete one stay of a possession for good. The other stays are left as they are, so its period
 * shows as unrecorded. Deleting the open stay leaves the possession with no current location until it
 * is moved.
 */
class DestroyPossessionLocationStay
{
    public function __construct(
        private readonly User $user,
        private readonly PossessionLocationStay $stay,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->stay->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This stay is in another account.');
        }
    }

    private function destroy(): void
    {
        DB::transaction(function (): void {
            if ($this->stay->isOpen()) {
                $this->stay->possession->update(['current_location_id' => null]);
            }

            $this->stay->delete();
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionLocationStayDeletion,
            parameters: ['name' => $this->stay->possession->name],
            loggable: $this->stay,
        )->onQueue('low');
    }
}
