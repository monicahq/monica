<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\KnowledgeListColumn;
use App\Models\KnowledgeListItem;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Remove a column from a list of a vault's Knowledge, with every cell written in it. An item left
 * with nothing written in any column goes too, since a list holds no blank item. The last column of
 * a list cannot be removed, and the columns and items left keep their positions without a gap.
 */
class DestroyKnowledgeListColumn
{
    public function __construct(
        private readonly User $user,
        private readonly KnowledgeListColumn $column,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->column->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This column is in another account.');
        }

        $this->user->authorize('delete', $this->column);
    }

    private function destroy(): void
    {
        DB::transaction(function (): void {
            $this->column->delete();

            $this->column->list->columns()
                ->get()
                ->each(function (KnowledgeListColumn $column, int $index): void {
                    $column->position = $index + 1;
                    $column->save();
                });

            $blankItems = $this->column->list->items()->whereDoesntHave('cells')->get();

            if ($blankItems->isEmpty()) {
                return;
            }

            $blankItems->each(fn (KnowledgeListItem $item) => $item->delete());

            $this->column->list->items()
                ->get()
                ->each(function (KnowledgeListItem $item, int $index): void {
                    $item->position = $index + 1;
                    $item->save();
                });
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::KnowledgeListColumnDeletion,
            parameters: ['label' => $this->column->name, 'name' => $this->column->list->title],
            loggable: $this->column,
        )->onQueue('low');
    }
}
