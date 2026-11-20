<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\CurrencyEnum;
use App\Enums\PossessionValuationConfidenceEnum;
use App\Enums\PossessionValuationTypeEnum;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Record what a possession is worth on a date: the type, the date and the amount in its currency are required. A valuation is never changed afterwards. To correct one, record a new valuation. The latest by date becomes the current value of the possession. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive(false)]
#[IsOpenWorld(false)]
class CreatePossessionValuation extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.valuations.create';

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
            'type' => $schema->string()->enum(array_column(PossessionValuationTypeEnum::cases(), 'value'))->description('Where the figure comes from.')->required(),
            'valued_on' => $schema->string()->description('The day the estimate applies to, as YYYY-MM-DD.')->required(),
            'confidence' => $schema->string()->enum(array_column(PossessionValuationConfidenceEnum::cases(), 'value'))->description('How sure somebody is of the figure. Unknown when left out.'),
            'amount' => $schema->number()->description('What the possession is worth, 0 or more.')->required(),
            'currency' => $schema->string()->enum(array_column(CurrencyEnum::cases(), 'value'))->description('The currency of the amount.')->required(),
            'valuer' => $schema->string()->description('Who estimated it: a jeweller, an auction house, the owner.'),
            'method' => $schema->string()->description('How it was estimated, such as comparable sales.'),
            'reference_number' => $schema->string()->description('The reference of the appraisal, the listing or the offer.'),
            'source_url' => $schema->string()->description('A full web address where the valuation can be checked.'),
            'note' => $schema->string()->description('Anything worth remembering about the valuation.'),
        ];
    }
}
