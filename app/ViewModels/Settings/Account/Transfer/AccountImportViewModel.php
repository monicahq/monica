<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Transfer;

use App\Enums\AccountImportOutcomeEnum;
use App\Enums\AccountImportReasonEnum;
use App\Enums\AccountImportStatusEnum;
use App\Helpers\StorageSize;
use App\Models\AccountImport;
use App\Models\AccountImportItem;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class AccountImportViewModel
{
    public const int LEFT_OUT_SHOWN = 200;

    public function __construct(
        private readonly User $user,
        private readonly AccountImport $import,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function title(): string
    {
        return $this->import->source_format === null
            ? __('Import a file')
            : __('Import from :source', ['source' => $this->import->source_format->label()]);
    }

    /** @return array<int, array{label: string, url?: string}> */
    public function breadcrumb(): array
    {
        return [
            ['label' => __('Export and import'), 'url' => route('settings.accountImports.index')],
            ['label' => __('Import')],
        ];
    }

    public function status(): AccountImportStatusEnum
    {
        return $this->import->status;
    }

    public function isRefused(): bool
    {
        return $this->import->status === AccountImportStatusEnum::Refused;
    }

    public function isRead(): bool
    {
        return $this->import->status === AccountImportStatusEnum::Read;
    }

    public function isWriting(): bool
    {
        return $this->import->status === AccountImportStatusEnum::Writing;
    }

    public function isCompleted(): bool
    {
        return $this->import->status === AccountImportStatusEnum::Completed;
    }

    public function isMoving(): bool
    {
        return in_array($this->import->status, [
            AccountImportStatusEnum::Reading,
            AccountImportStatusEnum::Writing,
            AccountImportStatusEnum::RollingBack,
        ], true);
    }

    public function statusSentence(): string
    {
        return match ($this->import->status) {
            AccountImportStatusEnum::Reading => __('Reading the file. Nothing is written anywhere yet.'),
            AccountImportStatusEnum::Refused => __('This file cannot be imported.'),
            AccountImportStatusEnum::Read => __('The file is read. Nothing is written until you choose where it goes.'),
            AccountImportStatusEnum::Writing => trans_choice('[0,*]Writing :count of :total.', $this->import->items_done, ['total' => $this->import->items_total]),
            AccountImportStatusEnum::RollingBack => __('Removing what the import wrote.'),
            AccountImportStatusEnum::RolledBack => __('The import was undone. Your account is as it was before it started.'),
            AccountImportStatusEnum::RollbackFailed => __('The import could not be undone. What it wrote is still in the vault, and can be removed by hand.'),
            AccountImportStatusEnum::Completed => __('The import is finished.'),
        };
    }

    public function refusal(): ?string
    {
        return $this->import->refusal;
    }

    public function progressPercent(): int
    {
        if ($this->import->items_total === 0) {
            return 0;
        }

        return (int) floor($this->import->items_done * 100 / $this->import->items_total);
    }

    /** @return list<array{label: string, found: int, left_out: int}> */
    public function summary(): array
    {
        $rows = [];

        foreach ($this->import->summary ?? [] as $kind => $counts) {
            $rows[] = [
                'label' => $this->kindLabel((string) $kind),
                'found' => (int) ($counts['found'] ?? 0),
                'left_out' => (int) ($counts['left_out'] ?? 0),
            ];
        }

        return $rows;
    }

    public function sourceName(): string
    {
        return $this->import->source_format?->label() ?? '';
    }

    /** @return list<string> */
    public function vaultsImportedIntoBefore(): array
    {
        return AccountImport::query()
            ->where('account_id', $this->import->account_id)
            ->where('source_checksum', $this->import->source_checksum)
            ->where('status', AccountImportStatusEnum::Completed)
            ->whereKeyNot($this->import->id)
            ->with('vault')
            ->get()
            ->map(fn (AccountImport $earlier): string => $earlier->vault->name ?? __('A vault since deleted'))
            ->unique()
            ->values()
            ->all();
    }

    public function holdsSeveralVaults(): bool
    {
        return $this->import->holdsSeveralVaults();
    }

    public function chosenVault(): string
    {
        $vault = old('vault', '');

        return is_scalar($vault) ? (string) $vault : '';
    }

    /** @return list<array{id: int, name: string}> */
    public function vaults(): array
    {
        return Vault::query()
            ->where('account_id', $this->user->account_id)
            ->orderBy('position')
            ->get()
            ->map(fn (Vault $vault): array => ['id' => $vault->id, 'name' => $vault->name])
            ->all();
    }

    public function meSourceUuid(): ?string
    {
        return $this->import->me_source_uuid;
    }

    /** @return list<array{uuid: string, name: string}> */
    public function contactsOfTheFile(): array
    {
        return $this->import->items()
            ->reorder()
            ->where('kind', 'contact')
            ->orderBy('name')
            ->orderBy('id')
            ->get(['source_uuid', 'name'])
            ->map(fn (AccountImportItem $item): array => [
                'uuid' => (string) $item->source_uuid,
                'name' => (string) $item->name,
            ])
            ->all();
    }

    /** @return list<array{reason: string, count: int}> */
    public function reasonsForLeavingOut(): array
    {
        $rows = $this->import->items()
            ->reorder()
            ->where('outcome', AccountImportOutcomeEnum::LeftOut)
            ->whereNotNull('reason')
            ->groupBy('reason')
            ->orderByRaw('min(id)')
            ->selectRaw('reason, count(*) as total')
            ->toBase()
            ->get()
            ->map(fn (object $row): array => [
                'reason' => __(AccountImportReasonEnum::tryFrom((string) $row->reason)?->label() ?? ''),
                'count' => (int) $row->total,
            ])
            ->all();

        $notKept = 0;

        foreach ($this->import->summary ?? [] as $kind => $counts) {
            if (in_array($kind, AccountImportItem::SUMMARY_KINDS_WRITTEN_AS_ITEMS, true)) {
                continue;
            }

            $notKept += (int) ($counts['left_out'] ?? 0);
        }

        if ($notKept > 0) {
            $rows[] = ['reason' => __('Nothing of this kind is kept in a vault.'), 'count' => $notKept];
        }

        return $rows;
    }

    /** @return list<array{label: string, imported: int, left_out: int}> */
    public function report(): array
    {
        if (! $this->isCompleted()) {
            return [];
        }

        $rows = $this->import->items()
            ->reorder()
            ->whereNotIn('kind', AccountImportItem::KINDS_LEFT_OUT_OF_THE_REPORT)
            ->groupBy('kind', 'outcome')
            ->orderByRaw('min(id)')
            ->selectRaw('kind, outcome, count(*) as total')
            ->toBase()
            ->get()
            ->groupBy('kind')
            ->map(fn (Collection $rows, string $kind): array => [
                'label' => $this->kindLabel($kind),
                'imported' => (int) $rows->whereIn('outcome', [AccountImportOutcomeEnum::Imported->value, AccountImportOutcomeEnum::MergedIntoMe->value])->sum('total'),
                'left_out' => (int) $rows->where('outcome', AccountImportOutcomeEnum::LeftOut->value)->sum('total'),
            ])
            ->values()
            ->all();

        $written = $this->import->writtenInsideItems();

        foreach ($this->import->summary ?? [] as $kind => $counts) {
            if (in_array($kind, AccountImportItem::SUMMARY_KINDS_WRITTEN_AS_ITEMS, true)) {
                continue;
            }

            $found = (int) ($counts['found'] ?? 0);
            $imported = min($found, $written[$kind] ?? 0);

            $rows[] = [
                'label' => $this->kindLabel((string) $kind),
                'imported' => $imported,
                'left_out' => $found - $imported,
            ];
        }

        return $rows;
    }

    /** @return list<array{kind: string, name: string, reason: string}> */
    public function leftOut(): array
    {
        return $this->import->items()
            ->where('outcome', AccountImportOutcomeEnum::LeftOut)
            ->limit(self::LEFT_OUT_SHOWN)
            ->get()
            ->map(fn (AccountImportItem $item): array => [
                'kind' => $this->kindLabel($item->kind),
                'name' => $this->nameIn($item->payload ?? []),
                'reason' => __(AccountImportReasonEnum::tryFrom((string) $item->reason)?->label() ?? ''),
            ])
            ->all();
    }

    public function leftOutListNote(): ?string
    {
        $count = $this->import->items()->where('outcome', AccountImportOutcomeEnum::LeftOut)->count();

        if ($count <= self::LEFT_OUT_SHOWN) {
            return null;
        }

        return trans_choice(':count thing was left out, and the first :shown are listed here.|:count things were left out, and the first :shown are listed here.', $count, ['shown' => self::LEFT_OUT_SHOWN]);
    }

    public function vaultUrl(): ?string
    {
        return $this->import->vault === null ? null : route('vaults.show', $this->import->vault);
    }

    public function startUrl(): string
    {
        return route('settings.accountImports.update', $this->import);
    }

    public function cancelUrl(): string
    {
        return route('settings.accountImports.destroy', $this->import);
    }

    public function refreshUrl(): string
    {
        return route('settings.accountImports.show', $this->import);
    }

    public function storageNote(): ?string
    {
        $limit = $this->user->account->storageLimitInBytes();

        if ($limit === null) {
            return null;
        }

        return __('This account has :left of storage left.', ['left' => StorageSize::readable(max(0, $limit - $this->user->account->storage_used_in_bytes))]);
    }

    /** @param array<string, mixed> $payload */
    private function nameIn(array $payload): string
    {
        $name = implode(' ', array_filter([
            Arr::get($payload, 'properties.first_name'),
            Arr::get($payload, 'properties.last_name'),
        ]));

        return $name !== '' ? $name : (string) (Arr::get($payload, 'original_filename') ?? Arr::get($payload, 'reminder.title') ?? Arr::get($payload, 'name') ?? Arr::get($payload, 'type') ?? '');
    }

    private function kindLabel(string $kind): string
    {
        return match ($kind) {
            'contact', 'contacts' => __('Contacts'),
            'contact_fields' => __('Emails, phones and links'),
            'addresses' => __('Addresses'),
            'notes' => __('Notes'),
            'gifts' => __('Gifts'),
            'debts' => __('Debts'),
            'tasks' => __('Tasks'),
            'tags' => __('Tags'),
            'reminder', 'reminders' => __('Reminders'),
            'life_event', 'life_events' => __('Life events'),
            'call', 'calls' => __('Calls'),
            'conversation', 'conversations' => __('Conversations'),
            'pet', 'pets' => __('Pets'),
            'activity', 'activities' => __('Activities'),
            'relationship', 'relationships' => __('Relationships'),
            'file', 'files' => __('Photos and documents'),
            'photos' => __('Photos'),
            'documents' => __('Documents'),
            'journal_entry', 'journal_entries' => __('Journal entries'),
            'day_ratings' => __('Day ratings'),
            'life_event_type', 'life_event_types' => __('Life event types'),
            'relationship_type', 'relationship_types' => __('Relationship types'),
            'vault', 'vaults' => __('Vaults'),
            'structure', 'templates' => __('Templates'),
            'entity', 'entities' => __('Other records'),
            'related_values' => __('Values pointing at another record'),
            'users' => __('Users of the old account'),
            'address_books' => __('Address books'),
            'address_book_subscriptions' => __('Address book subscriptions'),
            'modules' => __('Modules'),
            'reminder_rules' => __('Reminder rules'),
            'audit_logs' => __('History of changes'),
            default => $kind,
        };
    }
}
