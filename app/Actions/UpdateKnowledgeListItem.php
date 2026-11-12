<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\KnowledgeListCells;
use App\Jobs\LogUserAction;
use App\Models\KnowledgeListItem;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Change the texts of an item of a list of a vault's Knowledge. Only the columns given change, a
 * blank text empties its cell, and an item left with every cell blank is refused.
 */
class UpdateKnowledgeListItem
{
    /** @var array<int, string|null> */
    private array $values;

    /** @param  array<string, string|null>  $cells */
    public function __construct(
        private readonly User $user,
        private readonly KnowledgeListItem $item,
        private readonly array $cells,
    ) {}

    public function execute(): KnowledgeListItem
    {
        $this->validate();
        $this->update();
        $this->log();

        return $this->item->refresh();
    }

    private function validate(): void
    {
        if ($this->item->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This item is in another account.');
        }

        $this->values = KnowledgeListCells::from($this->item->list, $this->cells);

        $kept = $this->item->cells()
            ->whereNotIn('knowledge_list_column_id', array_keys($this->values))
            ->exists();

        if (! $kept && KnowledgeListCells::holdsNothing($this->values)) {
            KnowledgeListCells::refuseBlank();
        }
    }

    private function update(): void
    {
        DB::transaction(function (): void {
            KnowledgeListCells::write($this->item, $this->values);
            $this->item->touch();
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::KnowledgeListItemUpdate,
            parameters: ['name' => $this->item->list->title],
            loggable: $this->item,
        )->onQueue('low');
    }
}
