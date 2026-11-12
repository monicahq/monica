<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

/**
 * Change a list of a vault's Knowledge: its title, its description, or the folder it sits in. What
 * is not given stays as it is, and a blank title keeps the previous one.
 */
class UpdateKnowledgeList
{
    public function __construct(
        private readonly User $user,
        private readonly KnowledgeList $list,
        private readonly ?string $title = null,
        private readonly ?string $description = null,
        private readonly ?KnowledgeFolder $folder = null,
    ) {}

    public function execute(): KnowledgeList
    {
        $this->validate();
        $this->update();
        $this->log();

        return $this->list;
    }

    private function validate(): void
    {
        if ($this->list->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This list is in another account.');
        }

        if ($this->folder instanceof KnowledgeFolder && $this->folder->vault_id !== $this->list->vault_id) {
            throw new ModelNotFoundException('This folder is in another vault.');
        }

        if ($this->title !== null && mb_strlen(TextSanitizer::plainText($this->title)) > 255) {
            throw ValidationException::withMessages(['title' => __('Keep the title under 255 characters.')]);
        }

        if (mb_strlen($this->description ?? '') > KnowledgeList::MAX_DESCRIPTION_LENGTH) {
            throw ValidationException::withMessages(['description' => __('Keep the description under 5,000 characters.')]);
        }
    }

    private function update(): void
    {
        $title = TextSanitizer::plainText($this->title ?? '');

        if ($title !== '') {
            $this->list->title = $title;
        }

        if ($this->description !== null) {
            $this->list->description = blank($this->description) ? null : $this->description;
        }

        if ($this->folder instanceof KnowledgeFolder) {
            $this->list->knowledge_folder_id = $this->folder->id;
        }

        $this->list->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::KnowledgeListUpdate,
            parameters: ['name' => $this->list->title],
            loggable: $this->list,
        )->onQueue('low');
    }
}
