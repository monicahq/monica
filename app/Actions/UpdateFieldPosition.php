<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Field;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Move a field up or down inside its section.
 */
class UpdateFieldPosition
{
    public function __construct(
        private readonly User $user,
        private readonly Field $field,
        private readonly int $position,
    ) {}

    public function execute(): Field
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
        $siblings = Field::query()
            ->where('section_version_id', $this->field->section_version_id)
            ->where('parent_field_id', $this->field->parent_field_id)
            ->orderBy('position')
            ->get()
            ->reject(fn (Field $field): bool => $field->is($this->field))
            ->values();

        $siblings->splice(max(0, $this->position - 1), 0, [$this->field]);

        DB::transaction(function () use ($siblings): void {
            $siblings->each(function (Field $field, int $index): void {
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
            action: UserActionEnum::FieldPositionUpdate,
            parameters: [
                'label' => $this->field->label,
                'name' => $this->field->sectionVersion->section->name,
            ],
            loggable: $this->field,
        );
    }
}
