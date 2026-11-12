<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\KnowledgeListColumn;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Move a column of a list of a vault's Knowledge. A position past the end puts it last, and the
 * columns keep their positions from one upward without a gap.
 */
class UpdateKnowledgeListColumnPosition
{
    public function __construct(
        private readonly User $user,
        private readonly KnowledgeListColumn $column,
        private readonly int $position,
    ) {}

    public function execute(): KnowledgeListColumn
    {
        $this->validate();
        $this->update();
        $this->log();

        return $this->column->refresh();
    }

    private function validate(): void
    {
        if ($this->column->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This column is in another account.');
        }
    }

    private function update(): void
    {
        $columns = $this->column->list->columns()
            ->get()
            ->reject(fn (KnowledgeListColumn $column): bool => $column->is($this->column))
            ->values();

        $columns->splice(max(0, $this->position - 1), 0, [$this->column]);

        DB::transaction(function () use ($columns): void {
            $columns->each(function (KnowledgeListColumn $column, int $index): void {
                $column->position = $index + 1;
                $column->save();
            });
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::KnowledgeListColumnPositionUpdate,
            parameters: ['label' => $this->column->name, 'name' => $this->column->list->title],
            loggable: $this->column,
        )->onQueue('low');
    }
}
