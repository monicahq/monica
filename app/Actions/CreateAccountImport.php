<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AccountImportStatusEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Jobs\ReadAccountImport;
use App\Models\Account;
use App\Models\AccountImport;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Take a file somebody wants to bring into their account, keep it under the account's prefix, and
 * have it read in the background. Nothing reaches a vault until the person has seen what the file
 * holds and confirmed where it goes.
 */
class CreateAccountImport
{
    private AccountImport $import;

    public function __construct(
        private readonly User $user,
        private readonly UploadedFile $file,
    ) {}

    public function execute(): AccountImport
    {
        DB::transaction(function (): void {
            Account::query()->whereKey($this->user->account_id)->lockForUpdate()->firstOrFail();

            $this->user->authorize('create', AccountImport::class);

            $this->create();
            $this->log();
        });

        ReadAccountImport::dispatch($this->import)->afterCommit();

        return $this->import;
    }

    private function create(): void
    {
        $disk = (string) config('filesystems.default');

        $this->import = new AccountImport([
            'account_id' => $this->user->account_id,
            'user_id' => $this->user->id,
            'source_checksum' => (string) hash_file('sha256', (string) $this->file->getRealPath()),
            'disk' => $disk,
            'path' => '',
            'status' => AccountImportStatusEnum::Reading,
            'created_vault_ids' => [],
        ]);
        $this->import->uuid = (string) Str::uuid();

        DB::afterRollBack(fn (): bool => Storage::disk($disk)->deleteDirectory($this->import->directory()));

        $this->import->path = (string) $this->file->storeAs($this->import->directory(), 'upload.json', ['disk' => $disk]);
        $this->import->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::AccountImportStarted,
            loggable: $this->import,
        );
    }
}
