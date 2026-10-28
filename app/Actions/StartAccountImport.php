<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AccountImportStatusEnum;
use App\Exceptions\ImportExceedsALimit;
use App\Helpers\StorageSize;
use App\Jobs\WriteAccountImportBatch;
use App\Models\AccountImport;
use App\Models\AccountImportItem;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Start writing a file that has been read, once its owner has chosen where it goes and which of
 * its contacts is them, or, for a file of several vaults, once they confirm it. The account's limits
 * are compared with the whole file first, so an import crossing one is refused before anything is written.
 * The contact that is them is written into Me rather than created, so it does not count.
 */
class StartAccountImport
{
    private bool $holdsSeveralVaults = false;

    public function __construct(
        private readonly User $user,
        private readonly AccountImport $import,
        private readonly ?Vault $vault = null,
        private readonly ?string $vaultName = null,
        private readonly ?string $meSourceUuid = null,
    ) {}

    public function execute(): AccountImport
    {
        $this->holdsSeveralVaults = $this->import->holdsSeveralVaults();

        $this->validate();
        $this->refuseAboveALimit();

        DB::transaction(function (): void {
            $this->claim();
            $this->settleTheVault();
            $this->settleMe();
            $this->start();
        });

        WriteAccountImportBatch::dispatch($this->import, $this->import->writing_run)->afterCommit();

        return $this->import;
    }

    private function validate(): void
    {
        if ($this->import->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This import is in another account.');
        }

        if ($this->vault instanceof Vault && $this->vault->account_id !== $this->user->account_id) {
            throw new ModelNotFoundException('This vault is in another account.');
        }

        if ($this->import->status !== AccountImportStatusEnum::Read) {
            throw new InvalidArgumentException(__('This import is not waiting for a decision any more.'));
        }

        if ($this->holdsSeveralVaults && $this->vault instanceof Vault) {
            throw new InvalidArgumentException(__('This file holds several vaults, and each one arrives as a new vault of its own.'));
        }

        if ($this->holdsSeveralVaults) {
            return;
        }

        if (! $this->vault instanceof Vault && mb_trim((string) $this->vaultName) === '') {
            throw new InvalidArgumentException(__('Choose a vault, or name the new one.'));
        }

        if ($this->meSourceUuid === null) {
            return;
        }

        $isAContactOfTheFile = $this->import->items()
            ->where('kind', 'contact')
            ->where('source_uuid', $this->meSourceUuid)
            ->exists();

        if (! $isAContactOfTheFile) {
            throw new InvalidArgumentException(__('The contact chosen as you is not in this file.'));
        }
    }

    private function refuseAboveALimit(): void
    {
        $account = $this->user->account;
        $contacts = 0;
        $avatars = [];
        $joiningMe = $this->import->items()->where('kind', 'me')->pluck('source_uuid')->all();

        if (! $this->holdsSeveralVaults && $this->meSourceUuid !== null) {
            $joiningMe[] = $this->meSourceUuid;
        }

        foreach ($this->import->items()->reorder()->where('kind', 'contact')->select(['id', 'source_uuid', 'payload'])->lazyById(500) as $item) {
            $payload = $item->payload ?? [];
            $avatar = $payload['avatar_file'] ?? $payload['properties']['avatar_photo'] ?? null;
            $isCounted = ! (bool) ($payload['properties']['is_partial'] ?? false) && ! in_array($item->source_uuid, $joiningMe, true);

            if ($isCounted) {
                $contacts++;
            }

            if (is_string($avatar)) {
                $avatars[] = $avatar;
            }
        }

        $contactLimit = $account->contactLimit();

        if ($contactLimit !== null && $account->contactCount() + $contacts > $contactLimit) {
            throw new ImportExceedsALimit(trans_choice(
                'This file holds :count contact and the account already holds :held, more than the :limit the free plan allows. Full access lifts the ceiling.|This file holds :count contacts and the account already holds :held, more than the :limit the free plan allows. Full access lifts the ceiling.',
                $contacts,
                ['held' => $account->contactCount(), 'limit' => $contactLimit],
            ));
        }

        $vaultLimit = $account->vaultLimit();
        $newVaults = match (true) {
            $this->holdsSeveralVaults => $this->import->items()->where('kind', 'vault')->count(),
            $this->vault instanceof Vault => 0,
            default => 1,
        };

        if ($newVaults > 0 && $vaultLimit !== null && $account->vaults()->count() + $newVaults > $vaultLimit) {
            throw new ImportExceedsALimit(trans_choice(
                'This file holds :count vault and the account already holds :held, more than the :limit the free plan allows. Import into a vault you have, or get full access.|This file holds :count vaults and the account already holds :held, more than the :limit the free plan allows. Import into a vault you have, or get full access.',
                $newVaults,
                ['held' => $account->vaults()->count(), 'limit' => $vaultLimit],
            ));
        }

        $storageLimit = $account->storageLimitInBytes();

        if ($storageLimit === null) {
            return;
        }

        $fileSizes = [];

        foreach ($this->import->items()->reorder()->where('kind', 'file')->whereNull('outcome')->select(['id', 'source_uuid', 'payload'])->lazyById(500) as $item) {
            $fileSizes[(string) $item->source_uuid] = (int) ($item->payload['size_in_bytes'] ?? 0);
        }

        $avatarCopies = array_sum(array_map(fn (string $uuid): int => $fileSizes[$uuid] ?? 0, $avatars));
        $bytes = array_sum($fileSizes) + $avatarCopies;

        if ($account->storage_used_in_bytes + $bytes > $storageLimit) {
            throw new ImportExceedsALimit(__('The files in this export take :size, and the account has :left left. Full access raises the storage limit.', [
                'size' => StorageSize::readable($bytes),
                'left' => StorageSize::readable(max(0, $storageLimit - $account->storage_used_in_bytes)),
            ]));
        }
    }

    private function claim(): void
    {
        $status = AccountImport::query()->whereKey($this->import->id)->lockForUpdate()->firstOrFail()->status;

        if ($status !== AccountImportStatusEnum::Read) {
            throw new InvalidArgumentException(__('This import is not waiting for a decision any more.'));
        }
    }

    private function settleTheVault(): void
    {
        if ($this->holdsSeveralVaults) {
            return;
        }

        if ($this->vault instanceof Vault) {
            $this->import->vault_id = $this->vault->id;

            return;
        }

        $vault = new CreateVault(user: $this->user, name: (string) $this->vaultName)->execute();

        $this->import->vault_id = $vault->id;
        $this->import->created_vault_ids = [$vault->id];
    }

    private function settleMe(): void
    {
        if ($this->holdsSeveralVaults) {
            return;
        }

        $this->import->me_source_uuid = $this->meSourceUuid;

        if ($this->meSourceUuid === null) {
            return;
        }

        AccountImportItem::query()->create([
            'account_id' => $this->import->account_id,
            'account_import_id' => $this->import->id,
            'kind' => 'me',
            'source_uuid' => $this->meSourceUuid,
            'position' => AccountImportItem::RANKS['me'] * AccountImportItem::RANK_SPAN + 1,
            'payload' => [],
        ]);
    }

    private function start(): void
    {
        $this->import->status = AccountImportStatusEnum::Writing;
        $this->import->writing_run = (string) Str::uuid();
        $this->import->items_total = $this->import->items()->whereNull('outcome')->count();
        $this->import->items_done = 0;
        $this->import->save();
    }
}
