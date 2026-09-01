<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\FieldValueRow;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Move one repetition of a repeater above or below the others.
 *
 * The repetition is taken out and put back at the position asked for, and the
 * rest are numbered from one again. A position past the end puts it last.
 *
 * It touches the order and nothing else. What was filled in stays where it was,
 * and no value changes which repetition it belongs to.
 */
class UpdateFieldValueRowPosition
{
    public function __construct(
        private readonly User $user,
        private readonly FieldValueRow $row,
        private readonly int $position,
    ) {}

    public function execute(): FieldValueRow
    {
        $this->validate();
        $this->update();
        $this->log();

        return $this->row;
    }

    private function validate(): void
    {
        if ($this->row->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This repetition is in another account.');
        }
    }

    private function update(): void
    {
        $siblings = FieldValueRow::query()
            ->where('valuable_type', $this->row->valuable_type)
            ->where('valuable_id', $this->row->valuable_id)
            ->where('field_id', $this->row->field_id)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->reject(fn (FieldValueRow $row): bool => $row->is($this->row))
            ->values();

        $siblings->splice(max(0, $this->position - 1), 0, [$this->row]);

        DB::transaction(function () use ($siblings): void {
            $siblings->each(function (FieldValueRow $row, int $index): void {
                $row->position = $index + 1;
                $row->save();
            });
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::FieldValueRowPositionUpdate,
            vault: $this->row->valuable->vault,
            parameters: ['label' => $this->row->field->label, 'name' => $this->row->valuable->name],
            loggable: $this->row,
        )->onQueue('low');
    }
}
