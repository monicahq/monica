<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\TemplateVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Delete a record. The deletion is soft, and {@see RestoreEntity} brings it back
 * with everything it was part of.
 *
 * Nothing the record took part in is destroyed. Activities and files outlive it,
 * because they describe things that happened rather than the row itself, and a
 * value pointing at it is kept and rendered as unavailable rather than nulled.
 */
class DestroyEntity
{
    private string $name;

    public function __construct(
        private readonly User $user,
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
        if ($this->entity->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This entity is in another account.');
        }
    }

    private function destroy(): void
    {
        $this->name = $this->entity->name;

        DB::transaction(function (): void {
            $this->entity->delete();

            EntityType::query()
                ->whereKey($this->entity->entity_type_id)
                ->where('entity_count', '>', 0)
                ->decrement('entity_count');

            TemplateVersion::query()
                ->whereKey($this->entity->template_version_id)
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
            vault: $this->entity->vault,
            parameters: ['name' => $this->name],
            loggable: $this->entity,
        )->onQueue('low');
    }
}
