<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReminderInactiveReasonEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\StructureVersion;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Bring a record of a type the account invented, such as an animal, back from the vault's trash, with the
 * reminders its deletion stopped.
 */
class RestoreEntity
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Entity $entity,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->restore();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->entity->account_id !== $this->user->account_id || $this->entity->vault_id !== $this->vault->id) {
            throw new ModelNotFoundException('This record is in another vault.');
        }

        if (! $this->entity->trashed()) {
            throw new ModelNotFoundException('This record is not deleted.');
        }
    }

    private function restore(): void
    {
        DB::transaction(function (): void {
            $isStillTrashed = Entity::query()
                ->onlyTrashed()
                ->whereKey($this->entity->id)
                ->lockForUpdate()
                ->value('id') !== null;

            if (! $isStillTrashed) {
                throw new ModelNotFoundException('This record is not deleted.');
            }

            $this->entity->restore();

            new ReactivateReminders(
                user: $this->user,
                subject: $this->entity,
                reason: ReminderInactiveReasonEnum::RecordDeleted,
                writesToLog: false,
            )->execute();

            EntityType::query()->whereKey($this->entity->entity_type_id)->increment('entity_count');
            StructureVersion::query()->whereKey($this->entity->structure_version_id)->increment('record_count');
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::EntityRestoration,
            vault: $this->vault,
            parameters: ['name' => $this->entity->name],
            loggable: $this->entity,
        );
    }
}
