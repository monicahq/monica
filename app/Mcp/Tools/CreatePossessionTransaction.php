<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\CurrencyEnum;
use App\Enums\PossessionTransactionTypeEnum;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Record money or ownership changing hands for a possession: a purchase, a sale, an exchange, a gift received or given, an inheritance, a refund, a fee, a tax, shipping or anything else. Every figure is optional and zero or more, and needs the currency it is in. The earliest purchase, exchange, gift received or inheritance gives the possession its acquisition date and price. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class CreatePossessionTransaction extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.transactions.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'possession' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $possession = $this->possession($vault, $validated['possession']);

        return $this->answerThroughApi($request, $vault->uuid, $possession->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'possession' => $schema->string()->description('The id of the possession, as list-possessions gives it.')->required(),
            'type' => $schema->string()->enum(array_column(PossessionTransactionTypeEnum::cases(), 'value'))->description('What happened.')->required(),
            'occurred_on' => $schema->string()->description('The day it happened, as YYYY-MM-DD. Left out when not known.'),
            'counterparty' => $schema->string()->description('Who was on the other side, as text: a shop, a person, an estate.'),
            'amount' => $schema->number()->description('The price or the amount, 0 or more, with two decimals.'),
            'tax' => $schema->number()->description('The tax paid, 0 or more.'),
            'fees' => $schema->number()->description('The fees paid, 0 or more.'),
            'shipping' => $schema->number()->description('The shipping paid, 0 or more.'),
            'entered_total' => $schema->number()->description('The total, when it is not the sum of the amount, the tax, the fees and the shipping.'),
            'currency' => $schema->string()->enum(array_column(CurrencyEnum::cases(), 'value'))->description('The currency of every figure. Required with any figure.'),
            'reference' => $schema->string()->description('An invoice, receipt or deed number.'),
            'source_url' => $schema->string()->description('A full web address where the transaction can be checked.'),
            'note' => $schema->string()->description('Anything worth remembering about the transaction.'),
        ];
    }
}
