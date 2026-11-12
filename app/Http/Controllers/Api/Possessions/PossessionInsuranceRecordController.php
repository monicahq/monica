<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Possessions;

use App\Actions\CreatePossessionInsuranceRecord;
use App\Actions\DestroyPossessionInsuranceRecord;
use App\Actions\UpdatePossessionInsuranceRecord;
use App\Enums\CurrencyEnum;
use App\Enums\PossessionInsuranceStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\PossessionInsuranceRecordResource;
use App\Models\Possession;
use App\Models\PossessionInsuranceRecord;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class PossessionInsuranceRecordController extends Controller
{
    public function index(Request $request, string $vault, string $possession): JsonResponse
    {
        $record = $this->possession($this->vault($request, $vault), $possession);
        $record->load('insuranceRecords');

        $insuranceRecords = $record->insuranceRecordsInOrder()
            ->each(fn (PossessionInsuranceRecord $insuranceRecord) => $insuranceRecord->setRelation('possession', $record));

        return response()->json([
            'data' => PossessionInsuranceRecordResource::collection($insuranceRecords)->resolve(),
        ]);
    }

    public function show(Request $request, string $vault, string $possession, string $insuranceRecord): JsonResponse
    {
        $record = $this->possession($this->vault($request, $vault), $possession);

        return new PossessionInsuranceRecordResource($this->insuranceRecord($record, $insuranceRecord))->response();
    }

    public function create(Request $request, string $vault, string $possession): JsonResponse
    {
        $record = $this->possession($this->vault($request, $vault), $possession);

        $validated = $request->validate($this->rules(isCreating: true));

        $insuranceRecord = new CreatePossessionInsuranceRecord(
            user: $request->user(),
            possession: $record,
            insurer: $validated['insurer'],
            status: PossessionInsuranceStatusEnum::from($validated['status']),
            insuredValue: (string) $validated['insured_value'],
            currency: CurrencyEnum::from($validated['currency']),
            policyNumber: $validated['policy_number'] ?? null,
            coverage: $validated['coverage'] ?? null,
            excess: isset($validated['excess']) ? (string) $validated['excess'] : null,
            startsOn: isset($validated['starts_on']) ? CarbonImmutable::parse($validated['starts_on']) : null,
            endsOn: isset($validated['ends_on']) ? CarbonImmutable::parse($validated['ends_on']) : null,
            isListedIndividually: (bool) ($validated['is_listed_individually'] ?? false),
            contactName: $validated['contact_name'] ?? null,
            contactEmail: $validated['contact_email'] ?? null,
            contactPhone: $validated['contact_phone'] ?? null,
            note: $validated['note'] ?? null,
        )->execute();

        return new PossessionInsuranceRecordResource($insuranceRecord)->response()->setStatusCode(201);
    }

    /**
     * Change an insurance record. A field left out of the request keeps what the record says; a
     * field sent as null is cleared.
     */
    public function update(Request $request, string $vault, string $possession, string $insuranceRecord): JsonResponse
    {
        $record = $this->insuranceRecord($this->possession($this->vault($request, $vault), $possession), $insuranceRecord);
        $validated = $request->validate($this->rules(isCreating: false));
        $kept = function (string $field) use ($validated, $record): ?string {
            $value = array_key_exists($field, $validated) ? $validated[$field] : $record->getRawOriginal($field);

            return $value === null ? null : (string) $value;
        };

        new UpdatePossessionInsuranceRecord(
            user: $request->user(),
            record: $record,
            insurer: $kept('insurer') ?? $record->insurer,
            status: PossessionInsuranceStatusEnum::from($kept('status') ?? $record->status->value),
            insuredValue: $kept('insured_value') ?? $record->insured_value,
            currency: CurrencyEnum::from($kept('currency') ?? $record->currency->value),
            policyNumber: $kept('policy_number'),
            coverage: $kept('coverage'),
            excess: $kept('excess'),
            startsOn: $kept('starts_on') === null ? null : CarbonImmutable::parse($kept('starts_on')),
            endsOn: $kept('ends_on') === null ? null : CarbonImmutable::parse($kept('ends_on')),
            isListedIndividually: (bool) $kept('is_listed_individually'),
            contactName: $kept('contact_name'),
            contactEmail: $kept('contact_email'),
            contactPhone: $kept('contact_phone'),
            note: $kept('note'),
        )->execute();

        return new PossessionInsuranceRecordResource($record->refresh())->response();
    }

    public function destroy(Request $request, string $vault, string $possession, string $insuranceRecord): Response
    {
        new DestroyPossessionInsuranceRecord(
            user: $request->user(),
            record: $this->insuranceRecord($this->possession($this->vault($request, $vault), $possession), $insuranceRecord),
        )->execute();

        return response()->noContent(204);
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(bool $isCreating): array
    {
        $required = $isCreating ? 'required' : 'sometimes';

        return [
            'insurer' => [$required, 'string', 'max:255'],
            'status' => [$required, Rule::enum(PossessionInsuranceStatusEnum::class)],
            'policy_number' => ['nullable', 'string', 'max:255'],
            'coverage' => ['nullable', 'string', 'max:255'],
            'insured_value' => [$required, 'numeric', 'min:0'],
            'excess' => ['nullable', 'numeric', 'min:0'],
            'currency' => [$required, Rule::enum(CurrencyEnum::class)],
            'starts_on' => ['nullable', 'date_format:Y-m-d'],
            'ends_on' => ['nullable', 'date_format:Y-m-d'],
            'is_listed_individually' => ['sometimes', 'boolean'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:5000'],
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

    private function insuranceRecord(Possession $possession, string $uuid): PossessionInsuranceRecord
    {
        return PossessionInsuranceRecord::query()
            ->where('possession_id', $possession->id)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
