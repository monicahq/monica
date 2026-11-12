<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Models\KnowledgeList;
use App\Models\KnowledgeListColumn;
use App\Models\User;
use App\Models\Vault;

/**
 * The columns of one list of a vault's Knowledge, to add, rename, reorder or remove.
 */
class KnowledgeListColumnsViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly KnowledgeList $list,
    ) {
        $this->list->load('columns');
    }

    public function user(): User
    {
        return $this->user;
    }

    public function vault(): Vault
    {
        return $this->list->folder->vault;
    }

    public function tree(): KnowledgeFolderTreeBlock
    {
        return once(fn (): KnowledgeFolderTreeBlock => new KnowledgeFolderTreeBlock(
            user: $this->user,
            vault: $this->vault(),
            current: "list:{$this->list->uuid}",
            currentFolder: $this->list->folder,
        ));
    }

    public function title(): string
    {
        return $this->list->title;
    }

    /**
     * @return list<array{id: string, name: string, position: int, isFirst: bool, isLast: bool, previous: string, next: string, updateUrl: string, positionUrl: string, destroyUrl: string, canDelete: bool, cannotDeleteReason: string, deleteTitle: string}>
     */
    public function columns(): array
    {
        $columns = $this->list->columns->values();
        $lastIndex = $columns->count() - 1;

        return $columns
            ->map(function (KnowledgeListColumn $column, int $index) use ($columns, $lastIndex): array {
                $refusal = $this->user->inspect('delete', $column);

                return [
                    'id' => $column->uuid,
                    'name' => $column->name,
                    'position' => $column->position,
                    'isFirst' => $index === 0,
                    'isLast' => $index === $lastIndex,
                    'previous' => $columns->get($index - 1)->name ?? '',
                    'next' => $columns->get($index + 1)->name ?? '',
                    'updateUrl' => route('knowledge.listColumns.update', [$this->vault(), $this->list, $column]),
                    'positionUrl' => route('knowledge.listColumnPosition.update', [$this->vault(), $this->list, $column]),
                    'destroyUrl' => route('knowledge.listColumns.destroy', [$this->vault(), $this->list, $column]),
                    'canDelete' => $refusal->allowed(),
                    'cannotDeleteReason' => (string) $refusal->message(),
                    'deleteTitle' => __('Remove the column “:name”?', ['name' => $column->name]),
                ];
            })
            ->all();
    }

    public function createUrl(): string
    {
        return route('knowledge.listColumns.create', [$this->vault(), $this->list]);
    }

    public function listUrl(): string
    {
        return route('knowledge.lists.show', [$this->vault(), $this->list]);
    }
}
