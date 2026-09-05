<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\EntityType;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Move a kind of object in the navigation, so the ones somebody opens daily are the ones they
 * reach first.
 */
class UpdateEntityTypePosition
{
    public function __construct(
        private readonly User $user,
        private readonly EntityType $entityType,
        private readonly int $position,
    ) {}

    public function execute(): EntityType
    {
        $this->validate();
        $this->update();
        $this->log();

        return $this->entityType;
    }

    private function validate(): void
    {
        if ($this->entityType->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This entity type is in another account.');
        }
    }

    private function update(): void
    {
        $types = EntityType::query()
            ->where('account_id', $this->user->account_id)
            ->orderBy('position')
            ->get()
            ->reject(fn (EntityType $type): bool => $type->is($this->entityType))
            ->values();

        $types->splice(max(0, $this->position - 1), 0, [$this->entityType]);

        DB::transaction(function () use ($types): void {
            $types->each(function (EntityType $type, int $index): void {
                $type->position = $index + 1;
                $type->save();
            });
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::EntityTypePositionUpdate,
            parameters: ['name' => $this->entityType->name_plural],
            loggable: $this->entityType,
        )->onQueue('low');
    }
}
