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
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Description('Change a provenance event of a possession. The date is written in the shape its precision gives. The change is written to the account log.')]
#[IsReadOnly(false)]
#[IsDestructive]
#[IsOpenWorld(false)]
class UpdatePossessionProvenanceEvent extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.provenance.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'possession' => ['required', 'string'],
            'event' => ['required', 'string'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $possession = $this->possession($vault, $validated['possession']);
        $record = $this->possessionProvenanceEvent($possession, $validated['event']);

        return $this->answerThroughApi($request, $vault->uuid, $possession->uuid, $record->uuid);
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The id of the vault, as list-vaults gives it.')->required(),
            'possession' => $schema->string()->description('The id of the possession, as list-possessions gives it.')->required(),
            'event' => $schema->string()->description('The id of the event, as list-possession-provenance-events gives it.')->required(),
            'type' => $schema->string()->enum(array_column(PossessionProvenanceEventTypeEnum::cases(), 'value'))->description('What happened.'),
            'date_precision' => $schema->string()->enum(array_column(PossessionProvenanceDatePrecisionEnum::cases(), 'value'))->description('How much of the date is known.'),
            'occurred_on' => $schema->string()->description('When it happened, in the shape its precision gives: YYYY-MM-DD for a day, YYYY-MM for a month, YYYY for a year or around a year, null when unknown.'),
            'title' => $schema->string()->description('What happened, in a few words.'),
            'from' => $schema->string()->description('Who it came from: a person, a shop, an estate.'),
            'to' => $schema->string()->description('Who held it afterwards.'),
            'location' => $schema->string()->description('Where it happened.'),
            'description' => $schema->string()->description('What happened, and how it is known.'),
            'reference_number' => $schema->string()->description('The reference of the record: a lot, a deed, a certificate.'),
            'source_url' => $schema->string()->description('A full web address where the event can be checked.'),
            'transaction_id' => $schema->string()->description('The id of a transaction of the same possession recording what the moment cost, as list-possession-transactions gives it.'),
            'is_verified' => $schema->boolean()->description('Whether a document, a record or an expert confirms the event.'),
        ];
    }
}
