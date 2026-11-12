<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\PossessionField;
use App\Models\PossessionFieldGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Remove a group from its possession type and keep its fields: they become standalone fields of the
 * type, after the ones already there, in the order they had in the group.
 */
class DestroyPossessionFieldGroup
{
    private string $name;

    private string $typeName;

    public function __construct(
        private readonly User $user,
        private readonly PossessionFieldGroup $group,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->group->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This group is in another account.');
        }
    }

    private function destroy(): void
    {
        $this->name = $this->group->name;
        $this->typeName = $this->group->possessionType->name;

        $lastPosition = (int) PossessionField::query()
            ->where('possession_type_id', $this->group->possession_type_id)
            ->whereNull('possession_field_group_id')
            ->max('position');

        DB::transaction(function () use ($lastPosition): void {
            $this->group->fields->each(function (PossessionField $field, int $index) use ($lastPosition): void {
                $field->possession_field_group_id = null;
                $field->position = $lastPosition + $index + 1;
                $field->save();
            });

            $this->group->delete();
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionFieldGroupDeletion,
            parameters: ['name' => $this->name, 'type' => $this->typeName],
            loggable: $this->group,
        )->onQueue('low');
    }
}
