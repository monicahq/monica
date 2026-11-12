<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\PossessionTag;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Delete a possession tag for good. The possessions carrying it lose it and nothing else.
 */
class DestroyPossessionTag
{
    public function __construct(
        private readonly User $user,
        private readonly PossessionTag $possessionTag,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->possessionTag->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This possession tag is in another account.');
        }
    }

    private function destroy(): void
    {
        $this->possessionTag->delete();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionTagDeletion,
            parameters: ['name' => $this->possessionTag->name],
            loggable: $this->possessionTag,
        )->onQueue('low');
    }
}
