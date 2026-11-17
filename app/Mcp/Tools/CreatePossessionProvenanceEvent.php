<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Enums\PossessionProvenanceDatePrecisionEnum;
use App\Enums\PossessionProvenanceEventTypeEnum;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Record one moment in the provenance of a possession: where it was made, a change of owner or of custody, a loss or a recovery, or what authenticated, exhibited or published it. Only the type is required. The date is written in the shape its precision gives. An event holds no money: point it at the transaction that records what it cost. The change is written to the account log.')]
class CreatePossessionProvenanceEvent extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.provenance.create';

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
            'type' => $schema->string()->enum(array_column(PossessionProvenanceEventTypeEnum::cases(), 'value'))->description('What happened.')->required(),
            'date_precision' => $schema->string()->enum(array_column(PossessionProvenanceDatePrecisionEnum::cases(), 'value'))->description('How much of the date is known. A day when left out.'),
            'occurred_on' => $schema->string()->description('When it happened, in the shape its precision gives: YYYY-MM-DD for a day, YYYY-MM for a month, YYYY for a year or around a year, nothing when unknown.'),
            'title' => $schema->string()->description('What happened, in a few words. The name of the type is shown when left out.'),
            'from' => $schema->string()->description('Who it came from: a person, a shop, an estate.'),
            'to' => $schema->string()->description('Who held it afterwards.'),
            'location' => $schema->string()->description('Where it happened.'),
            'description' => $schema->string()->description('What happened, and how it is known.'),
            'reference_number' => $schema->string()->description('The reference of the record: a lot, a deed, a certificate.'),
            'source_url' => $schema->string()->description('A full web address where the event can be checked.'),
            'transaction_id' => $schema->string()->description('The id of a transaction of the same possession recording what the moment cost, as list-possession-transactions gives it.'),
            'is_verified' => $schema->boolean()->description('Whether a document, a record or an expert confirms the event. Not verified when left out.'),
        ];
    }
}
