<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\KnowledgeFolder;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

/**
 * Rename a folder of a vault's Knowledge. A blank name keeps the one it had.
 */
class UpdateKnowledgeFolder
{
    public function __construct(
        private readonly User $user,
        private readonly KnowledgeFolder $folder,
        private string $name,
    ) {}

    public function execute(): KnowledgeFolder
    {
        $this->sanitize();
        $this->validate();

        if ($this->name === '') {
            return $this->folder;
        }

        $this->update();
        $this->log();

        return $this->folder;
    }

    private function sanitize(): void
    {
        $this->name = TextSanitizer::plainText($this->name);
    }

    private function validate(): void
    {
        if ($this->folder->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This folder is in another account.');
        }

        if (mb_strlen($this->name) > 255) {
            throw ValidationException::withMessages(['name' => __('Keep the name under 255 characters.')]);
        }
    }

    private function update(): void
    {
        $this->folder->name = $this->name;
        $this->folder->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::KnowledgeFolderUpdate,
            parameters: ['name' => $this->folder->name],
            loggable: $this->folder,
        )->onQueue('low');
    }
}
