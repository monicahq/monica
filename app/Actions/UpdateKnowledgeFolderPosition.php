<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\KnowledgeFolder;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Move a folder in the order of its vault's Knowledge. A position past the end puts it last, and
 * the folders keep their positions from one upward without a gap.
 */
class UpdateKnowledgeFolderPosition
{
    public function __construct(
        private readonly User $user,
        private readonly KnowledgeFolder $folder,
        private readonly int $position,
    ) {}

    public function execute(): KnowledgeFolder
    {
        $this->validate();
        $this->update();
        $this->log();

        return $this->folder->refresh();
    }

    private function validate(): void
    {
        if ($this->folder->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This folder is in another account.');
        }
    }

    private function update(): void
    {
        $folders = $this->folder->vault->knowledgeFolders()
            ->get()
            ->reject(fn (KnowledgeFolder $folder): bool => $folder->is($this->folder))
            ->values();

        $folders->splice(max(0, $this->position - 1), 0, [$this->folder]);

        DB::transaction(function () use ($folders): void {
            $folders->each(function (KnowledgeFolder $folder, int $index): void {
                $folder->position = $index + 1;
                $folder->save();
            });
        });
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::KnowledgeFolderPositionUpdate,
            parameters: ['name' => $this->folder->name],
            loggable: $this->folder,
        )->onQueue('low');
    }
}
