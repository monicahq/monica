<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\PossessionProvenanceEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Delete a provenance event of a possession for good. The transaction it points at, if any, stays
 * where it is, since the money it records happened all the same.
 */
class DestroyPossessionProvenanceEvent
{
    public function __construct(
        private readonly User $user,
        private readonly PossessionProvenanceEvent $event,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->event->delete();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->event->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This provenance event is in another account.');
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionProvenanceEventDeletion,
            parameters: ['name' => $this->event->possession->name],
            loggable: $this->event,
        )->onQueue('low');
    }
}
