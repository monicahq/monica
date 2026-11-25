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
 * Bring a relationship back from the vault's trash.
 */
class RestoreRelationship
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Relationship $relationship,
    ) {}

    public function execute(): void
    {
        $this->validate();

        $this->relationship->restoreFromTheTrash();

        $this->log();
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->relationship->account_id !== $this->user->account_id || $this->relationship->vault_id !== $this->vault->id) {
            throw new ModelNotFoundException('This record is in another vault.');
        }

        if (! $this->relationship->trashed()) {
            throw new ModelNotFoundException('This record is not deleted.');
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::RelationshipRestoration,
            vault: $this->vault,
            parameters: ['name' => $this->relationship->contactA->name, 'other' => $this->relationship->contactB->name],
            loggable: $this->relationship,
        );
    }
}
