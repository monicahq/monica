<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Possessions;

use App\Actions\CreatePossessionMaintenanceRecord;
use App\Actions\DestroyPossessionMaintenanceRecord;
use App\Actions\UpdatePossessionMaintenanceRecord;
use App\Enums\CurrencyEnum;
use App\Enums\PossessionMaintenanceTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\PossessionMaintenanceRecordResource;
use App\Models\Possession;
use App\Models\PossessionCondition;
use App\Models\PossessionMaintenanceRecord;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class PossessionMaintenanceRecordController extends Controller
{
    public function index(Request $request, string $vault, string $possession): JsonResponse
    {
        $record = $this->possession($this->vault($request, $vault), $possession);
        $record->load('maintenanceRecords.conditionBefore', 'maintenanceRecords.conditionAfter');

        $maintenanceRecords = $record->maintenanceRecords
            ->each(fn (PossessionMaintenanceRecord $maintenanceRecord) => $maintenanceRecord->setRelation('possession', $record));

        return response()->json([
            'data' => PossessionMaintenanceRecordResource::collection($maintenanceRecords)->resolve(),
        ]);
    }

    public function show(Request $request, string $vault, string $possession, string $maintenanceRecord): JsonResponse
    {
        $record = $this->possession($this->vault($request, $vault), $possession);

        return new PossessionMaintenanceRecordResource($this->maintenanceRecord($record, $maintenanceRecord))->response();
    }

    public function create(Request $request, string $vault, string $possession): JsonResponse
    {
        $record = $this->possession($this->vault($request, $vault), $possession);

        $validated = $request->validate($this->rules(isCreating: true));

        $maintenanceRecord = new CreatePossessionMaintenanceRecord(
            user: $request->user(),
            possession: $record,
            type: PossessionMaintenanceTypeEnum::from($validated['maintenance_type']),
            title: $validated['title'],
            performedOn: CarbonImmutable::parse($validated['performed_on']),
            performedBy: $validated['performed_by'] ?? null,
            cost: isset($validated['cost']) ? (string) $validated['cost'] : null,
            currency: isset($validated['currency']) ? CurrencyEnum::from($validated['currency']) : null,
            conditionBefore: $this->condition($request, $validated['condition_before_id'] ?? null),
            conditionAfter: $this->condition($request, $validated['condition_after_id'] ?? null),
            nextDueOn: isset($validated['next_due_on']) ? CarbonImmutable::parse($validated['next_due_on']) : null,
            description: $validated['description'] ?? null,
        )->execute();

        return new PossessionMaintenanceRecordResource($maintenanceRecord)->response()->setStatusCode(201);
    }

    /**
     * Change a maintenance record. A field left out of the request keeps what the record says; a
     * field sent as null is cleared.
     */
    public function update(Request $request, string $vault, string $possession, string $maintenanceRecord): JsonResponse
    {
        $record = $this->maintenanceRecord($this->possession($this->vault($request, $vault), $possession), $maintenanceRecord);
        $validated = $request->validate($this->rules(isCreating: false));
        $kept = function (string $field) use ($validated, $record): ?string {
            $value = array_key_exists($field, $validated) ? $validated[$field] : $record->getRawOriginal($field);

            return $value === null ? null : (string) $value;
        };

        new UpdatePossessionMaintenanceRecord(
            user: $request->user(),
            record: $record,
            type: PossessionMaintenanceTypeEnum::from($validated['maintenance_type'] ?? $record->type->value),
            title: $kept('title') ?? $record->title,
            performedOn: CarbonImmutable::parse($kept('performed_on') ?? $record->performed_on),
            performedBy: $kept('performed_by'),
            cost: $kept('cost'),
            currency: CurrencyEnum::tryFrom((string) $kept('currency')),
            conditionBefore: array_key_exists('condition_before_id', $validated) ? $this->condition($request, $validated['condition_before_id']) : $record->conditionBefore,
            conditionAfter: array_key_exists('condition_after_id', $validated) ? $this->condition($request, $validated['condition_after_id']) : $record->conditionAfter,
            nextDueOn: $kept('next_due_on') === null ? null : CarbonImmutable::parse($kept('next_due_on')),
            description: $kept('description'),
        )->execute();

        return new PossessionMaintenanceRecordResource($record->refresh())->response();
    }

    public function destroy(Request $request, string $vault, string $possession, string $maintenanceRecord): Response
    {
        new DestroyPossessionMaintenanceRecord(
            user: $request->user(),
            record: $this->maintenanceRecord($this->possession($this->vault($request, $vault), $possession), $maintenanceRecord),
        )->execute();

        return response()->noContent(204);
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(bool $isCreating): array
    {
        $required = $isCreating ? 'required' : 'sometimes';

        return [
            'maintenance_type' => [$required, Rule::enum(PossessionMaintenanceTypeEnum::class)],
            'title' => [$required, 'string', 'max:255'],
            'performed_by' => ['nullable', 'string', 'max:255'],
            'performed_on' => [$required, 'date_format:Y-m-d'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', Rule::enum(CurrencyEnum::class)],
            'condition_before_id' => ['nullable', 'uuid'],
            'condition_after_id' => ['nullable', 'uuid'],
            'next_due_on' => ['nullable', 'date_format:Y-m-d'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    private function condition(Request $request, ?string $uuid): ?PossessionCondition
    {
        if ($uuid === null) {
            return null;
        }

        return $request->user()->account->possessionConditions()
            ->where('uuid', $uuid)
            ->firstOrFail();
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

    private function maintenanceRecord(Possession $possession, string $uuid): PossessionMaintenanceRecord
    {
        return PossessionMaintenanceRecord::query()
            ->where('possession_id', $possession->id)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
