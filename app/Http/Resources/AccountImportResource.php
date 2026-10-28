<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\AccountImportOutcomeEnum;
use App\Enums\AccountImportStatusEnum;
use App\Models\AccountImport;
use App\Models\AccountImportItem;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Override;

/** @mixin AccountImport */
class AccountImportResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'account_import',
            'id' => $this->uuid,
            'attributes' => [
                'status' => $this->status->value,
                'source_format' => $this->source_format?->value,
                'refusal' => $this->refusal,
                'summary' => $this->summaryByKind(),
                'already_imported_into' => $this->vaultsImportedIntoBefore(),
                'progress' => [
                    'done' => $this->items_done,
                    'total' => $this->items_total,
                ],
                'vault' => $this->vault?->uuid,
                'me_contact' => $this->me_source_uuid,
                'report' => $this->status === AccountImportStatusEnum::Completed ? $this->reportByKind() : null,
                'created_at' => $this->created_at->timestamp,
                'ended_at' => $this->ended_at?->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.accountImports.show', $this->resource),
            ],
        ];
    }

    /** @return list<array{kind: string, found: int, left_out: int}>|null */
    private function summaryByKind(): ?array
    {
        if ($this->summary === null) {
            return null;
        }

        $rows = [];

        foreach ($this->summary as $kind => $counts) {
            $rows[] = [
                'kind' => (string) $kind,
                'found' => (int) ($counts['found'] ?? 0),
                'left_out' => (int) ($counts['left_out'] ?? 0),
            ];
        }

        return $rows;
    }

    /** @return list<string> */
    private function vaultsImportedIntoBefore(): array
    {
        $earlierImports = AccountImport::query()
            ->where('account_id', $this->account_id)
            ->where('source_checksum', $this->source_checksum)
            ->where('status', AccountImportStatusEnum::Completed)
            ->whereKeyNot($this->id)
            ->select('vault_id');

        return Vault::query()
            ->whereIn('id', $earlierImports)
            ->orderBy('position')
            ->pluck('uuid')
            ->values()
            ->all();
    }

    /** @return list<array{kind: string, imported: int, left_out: int}> */
    private function reportByKind(): array
    {
        $rows = $this->items()
            ->whereNotIn('kind', AccountImportItem::KINDS_LEFT_OUT_OF_THE_REPORT)
            ->get(['kind', 'outcome'])
            ->groupBy('kind')
            ->map(fn (Collection $items, string $kind): array => [
                'kind' => $kind,
                'imported' => $items->filter(fn (AccountImportItem $item): bool => in_array($item->outcome, [AccountImportOutcomeEnum::Imported, AccountImportOutcomeEnum::MergedIntoMe], true))->count(),
                'left_out' => $items->filter(fn (AccountImportItem $item): bool => $item->outcome === AccountImportOutcomeEnum::LeftOut)->count(),
            ])
            ->values()
            ->all();

        $written = $this->writtenInsideItems();

        foreach ($this->summary ?? [] as $kind => $counts) {
            if (in_array($kind, AccountImportItem::SUMMARY_KINDS_WRITTEN_AS_ITEMS, true)) {
                continue;
            }

            $found = (int) ($counts['found'] ?? 0);
            $imported = min($found, $written[$kind] ?? 0);

            $rows[] = [
                'kind' => (string) $kind,
                'imported' => $imported,
                'left_out' => $found - $imported,
            ];
        }

        return $rows;
    }
}
