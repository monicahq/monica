<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Possessions;

use App\Actions\CreatePossessionProvenanceEvent;
use App\Actions\DestroyPossessionProvenanceEvent;
use App\Actions\UpdatePossessionProvenanceEvent;
use App\Enums\PossessionProvenanceDatePrecisionEnum;
use App\Enums\PossessionProvenanceEventTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\PossessionProvenanceEventResource;
use App\Models\Possession;
use App\Models\PossessionProvenanceEvent;
use App\Models\PossessionTransaction;
use App\Models\Vault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class PossessionProvenanceEventController extends Controller
{
    public function index(Request $request, string $vault, string $possession): JsonResponse
    {
        $record = $this->possession($this->vault($request, $vault), $possession);
        $record->load('provenanceEvents.transaction');

        $events = $record->provenanceEvents
            ->each(fn (PossessionProvenanceEvent $event) => $event->setRelation('possession', $record));

        return response()->json([
            'data' => PossessionProvenanceEventResource::collection($events)->resolve(),
        ]);
    }

    public function show(Request $request, string $vault, string $possession, string $event): JsonResponse
    {
        $record = $this->possession($this->vault($request, $vault), $possession);

        return new PossessionProvenanceEventResource($this->event($record, $event))->response();
    }

    public function create(Request $request, string $vault, string $possession): JsonResponse
    {
        $record = $this->possession($this->vault($request, $vault), $possession);
        $validated = $request->validate($this->rules($record, typeRequired: true));

        $event = new CreatePossessionProvenanceEvent(
            user: $request->user(),
            possession: $record,
            type: PossessionProvenanceEventTypeEnum::from($validated['type']),
            datePrecision: PossessionProvenanceDatePrecisionEnum::tryFrom($validated['date_precision'] ?? '') ?? PossessionProvenanceDatePrecisionEnum::Day,
            occurredOn: $validated['occurred_on'] ?? null,
            title: $validated['title'] ?? null,
            from: $validated['from'] ?? null,
            to: $validated['to'] ?? null,
            location: $validated['location'] ?? null,
            description: $validated['description'] ?? null,
            referenceNumber: $validated['reference_number'] ?? null,
            sourceUrl: $validated['source_url'] ?? null,
            transaction: $this->transaction($record, $validated['transaction_id'] ?? null),
            isVerified: (bool) ($validated['is_verified'] ?? false),
        )->execute();

        return new PossessionProvenanceEventResource($event)->response()->setStatusCode(201);
    }

    public function update(Request $request, string $vault, string $possession, string $event): JsonResponse
    {
        $possessionRecord = $this->possession($this->vault($request, $vault), $possession);
        $record = $this->event($possessionRecord, $event);
        $validated = $request->validate($this->rules($possessionRecord, typeRequired: false));
        $kept = fn (string $field): ?string => array_key_exists($field, $validated)
            ? ($validated[$field] === null ? null : (string) $validated[$field])
            : $record->getRawOriginal($field);
        $datePrecision = PossessionProvenanceDatePrecisionEnum::tryFrom($validated['date_precision'] ?? '') ?? $record->date_precision;

        new UpdatePossessionProvenanceEvent(
            user: $request->user(),
            event: $record,
            type: PossessionProvenanceEventTypeEnum::tryFrom($validated['type'] ?? '') ?? $record->type,
            datePrecision: $datePrecision,
            occurredOn: array_key_exists('occurred_on', $validated)
                ? $validated['occurred_on']
                : $datePrecision->format($record->occurred_on),
            title: $kept('title'),
            from: $kept('from'),
            to: $kept('to'),
            location: $kept('location'),
            description: $kept('description'),
            referenceNumber: $kept('reference_number'),
            sourceUrl: $kept('source_url'),
            transaction: array_key_exists('transaction_id', $validated)
                ? $this->transaction($possessionRecord, $validated['transaction_id'])
                : $record->transaction,
            isVerified: array_key_exists('is_verified', $validated) ? (bool) $validated['is_verified'] : $record->is_verified,
        )->execute();

        return new PossessionProvenanceEventResource($record->refresh())->response();
    }

    public function destroy(Request $request, string $vault, string $possession, string $event): Response
    {
        $record = $this->event($this->possession($this->vault($request, $vault), $possession), $event);

        new DestroyPossessionProvenanceEvent(
            user: $request->user(),
            event: $record,
        )->execute();

        return response()->noContent(204);
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(Possession $possession, bool $typeRequired): array
    {
        return [
            'type' => [$typeRequired ? 'required' : 'sometimes', Rule::enum(PossessionProvenanceEventTypeEnum::class)],
            'date_precision' => ['sometimes', Rule::enum(PossessionProvenanceDatePrecisionEnum::class)],
            'occurred_on' => ['nullable', 'string', 'max:10'],
            'title' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'string', 'max:255'],
            'to' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url', 'max:255'],
            'transaction_id' => ['nullable', 'string', Rule::exists('possession_transactions', 'uuid')->where('possession_id', $possession->id)],
            'is_verified' => ['sometimes', 'boolean'],
        ];
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

    private function event(Possession $possession, string $uuid): PossessionProvenanceEvent
    {
        return PossessionProvenanceEvent::query()
            ->where('possession_id', $possession->id)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    private function transaction(Possession $possession, ?string $uuid): ?PossessionTransaction
    {
        if ($uuid === null) {
            return null;
        }

        return PossessionTransaction::query()
            ->where('possession_id', $possession->id)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
