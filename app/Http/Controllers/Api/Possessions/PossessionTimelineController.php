<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Possessions;

use App\Enums\PossessionTimelineSectionEnum;
use App\Helpers\PossessionTimeline;
use App\Helpers\PossessionTimelineEntry;
use App\Http\Controllers\Controller;
use App\Models\Possession;
use App\Models\Vault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PossessionTimelineController extends Controller
{
    public function index(Request $request, string $vault, string $possession): JsonResponse
    {
        $vault = $this->vault($request, $vault);
        $record = $this->possession($vault, $possession);

        $validated = $request->validate([
            'section' => ['nullable', Rule::enum(PossessionTimelineSectionEnum::class)],
        ]);

        $section = PossessionTimelineSectionEnum::tryFrom($validated['section'] ?? '');

        return response()->json([
            'data' => array_map(fn (PossessionTimelineEntry $entry): array => [
                'type' => 'possession_timeline_entry',
                'attributes' => [
                    'section' => $entry->section->value,
                    'kind' => $entry->kind->value,
                    'date' => $entry->date?->toDateString(),
                    'date_precision' => $entry->precision->value,
                    'is_current' => $entry->isCurrent,
                    'is_acquisition' => $entry->isAcquisition,
                    'record_id' => $entry->record->uuid,
                ],
                'links' => [
                    'record' => $this->recordUrl($vault, $record, $entry),
                ],
            ], new PossessionTimeline($record)->entries($section)),
        ]);
    }

    private function recordUrl(Vault $vault, Possession $possession, PossessionTimelineEntry $entry): string
    {
        [$name, $parameter] = match ($entry->section) {
            PossessionTimelineSectionEnum::Transactions => ['api.v1.possessions.transactions.show', 'transaction'],
            PossessionTimelineSectionEnum::Insurance => ['api.v1.possessions.insuranceRecords.show', 'insuranceRecord'],
            PossessionTimelineSectionEnum::Valuations => ['api.v1.possessions.valuations.show', 'valuation'],
            PossessionTimelineSectionEnum::Provenance => ['api.v1.possessions.provenance.show', 'event'],
            PossessionTimelineSectionEnum::Maintenance => ['api.v1.possessions.maintenanceRecords.show', 'maintenanceRecord'],
            PossessionTimelineSectionEnum::Loans => ['api.v1.possessions.loans.show', 'loan'],
            PossessionTimelineSectionEnum::Locations => ['api.v1.possessions.locationStays.show', 'stay'],
        };

        return route($name, ['vault' => $vault->uuid, 'possession' => $possession->uuid, $parameter => $entry->record->uuid]);
    }

    private function vault(Request $request, string $address): Vault
    {
        return $request->user()->account->vaults()
            ->addressedBy($address)
            ->firstOrFail();
    }

    private function possession(Vault $vault, string $uuid): Possession
    {
        return Possession::query()
            ->where('vault_id', $vault->id)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
