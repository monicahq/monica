<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\KnowledgeFolder;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

/**
 * Add a folder to a vault's Knowledge. It goes after the folders already there.
 */
class CreateKnowledgeFolder
{
    private KnowledgeFolder $folder;

    private string $cleanName;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly string $name,
    ) {}

    public function execute(): KnowledgeFolder
    {
        $this->validate();
        $this->create();
        $this->log();

        return $this->folder;
    }

    private function validate(): void
    {
        if ($this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        $this->cleanName = TextSanitizer::plainText($this->name);

        if ($this->cleanName === '') {
            throw ValidationException::withMessages(['name' => __('Give the folder a name.')]);
        }

        if (mb_strlen($this->cleanName) > 255) {
            throw ValidationException::withMessages(['name' => __('Keep the name under 255 characters.')]);
        }
    }

    private function create(): void
    {
        $this->folder = KnowledgeFolder::query()->create([
            'account_id' => $this->vault->account_id,
            'vault_id' => $this->vault->id,
            'name' => $this->cleanName,
            'position' => (int) KnowledgeFolder::query()->where('vault_id', $this->vault->id)->max('position') + 1,
        ]);
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::KnowledgeFolderCreation,
            parameters: ['name' => $this->folder->name],
            loggable: $this->folder,
        )->onQueue('low');
    }
}
