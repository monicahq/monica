<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\CreateLifeEvent as CreateLifeEventAction;
use App\Enums\LifeEventSourceEnum;
use App\Exceptions\FieldIsRequired;
use App\Exceptions\FieldValueIsNotUnique;
use App\Exceptions\LifeEventTypeIsNotAvailable;
use App\Exceptions\NotEnoughRepetitions;
use App\Exceptions\NotEnoughStorageLeft;
use App\Exceptions\TooManyRepetitions;
use App\Helpers\FieldValueRules;
use App\Http\Resources\LifeEventResource;
use App\Models\Contact;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Write down something that happened, on a date, with the people who were there. It goes on the timeline of every one of them, the first being the person it is recorded for. Read the custom fields of the type with get-life-event-type-fields first: values are keyed by field id. A file field cannot be written. The change is written to the account journal.')]
class CreateLifeEvent extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.lifeEvents.create';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'life_event_type' => ['required', 'string'],
            'happened_on' => ['required', 'date_format:Y-m-d'],
            'participants' => ['required', 'array', 'min:1'],
            'participants.*' => ['string', 'distinct'],
            'title' => ['nullable', 'string', 'max:255'],
            'narrative' => ['nullable', 'string', 'max:65535'],
            'location' => ['nullable', 'string', 'max:255'],
            'duration_in_minutes' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'fields' => ['nullable', 'array'],
        ]);

        $vault = $this->vault($request, $validated['vault']);
        $lifeEventType = $this->lifeEventType($request, $validated['life_event_type']);
        $people = array_map(fn (string $address): Contact => $this->contact($vault, $address), array_values($validated['participants']));
        $fields = $lifeEventType->structure?->publishedVersion?->inputFields() ?? new Collection;
        $values = $request->validate(FieldValueRules::withoutFiles($fields, $vault))['fields'] ?? [];

        try {
            $lifeEvent = new CreateLifeEventAction(
                user: $this->user($request),
                vault: $vault,
                contact: $people[0],
                lifeEventType: $lifeEventType,
                happenedOn: CarbonImmutable::parse($validated['happened_on']),
                title: $validated['title'] ?? null,
                narrative: $validated['narrative'] ?? null,
                location: $validated['location'] ?? null,
                durationInMinutes: isset($validated['duration_in_minutes']) ? (int) $validated['duration_in_minutes'] : null,
                participants: array_slice($people, 1),
                values: $values,
                createdFrom: LifeEventSourceEnum::Mcp,
            )->execute();
        } catch (LifeEventTypeIsNotAvailable|FieldIsRequired|FieldValueIsNotUnique|NotEnoughRepetitions|NotEnoughStorageLeft|TooManyRepetitions $e) {
            return Response::error($e->getMessage());
        }

        $resource = new LifeEventResource($this->lifeEvent($vault, $lifeEvent->uuid));
        $resource->withSensitive = $this->reachesSensitiveFields($request);

        return Response::json($resource->resolve());
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'vault' => $schema->string()->description('The address of the vault, as list-vaults gives it.')->required(),
            'life_event_type' => $schema->string()->description('The id of its type, as list-life-event-types gives it. A type switched off cannot be chosen.')->required(),
            'happened_on' => $schema->string()->description('The day it happened, as YYYY-MM-DD.')->required(),
            'participants' => $schema->array()->items($schema->string())->description('The addresses of the contacts who were there, in order, as list-contacts gives them. The first is the person it is recorded for.')->required(),
            'title' => $schema->string()->description('A title. Without one it is called by the name of its type.'),
            'narrative' => $schema->string()->description('What happened.'),
            'location' => $schema->string()->description('Where it happened.'),
            'duration_in_minutes' => $schema->integer()->description('How long it lasted, in minutes.'),
            'fields' => $schema->object()->description('The values of the custom fields, keyed by the field id get-life-event-type-fields gives. A select field takes the id of its choice.'),
        ];
    }
}
