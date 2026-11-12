<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Helpers\KnowledgeMarkdown;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\KnowledgeListColumn;
use App\Models\KnowledgeListItem;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonInterface;

/**
 * One list of a vault's Knowledge: its description, a table of its columns and items, and the
 * forms adding an item, changing one, or changing the list itself.
 */
class KnowledgeListViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly KnowledgeList $list,
        private readonly bool $editing = false,
        private readonly ?KnowledgeListItem $editingItem = null,
    ) {
        $this->list->load('columns', 'items.cells');
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

    public function isEditing(): bool
    {
        return $this->editing;
    }

    /** A list holding no item opens with the row to add one already showing. */
    public function startsAdding(): bool
    {
        return $this->list->items->isEmpty() && ! $this->editing;
    }

    public function title(): string
    {
        return $this->list->title;
    }

    public function folderName(): string
    {
        return $this->list->folder->name;
    }

    public function description(): string
    {
        return (string) $this->list->description;
    }

    public function descriptionHtml(): string
    {
        return KnowledgeMarkdown::inline($this->list->description);
    }

    public function itemCount(): string
    {
        return trans_choice(':count item|:count items', $this->list->items->count());
    }

    public function editedAt(): CarbonInterface
    {
        return ($this->list->updated_at ?? $this->list->created_at)->copy()->setTimezone($this->user->timezone);
    }

    public function edited(): string
    {
        return __('Edited :when', ['when' => $this->editedAt()->diffForHumans()]);
    }

    /** @return list<array{id: string, name: string}> */
    public function columns(): array
    {
        return $this->list->columns
            ->map(fn (KnowledgeListColumn $column): array => ['id' => $column->uuid, 'name' => $column->name])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: string, isEditing: bool, cells: list<array{column: string, columnId: string, html: string, value: string, isBlank: bool}>, editUrl: string, updateUrl: string, destroyUrl: string, cancelUrl: string, deleteTitle: string}>
     */
    public function items(): array
    {
        return $this->list->items
            ->map(fn (KnowledgeListItem $item): array => [
                'id' => $item->uuid,
                'isEditing' => $this->editingItem?->is($item) ?? false,
                'cells' => $this->list->columns
                    ->map(function (KnowledgeListColumn $column) use ($item): array {
                        $value = (string) $item->valueFor($column);

                        return [
                            'column' => $column->name,
                            'columnId' => $column->uuid,
                            'html' => KnowledgeMarkdown::inline($value),
                            'value' => $value,
                            'isBlank' => $value === '',
                        ];
                    })
                    ->values()
                    ->all(),
                'editUrl' => route('knowledge.listItems.edit', [$this->vault(), $this->list, $item]),
                'updateUrl' => route('knowledge.listItems.update', [$this->vault(), $this->list, $item]),
                'destroyUrl' => route('knowledge.listItems.destroy', [$this->vault(), $this->list, $item]),
                'cancelUrl' => "{$this->showUrl()}#item-{$item->uuid}",
                'deleteTitle' => __('Delete this item of “:title”?', ['title' => $this->list->title]),
            ])
            ->values()
            ->all();
    }

    /** @return list<array{id: string, name: string, isSelected: bool}> */
    public function folders(): array
    {
        return $this->vault()->knowledgeFolders()
            ->get()
            ->map(fn (KnowledgeFolder $folder): array => [
                'id' => $folder->uuid,
                'name' => $folder->name,
                'isSelected' => $folder->id === $this->list->knowledge_folder_id,
            ])
            ->values()
            ->all();
    }

    public function showUrl(): string
    {
        return route('knowledge.lists.show', [$this->vault(), $this->list]);
    }

    public function editUrl(): string
    {
        return route('knowledge.lists.edit', [$this->vault(), $this->list]);
    }

    public function updateUrl(): string
    {
        return route('knowledge.lists.update', [$this->vault(), $this->list]);
    }

    public function destroyUrl(): string
    {
        return route('knowledge.lists.destroy', [$this->vault(), $this->list]);
    }

    public function columnsUrl(): string
    {
        return route('knowledge.listColumns.index', [$this->vault(), $this->list]);
    }

    public function addItemUrl(): string
    {
        return route('knowledge.listItems.create', [$this->vault(), $this->list]);
    }

    public function deleteTitle(): string
    {
        return __('Delete the list “:title”?', ['title' => $this->list->title]);
    }
}
