<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Transfer;

use App\Enums\AccountExportStatusEnum;
use App\Enums\AccountImportStatusEnum;
use App\Helpers\StorageSize;
use App\Models\AccountExport;
use App\Models\AccountImport;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Auth\ConfirmPasswordViewModel;
use Carbon\CarbonImmutable;

class AccountTransferViewModel
{
    public const int MAXIMUM_UPLOAD_IN_KILOBYTES = 524288;

    public function __construct(
        private readonly User $user,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function exportUrl(): string
    {
        return route('settings.accountExports.create');
    }

    public function refreshUrl(): string
    {
        return route('settings.accountImports.index');
    }

    public function isPreparingAnExport(): bool
    {
        return AccountExport::query()
            ->where('account_id', $this->user->account_id)
            ->where('status', AccountExportStatusEnum::Preparing)
            ->exists();
    }

    public function credentials(): ConfirmPasswordViewModel
    {
        return new ConfirmPasswordViewModel(user: $this->user);
    }

    public function exportRefusal(): ?string
    {
        $answer = $this->user->inspect('create', AccountExport::class);

        return $answer->denied() ? $answer->message() : null;
    }

    /** @return list<array{id: int, name: string}> */
    public function vaults(): array
    {
        return Vault::query()
            ->where('account_id', $this->user->account_id)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(fn (Vault $vault): array => ['id' => $vault->id, 'name' => $vault->name])
            ->all();
    }

    /** @return list<array{vaults: string, status: string, tone: string, size: string|null, created_at: CarbonImmutable, ready_at: CarbonImmutable|null, expires_at: CarbonImmutable|null, download_url: string|null}> */
    public function exports(): array
    {
        $vaultNames = Vault::query()
            ->where('account_id', $this->user->account_id)
            ->pluck('name', 'id');

        return AccountExport::query()
            ->where('account_id', $this->user->account_id)
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(function (AccountExport $export) use ($vaultNames): array {
                $names = collect($export->vault_ids)
                    ->map(fn (int $id): ?string => $vaultNames->get($id))
                    ->filter()
                    ->implode(', ');

                return [
                    'vaults' => $names === '' ? __('A vault since deleted') : $names,
                    'status' => __($export->status->label()),
                    'tone' => match ($export->status) {
                        AccountExportStatusEnum::Preparing => 'info',
                        AccountExportStatusEnum::Ready => 'success',
                        AccountExportStatusEnum::Failed => 'danger',
                        AccountExportStatusEnum::Expired => 'neutral',
                    },
                    'size' => $export->size_in_bytes === null ? null : StorageSize::readable($export->size_in_bytes),
                    'created_at' => $export->created_at,
                    'ready_at' => $export->ready_at,
                    'expires_at' => $export->expires_at,
                    'download_url' => $this->user->can('download', $export) ? route('settings.accountExportDownloads.show', $export) : null,
                ];
            })
            ->all();
    }

    public function uploadUrl(): string
    {
        return route('settings.accountImports.create');
    }

    public function maximumUpload(): string
    {
        return StorageSize::readable(self::MAXIMUM_UPLOAD_IN_KILOBYTES * 1024);
    }

    public function importRefusal(): ?string
    {
        $answer = $this->user->inspect('create', AccountImport::class);

        return $answer->denied() ? $answer->message() : null;
    }

    /** @return list<array{url: string, source: string, status: string, tone: string, created_at: CarbonImmutable}> */
    public function imports(): array
    {
        return AccountImport::query()
            ->where('account_id', $this->user->account_id)
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (AccountImport $import): array => [
                'url' => route('settings.accountImports.show', $import),
                'source' => $import->source_format?->label() ?? __('A file being read'),
                'status' => __($import->status->label()),
                'tone' => match ($import->status) {
                    AccountImportStatusEnum::Completed => 'success',
                    AccountImportStatusEnum::Refused, AccountImportStatusEnum::RolledBack, AccountImportStatusEnum::RollbackFailed => 'danger',
                    default => 'neutral',
                },
                'created_at' => $import->created_at,
            ])
            ->all();
    }
}
