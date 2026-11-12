<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Possessions;

use App\Actions\CreatePossessionValuation;
use App\Actions\DestroyPossessionValuation;
use App\Enums\CurrencyEnum;
use App\Enums\PossessionValuationConfidenceEnum;
use App\Enums\PossessionValuationTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\PossessionValuationResource;
use App\Models\Possession;
use App\Models\PossessionValuation;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class PossessionValuationController extends Controller
{
    public function index(Request $request, string $vault, string $possession): JsonResponse
    {
        $record = $this->possession($this->vault($request, $vault), $possession);
        $record->load('valuations.documents');

        $valuations = $record->valuations
            ->each(fn (PossessionValuation $valuation) => $valuation->setRelation('possession', $record));

        return response()->json([
            'data' => PossessionValuationResource::collection($valuations)->resolve(),
        ]);
    }

    public function show(Request $request, string $vault, string $possession, string $valuation): JsonResponse
    {
        $record = $this->possession($this->vault($request, $vault), $possession);

        return new PossessionValuationResource($this->valuation($record, $valuation))->response();
    }

    public function create(Request $request, string $vault, string $possession): JsonResponse
    {
        $record = $this->possession($this->vault($request, $vault), $possession);

        $validated = $request->validate([
            'type' => ['required', Rule::enum(PossessionValuationTypeEnum::class)],
            'valued_on' => ['required', 'date'],
            'confidence' => ['nullable', Rule::enum(PossessionValuationConfidenceEnum::class)],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', Rule::enum(CurrencyEnum::class)],
            'valuer' => ['nullable', 'string', 'max:255'],
            'method' => ['nullable', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url', 'max:255'],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        $valuation = new CreatePossessionValuation(
            user: $request->user(),
            possession: $record,
            type: PossessionValuationTypeEnum::from($validated['type']),
            valuedOn: CarbonImmutable::parse($validated['valued_on']),
            amount: (string) $validated['amount'],
            currency: CurrencyEnum::from($validated['currency']),
            confidence: PossessionValuationConfidenceEnum::tryFrom($validated['confidence'] ?? '') ?? PossessionValuationConfidenceEnum::Unknown,
            valuer: $validated['valuer'] ?? null,
            method: $validated['method'] ?? null,
            referenceNumber: $validated['reference_number'] ?? null,
            sourceUrl: $validated['source_url'] ?? null,
            note: $validated['note'] ?? null,
        )->execute();

        return new PossessionValuationResource($valuation)->response()->setStatusCode(201);
    }

    public function destroy(Request $request, string $vault, string $possession, string $valuation): Response
    {
        $record = $this->valuation($this->possession($this->vault($request, $vault), $possession), $valuation);

        new DestroyPossessionValuation(
            user: $request->user(),
            valuation: $record,
        )->execute();

        return response()->noContent(204);
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

    private function valuation(Possession $possession, string $uuid): PossessionValuation
    {
        return PossessionValuation::query()
            ->where('possession_id', $possession->id)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
