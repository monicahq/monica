<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdatePossessionTransaction as UpdatePossessionTransactionAction;
use App\Enums\CurrencyEnum;
use App\Enums\PossessionTransactionTypeEnum;
use App\Http\Resources\PossessionTransactionResource;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Change a transaction of a possession. What you leave out keeps its value, and what you send as null is cleared. Every figure is zero or more and needs the currency it is in. The change is written to the account journal.')]
class UpdatePossessionTransaction extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.transactions.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'possession' => ['required', 'string'],
            'transaction' => ['required', 'string'],
            'type' => ['sometimes', Rule::enum(PossessionTransactionTypeEnum::class)],
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
        ]);

        $possession = $this->possession($this->vault($request, $validated['vault']), $validated['possession']);
        $transaction = $this->possessionTransaction($possession, $validated['transaction']);

        $kept = function (string $field) use ($validated, $transaction): ?string {
            $value = array_key_exists($field, $validated) ? $validated[$field] : $transaction->getRawOriginal($field);

            return $value === null ? null : (string) $value;
        };

        new UpdatePossessionTransactionAction(
            user: $this->user($request),
            transaction: $transaction,
            type: PossessionTransactionTypeEnum::from($kept('type') ?? $transaction->type->value),
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

        return Response::json(new PossessionTransactionResource($transaction->refresh())->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the possession.')->required(),
            'possession' => $schema->string()->description('The identifier of the possession, as list-possessions gives it.')->required(),
            'transaction' => $schema->string()->description('The identifier of the transaction, as list-possession-transactions gives it.')->required(),
            'type' => $schema->string()->enum(array_column(PossessionTransactionTypeEnum::cases(), 'value'))->description('What happened.'),
            'occurred_on' => $schema->string()->description('The day it happened, as YYYY-MM-DD, or null when it is not known.'),
            'counterparty' => $schema->string()->description('Who was on the other side, as text.'),
            'amount' => $schema->number()->description('The price or the amount, 0 or more.'),
            'tax' => $schema->number()->description('The tax paid, 0 or more.'),
            'fees' => $schema->number()->description('The fees paid, 0 or more.'),
            'shipping' => $schema->number()->description('The shipping paid, 0 or more.'),
            'entered_total' => $schema->number()->description('The total, when it is not the sum of the four figures.'),
            'currency' => $schema->string()->enum(array_column(CurrencyEnum::cases(), 'value'))->description('The currency of every figure. Required with any figure.'),
            'reference' => $schema->string()->description('An invoice, receipt or deed number.'),
            'source_url' => $schema->string()->description('A full web address where the transaction can be checked.'),
            'note' => $schema->string()->description('Anything worth remembering about it.'),
        ];
    }
}
