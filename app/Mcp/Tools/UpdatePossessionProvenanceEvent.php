<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdatePossessionProvenanceEvent as UpdatePossessionProvenanceEventAction;
use App\Enums\PossessionProvenanceDatePrecisionEnum;
use App\Enums\PossessionProvenanceEventTypeEnum;
use App\Http\Resources\PossessionProvenanceEventResource;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Change a provenance event of a possession. What you leave out keeps its value, and what you send as null is cleared. The date is written in the shape its precision gives. The change is written to the account journal.')]
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
            'type' => ['sometimes', Rule::enum(PossessionProvenanceEventTypeEnum::class)],
            'date_precision' => ['sometimes', Rule::enum(PossessionProvenanceDatePrecisionEnum::class)],
            'occurred_on' => ['nullable', 'string', 'max:10'],
            'title' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'string', 'max:255'],
            'to' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url', 'max:255'],
            'transaction' => ['nullable', 'string'],
            'is_verified' => ['sometimes', 'boolean'],
        ]);

        $possession = $this->possession($this->vault($request, $validated['vault']), $validated['possession']);
        $event = $this->possessionProvenanceEvent($possession, $validated['event']);
        $kept = fn (string $field): ?string => array_key_exists($field, $validated)
            ? ($validated[$field] === null ? null : (string) $validated[$field])
            : $event->getRawOriginal($field);
        $datePrecision = PossessionProvenanceDatePrecisionEnum::tryFrom($validated['date_precision'] ?? '') ?? $event->date_precision;
        $transaction = $event->transaction;

        if (array_key_exists('transaction', $validated)) {
            $transaction = $validated['transaction'] === null
                ? null
                : ($possession->transactions()->where('uuid', $validated['transaction'])->first()
                    ?? throw $this->doesNotExist(__('That transaction does not exist.')));
        }

        new UpdatePossessionProvenanceEventAction(
            user: $this->user($request),
            event: $event,
            type: PossessionProvenanceEventTypeEnum::tryFrom($validated['type'] ?? '') ?? $event->type,
            datePrecision: $datePrecision,
            occurredOn: array_key_exists('occurred_on', $validated) ? $validated['occurred_on'] : $datePrecision->format($event->occurred_on),
            title: $kept('title'),
            from: $kept('from'),
            to: $kept('to'),
            location: $kept('location'),
            description: $kept('description'),
            referenceNumber: $kept('reference_number'),
            sourceUrl: $kept('source_url'),
            transaction: $transaction,
            isVerified: array_key_exists('is_verified', $validated) ? (bool) $validated['is_verified'] : $event->is_verified,
        )->execute();

        return Response::json(new PossessionProvenanceEventResource($event->refresh())->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault holding the possession.')->required(),
            'possession' => $schema->string()->description('The identifier of the possession, as list-possessions gives it.')->required(),
            'event' => $schema->string()->description('The identifier of the event, as list-possession-provenance-events gives it.')->required(),
            'type' => $schema->string()->enum(array_column(PossessionProvenanceEventTypeEnum::cases(), 'value'))->description('What happened.'),
            'date_precision' => $schema->string()->enum(array_column(PossessionProvenanceDatePrecisionEnum::cases(), 'value'))->description('How much of the date is known.'),
            'occurred_on' => $schema->string()->description('When it happened, in the shape its precision gives: YYYY-MM-DD for a day, YYYY-MM for a month, YYYY for a year or around a year, null when unknown.'),
            'title' => $schema->string()->description('What happened, in a few words.'),
            'from' => $schema->string()->description('Who it came from.'),
            'to' => $schema->string()->description('Who held it afterwards.'),
            'location' => $schema->string()->description('Where it happened.'),
            'description' => $schema->string()->description('What happened, and how it is known.'),
            'reference_number' => $schema->string()->description('The reference of the record.'),
            'source_url' => $schema->string()->description('A full web address where the event can be checked.'),
            'transaction' => $schema->string()->description('The identifier of a transaction of the same possession, or null to unlink it.'),
            'is_verified' => $schema->boolean()->description('Whether a document, a record or an expert confirms it.'),
        ];
    }
}
