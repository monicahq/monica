<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeNote;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Write a note in a vault's Knowledge. Without a folder named it goes to the vault's first folder,
 * and a vault without any folder gets "General" to hold it. Without a title it is "Untitled note".
 */
class CreateKnowledgeNote
{
    private KnowledgeNote $note;

    private string $cleanTitle;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly ?KnowledgeFolder $folder = null,
        private readonly ?string $title = null,
        private readonly ?string $body = null,
    ) {}

    public function execute(): KnowledgeNote
    {
        $this->validate();
        $this->create();
        $this->log();

        return $this->note;
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->folder instanceof KnowledgeFolder && $this->folder->vault_id !== $this->vault->id) {
            throw new ModelNotFoundException('This folder is in another vault.');
        }

        $this->cleanTitle = TextSanitizer::plainText($this->title ?? '');

        if ($this->cleanTitle === '') {
            $this->cleanTitle = __('Untitled note');
        }

        if (mb_strlen($this->cleanTitle) > 255) {
            throw ValidationException::withMessages(['title' => __('Keep the title under 255 characters.')]);
        }

        if (mb_strlen($this->body ?? '') > KnowledgeNote::MAX_BODY_LENGTH) {
            throw ValidationException::withMessages(['body' => __('Keep the text under 200,000 characters.')]);
        }
    }

    private function create(): void
    {
        DB::transaction(function (): void {
            $this->note = KnowledgeNote::query()->create([
                'account_id' => $this->vault->account_id,
                'vault_id' => $this->vault->id,
                'knowledge_folder_id' => $this->folderToHoldIt()->id,
                'title' => $this->cleanTitle,
                'body' => blank($this->body) ? null : $this->body,
            ]);
        });
    }

    private function folderToHoldIt(): KnowledgeFolder
    {
        if ($this->folder instanceof KnowledgeFolder) {
            return $this->folder;
        }

        return $this->vault->knowledgeFolders()->first()
            ?? new CreateKnowledgeFolder(user: $this->user, vault: $this->vault, name: __('General'))->execute();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::KnowledgeNoteCreation,
            parameters: ['name' => $this->note->title],
            loggable: $this->note,
        )->onQueue('low');
    }
}
