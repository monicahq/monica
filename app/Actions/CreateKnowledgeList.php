<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\KnowledgeFolder;
use App\Models\KnowledgeList;
use App\Models\KnowledgeListColumn;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Start a list in a vault's Knowledge, with the columns "Name" and "Note". Without a folder named it
 * goes to the vault's first folder, and a vault without any folder gets "General" to hold it.
 * Without a title it is "Untitled list".
 */
class CreateKnowledgeList
{
    private KnowledgeList $list;

    private string $cleanTitle;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly ?KnowledgeFolder $folder = null,
        private readonly ?string $title = null,
        private readonly ?string $description = null,
    ) {}

    public function execute(): KnowledgeList
    {
        $this->validate();
        $this->create();
        $this->log();

        return $this->list;
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
            $this->cleanTitle = __('Untitled list');
        }

        if (mb_strlen($this->cleanTitle) > 255) {
            throw ValidationException::withMessages(['title' => __('Keep the title under 255 characters.')]);
        }

        if (mb_strlen($this->description ?? '') > KnowledgeList::MAX_DESCRIPTION_LENGTH) {
            throw ValidationException::withMessages(['description' => __('Keep the description under 5,000 characters.')]);
        }
    }

    private function create(): void
    {
        DB::transaction(function (): void {
            $folder = $this->folder
                ?? $this->vault->knowledgeFolders()->first()
                ?? new CreateKnowledgeFolder(user: $this->user, vault: $this->vault, name: __('General'))->execute();

            $this->list = KnowledgeList::query()->create([
                'account_id' => $this->vault->account_id,
                'vault_id' => $this->vault->id,
                'knowledge_folder_id' => $folder->id,
                'title' => $this->cleanTitle,
                'description' => blank($this->description) ? null : $this->description,
            ]);

            foreach ([__('Name'), __('Note')] as $index => $name) {
                KnowledgeListColumn::query()->create([
                    'account_id' => $this->list->account_id,
                    'vault_id' => $this->list->vault_id,
                    'knowledge_list_id' => $this->list->id,
                    'name' => $name,
                    'position' => $index + 1,
                ]);
            }
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::KnowledgeListCreation,
            parameters: ['name' => $this->list->title],
            loggable: $this->list,
        )->onQueue('low');
    }
}
