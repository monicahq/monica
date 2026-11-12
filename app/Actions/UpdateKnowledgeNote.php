<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeNote;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

/**
 * Change a note of a vault's Knowledge: its title, its text, or the folder it sits in. What is not
 * given stays as it is, and a blank title keeps the previous one. Moving a note changes nothing else.
 */
class UpdateKnowledgeNote
{
    public function __construct(
        private readonly User $user,
        private readonly KnowledgeNote $note,
        private readonly ?string $title = null,
        private readonly ?string $body = null,
        private readonly ?KnowledgeFolder $folder = null,
    ) {}

    public function execute(): KnowledgeNote
    {
        $this->validate();
        $this->update();
        $this->log();

        return $this->note;
    }

    private function validate(): void
    {
        if ($this->note->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This note is in another account.');
        }

        if ($this->folder instanceof KnowledgeFolder && $this->folder->vault_id !== $this->note->vault_id) {
            throw new ModelNotFoundException('This folder is in another vault.');
        }

        if ($this->title !== null && mb_strlen(TextSanitizer::plainText($this->title)) > 255) {
            throw ValidationException::withMessages(['title' => __('Keep the title under 255 characters.')]);
        }

        if (mb_strlen($this->body ?? '') > KnowledgeNote::MAX_BODY_LENGTH) {
            throw ValidationException::withMessages(['body' => __('Keep the text under 200,000 characters.')]);
        }
    }

    private function update(): void
    {
        $title = TextSanitizer::plainText($this->title ?? '');

        if ($title !== '') {
            $this->note->title = $title;
        }

        if ($this->body !== null) {
            $this->note->body = blank($this->body) ? null : $this->body;
        }

        if ($this->folder instanceof KnowledgeFolder) {
            $this->note->knowledge_folder_id = $this->folder->id;
        }

        $this->note->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::KnowledgeNoteUpdate,
            parameters: ['name' => $this->note->title],
            loggable: $this->note,
        )->onQueue('low');
    }
}
