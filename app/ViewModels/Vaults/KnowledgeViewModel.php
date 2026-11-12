<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Helpers\KnowledgeMarkdown;
use App\Helpers\LocaleCollation;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\KnowledgeNote;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * The Knowledge screen of a vault: what it is for while it holds nothing, and otherwise its
 * contents, every folder with its notes and lists and every note with its headings.
 */
class KnowledgeViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly string $search = '',
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function vault(): Vault
    {
        return $this->vault;
    }

    public function tree(): KnowledgeFolderTreeBlock
    {
        return once(fn (): KnowledgeFolderTreeBlock => new KnowledgeFolderTreeBlock(
            user: $this->user,
            vault: $this->vault,
            current: 'contents',
            search: $this->search,
        ));
    }

    public function isEmpty(): bool
    {
        return $this->folders()->every(fn (KnowledgeFolder $folder): bool => $folder->notes->isEmpty() && $folder->lists->isEmpty());
    }

    /**
     * @return list<array{name: string, count: string, isEmpty: bool, documents: list<array{icon: string, title: string, url: string, kind: string, editedAt: CarbonInterface, edited: string, headings: list<array{text: string, url: string}>}>}>
     */
    public function contents(): array
    {
        return $this->folders()
            ->map(fn (KnowledgeFolder $folder): array => [
                'name' => $folder->name,
                'count' => $folder->notes->count() + $folder->lists->count() === 0
                    ? ''
                    : trans_choice(':count note or list|:count notes and lists', $folder->notes->count() + $folder->lists->count()),
                'isEmpty' => $folder->notes->isEmpty() && $folder->lists->isEmpty(),
                'documents' => $this->documents($folder),
            ])
            ->values()
            ->all();
    }

    public function summary(): string
    {
        $folders = $this->folders();
        $notes = $folders->sum(fn (KnowledgeFolder $folder): int => $folder->notes->count());
        $lists = $folders->sum(fn (KnowledgeFolder $folder): int => $folder->lists->count());

        return __(':folders, :notes, :lists', [
            'folders' => trans_choice(':count folder|:count folders', $folders->count()),
            'notes' => trans_choice(':count note|:count notes', $notes),
            'lists' => trans_choice(':count list|:count lists', $lists),
        ]);
    }

    /** @return list<array{icon: string, title: string, url: string, kind: string, editedAt: CarbonInterface, edited: string, headings: list<array{text: string, url: string}>}> */
    private function documents(KnowledgeFolder $folder): array
    {
        $documents = collect()
            ->concat($folder->notes->map(function (KnowledgeNote $note): array {
                $url = route('knowledge.notes.show', [$this->vault, $note]);

                return [
                    'icon' => 'note',
                    'title' => $note->title,
                    'url' => $url,
                    'kind' => __('Note'),
                    'editedAt' => $this->editedAt($note->updated_at ?? $note->created_at),
                    'edited' => $this->edited($note->updated_at ?? $note->created_at),
                    'headings' => array_map(
                        fn (array $heading): array => ['text' => $heading['text'], 'url' => "{$url}#{$heading['anchor']}"],
                        KnowledgeMarkdown::headings($note->body),
                    ),
                ];
            }))
            ->concat($folder->lists->map(fn (KnowledgeList $list): array => [
                'icon' => 'text-lines',
                'title' => $list->title,
                'url' => route('knowledge.lists.show', [$this->vault, $list]),
                'kind' => trans_choice('List of :count item|List of :count items', (int) $list->items_count),
                'editedAt' => $this->editedAt($list->updated_at ?? $list->created_at),
                'edited' => $this->edited($list->updated_at ?? $list->created_at),
                'headings' => [],
            ]));

        return $documents
            ->sortBy(fn (array $document): string => LocaleCollation::sortKey($document['title']), SORT_STRING)
            ->values()
            ->all();
    }

    private function editedAt(CarbonInterface $at): CarbonInterface
    {
        return $at->copy()->setTimezone($this->user->timezone);
    }

    private function edited(CarbonInterface $at): string
    {
        return __('Edited :when', ['when' => $this->editedAt($at)->diffForHumans()]);
    }

    /** @return Collection<int, KnowledgeFolder> */
    private function folders(): Collection
    {
        return once(fn (): Collection => $this->vault->knowledgeFolders()
            ->with(['notes', 'lists' => fn ($query) => $query->withCount('items')])
            ->get());
    }
}
