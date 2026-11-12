<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CreatePossessionValuation as CreatePossessionValuationAction;
use App\Enums\CurrencyEnum;
use App\Enums\PossessionValuationConfidenceEnum;
use App\Enums\PossessionValuationTypeEnum;
use App\Http\Resources\PossessionValuationResource;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Record what a possession is worth on a date: the type, the date and the amount in its currency are required. A valuation is never changed afterwards; to correct one, record a new valuation. The latest by date becomes the current value of the possession. The change is written to the account journal.')]
class CreatePossessionValuation extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.valuations.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'possession' => ['required', 'string'],
            'type' => ['required', Rule::enum(PossessionValuationTypeEnum::class)],
            'valued_on' => ['required', 'date_format:Y-m-d'],
            'confidence' => ['nullable', Rule::enum(PossessionValuationConfidenceEnum::class)],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', Rule::enum(CurrencyEnum::class)],
            'valuer' => ['nullable', 'string', 'max:255'],
            'method' => ['nullable', 'string', 'max:255'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url', 'max:255'],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        $possession = $this->possession($this->vault($request, $validated['vault']), $validated['possession']);

        $valuation = new CreatePossessionValuationAction(
            user: $this->user($request),
            possession: $possession,
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

        return Response::json(new PossessionValuationResource($valuation)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the possession.')->required(),
            'possession' => $schema->string()->description('The identifier of the possession, as list-possessions gives it.')->required(),
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
