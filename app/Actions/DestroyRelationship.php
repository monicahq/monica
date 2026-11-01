<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Relationship;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Take back a link somebody wrote down between two people.
 *
 * One row holds both sides, so removing it removes the link from both records at once. Neither
 * person goes with it, including somebody whose record exists only because this link named them:
 * their name was still written down, and it stays.
 */
class DestroyRelationship
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Relationship $relationship,
    ) {}

    public function execute(): void
    {
        $this->validate();

        $this->log();

        $this->relationship->delete();
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->relationship->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This relationship is in another account.');
        }

        if ($this->relationship->vault_id !== $this->vault->id) {
            throw new ModelNotFoundException('This relationship is in another vault.');
        }
    }

    /**
     * Written before the row goes, so the two names are still there to be read.
     */
    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::RelationshipDeletion,
            vault: $this->vault,
            parameters: [
                'name' => $this->relationship->contactA->name,
                'other' => $this->relationship->contactB->name,
            ],
        );
    }
}
