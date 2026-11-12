<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\KnowledgeNote;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Delete a folder of a vault's Knowledge. Its notes and lists move to the folder named, or are
 * deleted with it when none is. The last folder of a vault cannot be deleted, and the folders left
 * keep their positions without a gap.
 */
class DestroyKnowledgeFolder
{
    private int $documentCount;

    public function __construct(
        private readonly User $user,
        private readonly KnowledgeFolder $folder,
        private readonly ?KnowledgeFolder $moveTo = null,
    ) {}

    public function execute(): void
    {
        $this->validate();
        $this->destroy();
        $this->log();
    }

    private function validate(): void
    {
        if ($this->folder->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This folder is in another account.');
        }

        if ($this->moveTo instanceof KnowledgeFolder && $this->moveTo->vault_id !== $this->folder->vault_id) {
            throw new ModelNotFoundException('This folder is in another vault.');
        }

        if ($this->moveTo instanceof KnowledgeFolder && $this->moveTo->is($this->folder)) {
            throw ValidationException::withMessages(['move_to' => __('Choose another folder to move the notes and lists to.')]);
        }

        $this->user->authorize('delete', $this->folder);
    }

    private function destroy(): void
    {
        $this->documentCount = $this->folder->notes()->count() + $this->folder->lists()->count();

        DB::transaction(function (): void {
            if ($this->moveTo instanceof KnowledgeFolder) {
                KnowledgeNote::query()->where('knowledge_folder_id', $this->folder->id)->update(['knowledge_folder_id' => $this->moveTo->id]);
                KnowledgeList::query()->where('knowledge_folder_id', $this->folder->id)->update(['knowledge_folder_id' => $this->moveTo->id]);
            }

            $this->folder->delete();

            KnowledgeFolder::query()
                ->where('vault_id', $this->folder->vault_id)
                ->orderBy('position')
                ->orderBy('id')
                ->get()
                ->each(function (KnowledgeFolder $folder, int $index): void {
                    $folder->position = $index + 1;
                    $folder->save();
                });
        });
    }

    private function log(): void
    {
        $deletedDocuments = ! $this->moveTo instanceof KnowledgeFolder && $this->documentCount > 0;

        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: $deletedDocuments ? UserActionEnum::KnowledgeFolderDeletedWithDocuments : UserActionEnum::KnowledgeFolderDeletion,
            parameters: $deletedDocuments ? ['name' => $this->folder->name, 'count' => $this->documentCount] : ['name' => $this->folder->name],
            loggable: $this->folder,
        )->onQueue('low');
    }
}
