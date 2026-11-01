<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\AccountExport;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;

/**
 * Hand the file of a ready export to its account holder, and write the download in the changelog.
 */
class DownloadAccountExport
{
    public function __construct(
        private readonly User $user,
        private readonly AccountExport $export,
    ) {}

    public function execute(): AccountExport
    {
        $this->validate();
        $this->log();

        return $this->export;
    }

    private function validate(): void
    {
        if ($this->export->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This export is in another account.');
        }

        $this->user->authorize('download', $this->export);

        if (! Storage::disk($this->export->disk)->exists($this->export->path)) {
            throw new ModelNotFoundException('The file of this export is no longer there.');
        }
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::AccountExportDownloaded,
            parameters: ['count' => count($this->export->vault_ids)],
            loggable: $this->export,
        );
    }
}
