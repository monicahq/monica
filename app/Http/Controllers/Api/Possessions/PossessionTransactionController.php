<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Possessions;

use App\Actions\CreatePossessionTransaction;
use App\Actions\DestroyPossessionTransaction;
use App\Actions\UpdatePossessionTransaction;
use App\Enums\CurrencyEnum;
use App\Enums\PossessionTransactionTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\PossessionTransactionResource;
use App\Models\Possession;
use App\Models\PossessionTransaction;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class PossessionTransactionController extends Controller
{
    public function index(Request $request, string $vault, string $possession): JsonResponse
    {
        $record = $this->possession($this->vault($request, $vault), $possession);
        $record->load('transactions.documents');

        $transactions = $record->transactionsOldestFirst()
            ->each(fn (PossessionTransaction $transaction) => $transaction->setRelation('possession', $record));

        return response()->json([
            'data' => PossessionTransactionResource::collection($transactions)->resolve(),
        ]);
    }

    public function show(Request $request, string $vault, string $possession, string $transaction): JsonResponse
    {
        $record = $this->possession($this->vault($request, $vault), $possession);

        return new PossessionTransactionResource($this->transaction($record, $transaction))->response();
    }

    public function create(Request $request, string $vault, string $possession): JsonResponse
    {
        $record = $this->possession($this->vault($request, $vault), $possession);

        $validated = $request->validate($this->rules(typeRequired: true));

        $transaction = new CreatePossessionTransaction(
            user: $request->user(),
            possession: $record,
            type: PossessionTransactionTypeEnum::from($validated['type']),
            occurredOn: isset($validated['occurred_on']) ? CarbonImmutable::parse($validated['occurred_on']) : null,
            counterparty: $validated['counterparty'] ?? null,
            amount: isset($validated['amount']) ? (string) $validated['amount'] : null,
            tax: isset($validated['tax']) ? (string) $validated['tax'] : null,
            fees: isset($validated['fees']) ? (string) $validated['fees'] : null,
            shipping: isset($validated['shipping']) ? (string) $validated['shipping'] : null,
            enteredTotal: isset($validated['entered_total']) ? (string) $validated['entered_total'] : null,
            currency: CurrencyEnum::tryFrom($validated['currency'] ?? ''),
            reference: $validated['reference'] ?? null,
            sourceUrl: $validated['source_url'] ?? null,
            note: $validated['note'] ?? null,
        )->execute();

        return new PossessionTransactionResource($transaction)->response()->setStatusCode(201);
    }

    /**
     * Change a transaction. A field left out of the request keeps what the transaction says; a
     * field sent as null is cleared.
     */
    public function update(Request $request, string $vault, string $possession, string $transaction): JsonResponse
    {
        $record = $this->transaction($this->possession($this->vault($request, $vault), $possession), $transaction);
        $validated = $request->validate($this->rules(typeRequired: false));
        $kept = function (string $field) use ($validated, $record): ?string {
            $value = array_key_exists($field, $validated) ? $validated[$field] : $record->getRawOriginal($field);

            return $value === null ? null : (string) $value;
        };

        new UpdatePossessionTransaction(
            user: $request->user(),
            transaction: $record,
            type: PossessionTransactionTypeEnum::from($kept('type') ?? $record->type->value),
            occurredOn: $kept('occurred_on') === null ? null : CarbonImmutable::parse($kept('occurred_on')),
            counterparty: $kept('counterparty'),
            amount: $kept('amount'),
            tax: $kept('tax'),
            fees: $kept('fees'),
            shipping: $kept('shipping'),
            enteredTotal: $kept('entered_total'),
            currency: CurrencyEnum::tryFrom($kept('currency') ?? ''),
            reference: $kept('reference'),
            sourceUrl: $kept('source_url'),
            note: $kept('note'),
        )->execute();

        return new PossessionTransactionResource($record->refresh())->response();
    }

    public function destroy(Request $request, string $vault, string $possession, string $transaction): Response
    {
        new DestroyPossessionTransaction(
            user: $request->user(),
            transaction: $this->transaction($this->possession($this->vault($request, $vault), $possession), $transaction),
        )->execute();

        return response()->noContent(204);
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(bool $typeRequired): array
    {
        return [
            'type' => [$typeRequired ? 'required' : 'sometimes', Rule::enum(PossessionTransactionTypeEnum::class)],
            'occurred_on' => ['nullable', 'date_format:Y-m-d'],
            'counterparty' => ['nullable', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'fees' => ['nullable', 'numeric', 'min:0'],
            'shipping' => ['nullable', 'numeric', 'min:0'],
            'entered_total' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', Rule::enum(CurrencyEnum::class)],
            'reference' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url', 'max:2048'],
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

    private function transaction(Possession $possession, string $uuid): PossessionTransaction
    {
        return PossessionTransaction::query()
            ->where('possession_id', $possession->id)
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
