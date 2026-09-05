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
 * Bring a deleted record back, on the version it was rendering from before it went.
 */
class RestoreEntity
{
    public function __construct(
        private readonly User $user,
        private readonly Entity $entity,
    ) {}

    public function execute(): Entity
    {
        $this->validate();
        $this->restore();
        $this->log();

        return $this->entity;
    }

    private function validate(): void
    {
        if ($this->entity->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This entity is in another account.');
        }
    }

    private function restore(): void
    {
        if (! $this->entity->trashed()) {
            return;
        }

        DB::transaction(function (): void {
            $this->entity->restore();

            EntityType::query()->withTrashed()->whereKey($this->entity->entity_type_id)->increment('entity_count');
            TemplateVersion::query()->whereKey($this->entity->template_version_id)->increment('record_count');
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::EntityRestoration,
            vault: $this->entity->vault,
            parameters: ['name' => $this->entity->name],
            loggable: $this->entity,
        )->onQueue('low');
    }
}
