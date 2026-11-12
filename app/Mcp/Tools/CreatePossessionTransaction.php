<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CreatePossessionTransaction as CreatePossessionTransactionAction;
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

#[Description('Record money or ownership changing hands for a possession: a purchase, a sale, an exchange, a gift received or given, an inheritance, a refund, a fee, a tax, shipping or anything else. Every figure is optional and zero or more, and needs the currency it is in. The earliest purchase, exchange, gift received or inheritance gives the possession its acquisition date and price. The change is written to the account journal.')]
class CreatePossessionTransaction extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.transactions.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'possession' => ['required', 'string'],
            'type' => ['required', Rule::enum(PossessionTransactionTypeEnum::class)],
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

        $transaction = new CreatePossessionTransactionAction(
            user: $this->user($request),
            possession: $possession,
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

        return Response::json(new PossessionTransactionResource($transaction)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the possession.')->required(),
            'possession' => $schema->string()->description('The identifier of the possession, as list-possessions gives it.')->required(),
            'type' => $schema->string()->enum(array_column(PossessionTransactionTypeEnum::cases(), 'value'))->description('What happened.')->required(),
            'occurred_on' => $schema->string()->description('The day it happened, as YYYY-MM-DD. Left out when not known.'),
            'counterparty' => $schema->string()->description('Who was on the other side, as text: a shop, a person, an estate.'),
            'amount' => $schema->number()->description('The price or the amount, 0 or more, with two decimals.'),
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
