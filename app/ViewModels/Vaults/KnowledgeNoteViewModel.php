<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Actions\ToggleKnowledgeNoteTask;
use App\Helpers\KnowledgeMarkdown;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeNote;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonInterface;

/**
 * One note of a vault's Knowledge, read with its Markdown drawn, or open in its editor.
 */
class KnowledgeNoteViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly KnowledgeNote $note,
        private readonly bool $editing = false,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function vault(): Vault
    {
        return $this->note->folder->vault;
    }

    public function tree(): KnowledgeFolderTreeBlock
    {
        return once(fn (): KnowledgeFolderTreeBlock => new KnowledgeFolderTreeBlock(
            user: $this->user,
            vault: $this->vault(),
            current: "note:{$this->note->uuid}",
            currentFolder: $this->note->folder,
        ));
    }

    public function isEditing(): bool
    {
        return $this->editing;
    }

    public function id(): string
    {
        return $this->note->uuid;
    }

    public function title(): string
    {
        return $this->note->title;
    }

    public function folderName(): string
    {
        return $this->note->folder->name;
    }

    public function body(): string
    {
        return (string) $this->note->body;
    }

    public function html(): string
    {
        return KnowledgeMarkdown::toHtml($this->note->body);
    }

    public function markdown(): string
    {
        return KnowledgeMarkdown::document($this->note->title, $this->note->body);
    }

    public function bodyHash(): string
    {
        return ToggleKnowledgeNoteTask::hash($this->note->body);
    }

    public function isEmpty(): bool
    {
        return blank($this->note->body);
    }

    public function editedAt(): CarbonInterface
    {
        return ($this->note->updated_at ?? $this->note->created_at)->copy()->setTimezone($this->user->timezone);
    }

    public function edited(): string
    {
        return __('Edited :when', ['when' => $this->editedAt()->diffForHumans()]);
    }

    /** @return list<array{id: string, name: string, isSelected: bool}> */
    public function folders(): array
    {
        return $this->vault()->knowledgeFolders()
            ->get()
            ->map(fn (KnowledgeFolder $folder): array => [
                'id' => $folder->uuid,
                'name' => $folder->name,
                'isSelected' => $folder->id === $this->note->knowledge_folder_id,
            ])
            ->values()
            ->all();
    }

    public function showUrl(): string
    {
        return route('knowledge.notes.show', [$this->vault(), $this->note]);
    }

    public function editUrl(): string
    {
        return route('knowledge.notes.edit', [$this->vault(), $this->note]);
    }

    public function updateUrl(): string
    {
        return route('knowledge.notes.update', [$this->vault(), $this->note]);
    }

    public function destroyUrl(): string
    {
        return route('knowledge.notes.destroy', [$this->vault(), $this->note]);
    }

    public function markdownUrl(): string
    {
        return route('knowledge.noteMarkdown.show', [$this->vault(), $this->note]);
    }

    public function taskUrl(): string
    {
        return route('knowledge.noteTasks.update', [$this->vault(), $this->note]);
    }

    public function deleteTitle(): string
    {
        return __('Delete the note “:title”?', ['title' => $this->note->title]);
    }
}
