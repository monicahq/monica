<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\KnowledgeListCells;
use App\Jobs\LogUserAction;
use App\Models\KnowledgeList;
use App\Models\KnowledgeListItem;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Add an item to a list of a vault's Knowledge, after the items already there. Its texts are keyed
 * by the uuid of their column, and an item whose texts are all blank is refused.
 */
class CreateKnowledgeListItem
{
    private KnowledgeListItem $item;

    /** @var array<int, string|null> */
    private array $values;

    /** @param  array<string, string|null>  $cells */
    public function __construct(
        private readonly User $user,
        private readonly KnowledgeList $list,
        private readonly array $cells,
    ) {}

    public function execute(): KnowledgeListItem
    {
        $this->validate();
        $this->create();
        $this->log();

        return $this->item;
    }

    private function validate(): void
    {
        if ($this->list->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This list is in another account.');
        }

        $this->values = KnowledgeListCells::from($this->list, $this->cells);

        if (KnowledgeListCells::holdsNothing($this->values)) {
            KnowledgeListCells::refuseBlank();
        }
    }

    private function create(): void
    {
        DB::transaction(function (): void {
            $this->item = KnowledgeListItem::query()->create([
                'account_id' => $this->list->account_id,
                'vault_id' => $this->list->vault_id,
                'knowledge_list_id' => $this->list->id,
                'position' => (int) $this->list->items()->max('position') + 1,
            ]);

            KnowledgeListCells::write($this->item, $this->values);
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::KnowledgeListItemCreation,
            parameters: ['name' => $this->list->title],
            loggable: $this->item,
        )->onQueue('low');
    }
}
