<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Possessions;

use App\Actions\CreatePossessionLoan;
use App\Actions\DestroyPossessionLoan;
use App\Actions\UpdatePossessionLoan;
use App\Enums\CurrencyEnum;
use App\Enums\PossessionLoanDirectionEnum;
use App\Enums\PossessionLoanStatusEnum;
use App\Exceptions\VaultHasNoContactDefault;
use App\Http\Controllers\Controller;
use App\Http\Resources\PossessionLoanResource;
use App\Models\Contact;
use App\Models\Possession;
use App\Models\PossessionCondition;
use App\Models\PossessionLoan;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PossessionLoanController extends Controller
{
    public function index(Request $request, string $vault, string $possession): JsonResponse
    {
        $record = $this->possession($this->vault($request, $vault), $possession);
        $record->load('loans.contact', 'loans.conditionOut', 'loans.conditionBack');

        $loans = $record->loans
            ->reverse()
            ->values()
            ->each(fn (PossessionLoan $loan) => $loan->setRelation('possession', $record));

        return response()->json([
            'data' => PossessionLoanResource::collection($loans)->resolve(),
        ]);
    }

    public function show(Request $request, string $vault, string $possession, string $loan): JsonResponse
    {
        $record = $this->possession($this->vault($request, $vault), $possession);

        return new PossessionLoanResource($this->loan($record, $loan))->response();
    }

    public function create(Request $request, string $vault, string $possession): JsonResponse
    {
        $vaultRecord = $this->vault($request, $vault);
        $record = $this->possession($vaultRecord, $possession);
        $validated = $request->validate([
            'direction' => ['required', Rule::enum(PossessionLoanDirectionEnum::class)],
            'status' => ['required', Rule::enum(PossessionLoanStatusEnum::class)],
            'contact_id' => ['nullable', 'uuid', 'required_without:new_contact'],
            'new_contact' => ['nullable', 'array', 'required_without:contact_id', 'prohibits:contact_id'],
            'new_contact.first_name' => ['required_with:new_contact', 'string', 'max:255'],
            'new_contact.last_name' => ['nullable', 'string', 'max:255'],
            ...$this->rules($request),
        ]);

        try {
            $loan = new CreatePossessionLoan(
                user: $request->user(),
                possession: $record,
                direction: PossessionLoanDirectionEnum::from($validated['direction']),
                status: PossessionLoanStatusEnum::from($validated['status']),
                startedOn: CarbonImmutable::parse($validated['started_on']),
                contact: isset($validated['contact_id']) ? $this->contact($vaultRecord, $validated['contact_id']) : null,
                newContactFirstName: $validated['new_contact']['first_name'] ?? null,
                newContactLastName: $validated['new_contact']['last_name'] ?? null,
                dueOn: isset($validated['due_on']) ? CarbonImmutable::parse($validated['due_on']) : null,
                returnedOn: isset($validated['returned_on']) ? CarbonImmutable::parse($validated['returned_on']) : null,
                conditionOut: $this->condition($request, $validated['condition_out_id'] ?? null),
                conditionBack: $this->condition($request, $validated['condition_back_id'] ?? null),
                depositAmount: isset($validated['deposit_amount']) ? (string) $validated['deposit_amount'] : null,
                depositCurrency: CurrencyEnum::tryFrom((string) ($validated['deposit_currency'] ?? '')),
                purpose: $validated['purpose'] ?? null,
                isInProvenance: (bool) ($validated['is_in_provenance'] ?? false),
            )->execute();
        } catch (VaultHasNoContactDefault) {
            throw ValidationException::withMessages(['new_contact' => __('This vault has no template for new contacts to start from. Choose one in the vault settings.')]);
        }

        return new PossessionLoanResource($loan)->response()->setStatusCode(201);
    }

    public function update(Request $request, string $vault, string $possession, string $loan): JsonResponse
    {
        $vaultRecord = $this->vault($request, $vault);
        $record = $this->loan($this->possession($vaultRecord, $possession), $loan);
        $validated = $request->validate([
            'direction' => ['sometimes', Rule::enum(PossessionLoanDirectionEnum::class)],
            'status' => ['sometimes', Rule::enum(PossessionLoanStatusEnum::class)],
            'contact_id' => ['sometimes', 'uuid'],
            ...$this->rules($request, isUpdate: true),
        ]);
        $date = fn (string $field): ?CarbonImmutable => array_key_exists($field, $validated)
            ? ($validated[$field] === null ? null : CarbonImmutable::parse($validated[$field]))
            : $record->{$field};
        $kept = fn (string $field): mixed => array_key_exists($field, $validated) ? $validated[$field] : $record->{$field};
        $condition = fn (string $field, ?PossessionCondition $current): ?PossessionCondition => array_key_exists($field, $validated)
            ? $this->condition($request, $validated[$field])
            : $current;

        new UpdatePossessionLoan(
            user: $request->user(),
            loan: $record,
            direction: isset($validated['direction']) ? PossessionLoanDirectionEnum::from($validated['direction']) : $record->direction,
            status: isset($validated['status']) ? PossessionLoanStatusEnum::from($validated['status']) : $record->status,
            startedOn: $date('started_on') ?? $record->started_on,
            contact: isset($validated['contact_id']) ? $this->contact($vaultRecord, $validated['contact_id']) : null,
            dueOn: $date('due_on'),
            returnedOn: $date('returned_on'),
            conditionOut: $condition('condition_out_id', $record->conditionOut),
            conditionBack: $condition('condition_back_id', $record->conditionBack),
            depositAmount: $kept('deposit_amount') === null ? null : (string) $kept('deposit_amount'),
            depositCurrency: array_key_exists('deposit_currency', $validated)
                ? CurrencyEnum::tryFrom((string) $validated['deposit_currency'])
                : $record->deposit_currency,
            purpose: $kept('purpose'),
            isInProvenance: (bool) $kept('is_in_provenance'),
        )->execute();

        return new PossessionLoanResource($record->refresh())->response();
    }

    public function destroy(Request $request, string $vault, string $possession, string $loan): Response
    {
        $record = $this->loan($this->possession($this->vault($request, $vault), $possession), $loan);

        new DestroyPossessionLoan(
            user: $request->user(),
            loan: $record,
        )->execute();

        return response()->noContent(204);
    }

    /** @return array<string, list<mixed>> */
    private function rules(Request $request, bool $isUpdate = false): array
    {
        return [
            'started_on' => [$isUpdate ? 'sometimes' : 'required', 'date_format:Y-m-d'],
            'due_on' => ['nullable', 'date_format:Y-m-d'],
            'returned_on' => ['nullable', 'date_format:Y-m-d'],
            'condition_out_id' => ['nullable', 'uuid', Rule::exists('possession_conditions', 'uuid')->where('account_id', $request->user()->account_id)],
            'condition_back_id' => ['nullable', 'uuid', Rule::exists('possession_conditions', 'uuid')->where('account_id', $request->user()->account_id)],
            'deposit_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'deposit_currency' => ['nullable', Rule::enum(CurrencyEnum::class)],
            'purpose' => ['nullable', 'string', 'max:5000'],
            'is_in_provenance' => ['sometimes', 'boolean'],
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

    private function loan(Possession $possession, string $uuid): PossessionLoan
    {
        return PossessionLoan::query()
            ->where('possession_id', $possession->id)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    private function contact(Vault $vault, string $uuid): Contact
    {
        return Contact::query()
            ->where('vault_id', $vault->id)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    private function condition(Request $request, ?string $uuid): ?PossessionCondition
    {
        if ($uuid === null) {
            return null;
        }

        return PossessionCondition::query()
            ->where('account_id', $request->user()->account_id)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
