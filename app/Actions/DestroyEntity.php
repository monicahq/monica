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
 * Send a record of a type the account invented, such as an animal, to the vault's trash. Its reminders
 * stop until it is restored.
 */
class DestroyEntity
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Entity $entity,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
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
    }

    private function destroy(): void
    {
        DB::transaction(function (): void {
            $isStillThere = Entity::query()
                ->whereKey($this->entity->id)
                ->lockForUpdate()
                ->value('id') !== null;

            if (! $isStillThere) {
                throw new ModelNotFoundException('This record is already deleted.');
            }

            $this->entity->delete();

            new DeactivateReminders(
                user: $this->user,
                subject: $this->entity,
                reason: ReminderInactiveReasonEnum::RecordDeleted,
                writesToLog: false,
            )->execute();

            EntityType::query()
                ->whereKey($this->entity->entity_type_id)
                ->where('entity_count', '>', 0)
                ->decrement('entity_count');

            StructureVersion::query()
                ->whereKey($this->entity->structure_version_id)
                ->where('record_count', '>', 0)
                ->decrement('record_count');
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::EntityDeletion,
            vault: $this->vault,
            parameters: ['name' => $this->entity->name],
            loggable: $this->entity,
        );
    }
}
