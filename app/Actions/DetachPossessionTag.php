<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Possession;
use App\Models\PossessionTag;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Take a tag off a possession. The tag stays in the account for the other possessions.
 */
class DetachPossessionTag
{
    public function __construct(
        private readonly User $user,
        private readonly Possession $possession,
        private readonly PossessionTag $possessionTag,
    ) {}

    public function execute(): Possession
    {
        $this->validate();

        if (! $this->possession->tags()->whereKey($this->possessionTag->id)->exists()) {
            return $this->possession;
        }

        $this->detach();
        $this->log();

        return $this->possession;
    }

    private function validate(): void
    {
        if ($this->possession->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This possession is in another account.');
        }

        if ($this->possessionTag->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This tag is in another account.');
        }
    }

    private function detach(): void
    {
        $this->possession->tags()->detach($this->possessionTag->id);
        $this->possession->unsetRelation('tags');
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionUpdate,
            parameters: ['name' => $this->possession->name],
            loggable: $this->possession,
        )->onQueue('low');
    }
}
