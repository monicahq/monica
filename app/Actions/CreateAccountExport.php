<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AccountExportStatusEnum;
use App\Enums\UserActionEnum;
use App\Jobs\BuildAccountExport;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\AccountExport;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Ask for a file holding everything in the chosen vaults, or in every vault of the account, and have
 * it prepared in the background.
 */
class CreateAccountExport
{
    private AccountExport $export;

    /** @var list<int> */
    private array $coveredVaultIds;

    /** @param  list<int>|null  $vaultIds */
    public function __construct(
        private readonly User $user,
        private readonly ?array $vaultIds = null,
    ) {}

    public function execute(): AccountExport
    {
        DB::transaction(function (): void {
            Account::query()->whereKey($this->user->account_id)->lockForUpdate()->firstOrFail();

            $this->user->authorize('create', AccountExport::class);

            $this->validate();
            $this->create();
            $this->log();
        });

        BuildAccountExport::dispatch($this->export)->afterCommit();

        return $this->export;
    }

    private function validate(): void
    {
        $vaults = Vault::query()
            ->where('account_id', $this->user->account_id)
            ->orderBy('position')
            ->orderBy('id');

        if ($this->vaultIds === null) {
            $this->coveredVaultIds = $vaults->pluck('id')->all();

            return;
        }

        $this->coveredVaultIds = $vaults->whereIn('id', $this->vaultIds)->pluck('id')->all();

        if (count($this->coveredVaultIds) !== count(array_unique($this->vaultIds))) {
            throw new ModelNotFoundException('A vault chosen for the export is in another account.');
        }
    }

    private function create(): void
    {
        $this->export = new AccountExport([
            'account_id' => $this->user->account_id,
            'user_id' => $this->user->id,
            'vault_ids' => $this->coveredVaultIds,
            'status' => AccountExportStatusEnum::Preparing,
            'disk' => (string) config('filesystems.default'),
            'path' => '',
        ]);
        $this->export->uuid = (string) Str::uuid();
        $day = now()->format('Y-m-d');
        $this->export->path = "{$this->export->directory()}/monica-export-{$day}.json";
        $this->export->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::AccountExportRequested,
            parameters: ['count' => count($this->coveredVaultIds)],
            loggable: $this->export,
        )->onQueue('low');
    }
}
