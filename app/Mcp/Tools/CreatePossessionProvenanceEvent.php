<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CreatePossessionProvenanceEvent as CreatePossessionProvenanceEventAction;
use App\Enums\PossessionProvenanceDatePrecisionEnum;
use App\Enums\PossessionProvenanceEventTypeEnum;
use App\Http\Resources\PossessionProvenanceEventResource;
use App\Models\Possession;
use App\Models\PossessionTransaction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Record one moment in the provenance of a possession: where it was made, a change of owner or of custody, a loss or a recovery, or what authenticated, exhibited or published it. Only the type is required. The date is written in the shape its precision gives. An event holds no money: point it at the transaction that records what it cost. The change is written to the account journal.')]
class CreatePossessionProvenanceEvent extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.possessions.provenance.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'possession' => ['required', 'string'],
            'type' => ['required', Rule::enum(PossessionProvenanceEventTypeEnum::class)],
            'date_precision' => ['nullable', Rule::enum(PossessionProvenanceDatePrecisionEnum::class)],
            'occurred_on' => ['nullable', 'string', 'max:10'],
            'title' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'string', 'max:255'],
            'to' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url', 'max:255'],
            'transaction' => ['nullable', 'string'],
            'is_verified' => ['nullable', 'boolean'],
        ]);

        $possession = $this->possession($this->vault($request, $validated['vault']), $validated['possession']);

        $event = new CreatePossessionProvenanceEventAction(
            user: $this->user($request),
            possession: $possession,
            type: PossessionProvenanceEventTypeEnum::from($validated['type']),
            datePrecision: PossessionProvenanceDatePrecisionEnum::tryFrom($validated['date_precision'] ?? '') ?? PossessionProvenanceDatePrecisionEnum::Day,
            occurredOn: $validated['occurred_on'] ?? null,
            title: $validated['title'] ?? null,
            from: $validated['from'] ?? null,
            to: $validated['to'] ?? null,
            location: $validated['location'] ?? null,
            description: $validated['description'] ?? null,
            referenceNumber: $validated['reference_number'] ?? null,
            sourceUrl: $validated['source_url'] ?? null,
            transaction: $this->linkedTransaction($possession, $validated['transaction'] ?? null),
            isVerified: (bool) ($validated['is_verified'] ?? false),
        )->execute();

        return Response::json(new PossessionProvenanceEventResource($event)->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the possession.')->required(),
            'possession' => $schema->string()->description('The identifier of the possession, as list-possessions gives it.')->required(),
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
            'transaction' => $schema->string()->description('The identifier of a transaction of the same possession recording what the moment cost, as list-possession-transactions gives it.'),
            'is_verified' => $schema->boolean()->description('Whether a document, a record or an expert confirms it. Not verified when left out.'),
        ];
    }

    private function linkedTransaction(Possession $possession, ?string $address): ?PossessionTransaction
    {
        if ($address === null) {
            return null;
        }

        return $possession->transactions()->where('uuid', $address)->first()
            ?? throw $this->doesNotExist(__('That transaction does not exist.'));
    }
}
