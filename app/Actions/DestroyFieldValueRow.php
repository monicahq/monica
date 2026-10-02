<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Exceptions\NotEnoughRepetitions;
use App\Jobs\LogUserAction;
use App\Models\FieldValue;
use App\Models\FieldValueRow;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Remove one repetition of a repeater, and what was filled in on it.
 */
class DestroyFieldValueRow
{
    private string $label;

    public function __construct(
        private readonly User $user,
        private readonly FieldValueRow $row,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->row->valuable?->searchable();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->row->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This repetition is in another account.');
        }

        $field = $this->row->field;
        $count = $this->siblings()->count();

        if ($field->repeat_min !== null && $count <= $field->repeat_min) {
            throw new NotEnoughRepetitions("This field asks for at least {$field->repeat_min} repetitions.");
        }
    }

    private function destroy(): void
    {
        $this->label = $this->row->field->label;

        DB::transaction(function (): void {
            $this->letGoOfTheFiles();

            FieldValue::query()->where('field_value_row_id', $this->row->id)->delete();

            $this->row->delete();

            $this->closeTheGap();
        });
    }

    private function letGoOfTheFiles(): void
    {
        FieldValue::query()
            ->where('field_value_row_id', $this->row->id)
            ->whereNotNull('value_file_id')
            ->with('file')
            ->get()
            ->each(function (FieldValue $value): void {
                if ($value->file !== null) {
                    new DetachFile(user: $this->user, file: $value->file)->execute();
                }
            });
    }

    private function closeTheGap(): void
    {
        $this->siblings()
            ->orderBy('position')
            ->get()
            ->each(function (FieldValueRow $row, int $index): void {
                $row->position = $index + 1;
                $row->save();
            });
    }

    /** @return Builder<FieldValueRow> */
    private function siblings(): Builder
    {
        return FieldValueRow::query()
            ->where('valuable_type', $this->row->valuable_type)
            ->where('valuable_id', $this->row->valuable_id)
            ->where('field_id', $this->row->field_id);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::FieldValueRowDeletion,
            vault: $this->row->valuable->vault,
            parameters: ['label' => $this->label, 'name' => $this->row->valuable->name],
            loggable: $this->row,
        )->onQueue('low');
    }
}
