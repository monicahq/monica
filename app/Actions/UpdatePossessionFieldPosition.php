<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\PossessionField;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Move a field to another place among its siblings: the fields of the same group, or the standalone
 * fields of the type. Every sibling is numbered again from one.
 */
class UpdatePossessionFieldPosition
{
    public function __construct(
        private readonly User $user,
        private readonly PossessionField $field,
        private readonly int $position,
    ) {}

    public function execute(): PossessionField
    {
        $this->validate();
        $this->update();
        $this->log();

        return $this->field;
    }

    private function validate(): void
    {
        if ($this->field->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This field is in another account.');
        }
    }

    private function update(): void
    {
        $fields = PossessionField::query()
            ->where('possession_type_id', $this->field->possession_type_id)
            ->where('possession_field_group_id', $this->field->possession_field_group_id)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->reject(fn (PossessionField $field): bool => $field->is($this->field))
            ->values();

        $fields->splice(max(0, $this->position - 1), 0, [$this->field]);

        DB::transaction(function () use ($fields): void {
            $fields->each(function (PossessionField $field, int $index): void {
                $field->position = $index + 1;
                $field->save();
            });
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::PossessionFieldUpdate,
            parameters: ['name' => $this->field->name, 'type' => $this->field->possessionType->name],
            loggable: $this->field,
        )->onQueue('low');
    }
}
