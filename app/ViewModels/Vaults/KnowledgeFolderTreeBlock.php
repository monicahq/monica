<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Helpers\KnowledgeSearch;
use App\Helpers\LocaleCollation;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\KnowledgeNote;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Collection;

/**
 * The column of folders beside every Knowledge screen: each folder with its notes and lists by
 * title, what may be done with it, and where a new note or list goes. While somebody searches, it
 * keeps only what holds every word, with every folder unfolded.
 */
class KnowledgeFolderTreeBlock
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly ?string $current = null,
        private readonly ?KnowledgeFolder $currentFolder = null,
        private readonly string $search = '',
    ) {}

    /**
     * @return list<array{id: string, name: string, count: int, documents: list<array{kind: string, icon: string, title: string, url: string, isCurrent: bool}>, isEmpty: bool, position: int, isFirst: bool, isLast: bool, previous: string, next: string, renameUrl: string, positionUrl: string, destroyUrl: string, canDelete: bool, cannotDeleteReason: string, destinations: list<array{id: string, name: string}>, documentCount: int, deleteTitle: string, deleteConsequence: string}>
     */
    public function folders(): array
    {
        $folders = $this->loadedFolders();
        $found = $this->isSearching() ? KnowledgeSearch::in($this->vault, $this->search) : null;
        $lastIndex = $folders->count() - 1;
        $rows = [];

        foreach ($folders->values() as $index => $folder) {
            $notes = $found === null ? $folder->notes : $folder->notes->whereIn('id', $found['notes']->pluck('id'));
            $lists = $found === null ? $folder->lists : $folder->lists->whereIn('id', $found['lists']->pluck('id'));
            $documents = $this->documents($notes, $lists);

            if ($found !== null && $documents === []) {
                continue;
            }

            $documentCount = $folder->notes->count() + $folder->lists->count();
            $refusal = $this->user->inspect('delete', $folder);

            $rows[] = [
                'id' => $folder->uuid,
                'name' => $folder->name,
                'count' => $documentCount,
                'documents' => $documents,
                'isEmpty' => $documentCount === 0,
                'position' => $folder->position,
                'isFirst' => $index === 0,
                'isLast' => $index === $lastIndex,
                'previous' => $folders->get($index - 1)->name ?? '',
                'next' => $folders->get($index + 1)->name ?? '',
                'renameUrl' => route('knowledge.folders.update', [$this->vault, $folder]),
                'positionUrl' => route('knowledge.folderPosition.update', [$this->vault, $folder]),
                'destroyUrl' => route('knowledge.folders.destroy', [$this->vault, $folder]),
                'canDelete' => $refusal->allowed(),
                'cannotDeleteReason' => (string) $refusal->message(),
                'destinations' => $folders
                    ->reject(fn (KnowledgeFolder $other): bool => $other->is($folder))
                    ->map(fn (KnowledgeFolder $other): array => ['id' => $other->uuid, 'name' => $other->name])
                    ->values()
                    ->all(),
                'documentCount' => $documentCount,
                'deleteTitle' => __('Delete the folder “:name”?', ['name' => $folder->name]),
                'deleteConsequence' => $documentCount === 0
                    ? __('The folder is empty. Nothing else is removed.')
                    : trans_choice('Choose where its :count note or list goes. Deleted with the folder, it cannot be recovered.|Choose where its :count notes and lists go. Deleted with the folder, they cannot be recovered.', $documentCount),
            ];
        }

        return $rows;
    }

    public function hasFolders(): bool
    {
        return $this->loadedFolders()->isNotEmpty();
    }

    public function isSearching(): bool
    {
        return mb_trim($this->search) !== '';
    }

    public function search(): string
    {
        return $this->search;
    }

    public function searchUrl(): string
    {
        return route('knowledge.index', $this->vault);
    }

    public function contentsUrl(): string
    {
        return route('knowledge.index', $this->vault);
    }

    public function contentsIsCurrent(): bool
    {
        return $this->current === 'contents';
    }

    public function newNoteUrl(): string
    {
        return route('knowledge.notes.create', $this->vault);
    }

    public function newListUrl(): string
    {
        return route('knowledge.lists.create', $this->vault);
    }

    public function newFolderUrl(): string
    {
        return route('knowledge.folders.create', $this->vault);
    }

    /** The folder a note or a list started from here goes to: the one being read, else the first. */
    public function targetFolder(): ?KnowledgeFolder
    {
        return $this->currentFolder ?? $this->loadedFolders()->first();
    }

    public function targetLine(): string
    {
        $folder = $this->targetFolder();

        if (! $folder instanceof KnowledgeFolder) {
            return __('A first folder is created with it.');
        }

        return __('Added to :name', ['name' => $folder->name]);
    }

    /**
     * @param  Collection<int, KnowledgeNote>  $notes
     * @param  Collection<int, KnowledgeList>  $lists
     * @return list<array{kind: string, icon: string, title: string, url: string, isCurrent: bool}>
     */
    private function documents(Collection $notes, Collection $lists): array
    {
        $documents = collect()
            ->concat($notes->map(fn (KnowledgeNote $note): array => [
                'kind' => 'note',
                'icon' => 'note',
                'title' => $note->title,
                'url' => route('knowledge.notes.show', [$this->vault, $note]),
                'isCurrent' => $this->current === "note:{$note->uuid}",
            ]))
            ->concat($lists->map(fn (KnowledgeList $list): array => [
                'kind' => 'list',
                'icon' => 'text-lines',
                'title' => $list->title,
                'url' => route('knowledge.lists.show', [$this->vault, $list]),
                'isCurrent' => $this->current === "list:{$list->uuid}",
            ]));

        return $documents
            ->sortBy(fn (array $document): string => LocaleCollation::sortKey($document['title']), SORT_STRING)
            ->values()
            ->all();
    }

    /** @return Collection<int, KnowledgeFolder> */
    private function loadedFolders(): Collection
    {
        return once(fn (): Collection => $this->vault->knowledgeFolders()
            ->with(['notes:id,uuid,knowledge_folder_id,title', 'lists:id,uuid,knowledge_folder_id,title'])
            ->get());
    }
}
