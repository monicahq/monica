<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Delete a location that holds nothing. One holding other locations is refused until they are moved,
 * so nothing is moved or lost on anybody's behalf.
 */
class DestroyLocation
{
    private string $name;

    public function __construct(
        private readonly User $user,
        private readonly Location $location,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->location->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This location is in another account.');
        }
    }

    /**
     * The row is locked before the children are counted, so a location added inside it meanwhile
     * waits on the lock instead of being cascaded away, and the count is taken afresh rather than from
     * whatever the screen had loaded.
     */
    private function destroy(): void
    {
        $this->name = $this->location->name;

        DB::transaction(function (): void {
            Location::query()->whereKey($this->location->id)->lockForUpdate()->first();

            $this->location->unsetRelation('children');
            $this->user->authorize('delete', $this->location);

            $this->location->delete();
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::LocationDeletion,
            parameters: ['name' => $this->name],
            loggable: $this->location,
        )->onQueue('low');
    }
}
