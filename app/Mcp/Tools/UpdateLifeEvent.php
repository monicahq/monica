<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Actions\UpdateLifeEvent as UpdateLifeEventAction;
use App\Exceptions\FieldIsRequired;
use App\Exceptions\FieldValueIsNotUnique;
use App\Exceptions\LifeEventTypeIsNotAvailable;
use App\Exceptions\NotEnoughRepetitions;
use App\Exceptions\NotEnoughStorageLeft;
use App\Exceptions\TooManyRepetitions;
use App\Helpers\FieldValueRules;
use App\Http\Resources\LifeEventResource;
use App\Models\Contact;
use App\Models\Field;
use App\Models\LifeEventParticipant;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Change a life event. Whatever is left out keeps its value; fields left out keep every custom value, and fields given replace the whole set, except sensitive fields, which keep their value unless given. The first participant has to stay the person it is recorded for. The change is written to the account journal.')]
class UpdateLifeEvent extends Tool
{
    use MirrorsAnApiCapability;

    protected string $mirrors = 'api.v1.lifeEvents.update';

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'vault' => ['required', 'string'],
            'life_event' => ['required', 'string'],
            'life_event_type' => ['nullable', 'string'],
            'happened_on' => ['nullable', 'date_format:Y-m-d'],
            'participants' => ['nullable', 'array', 'min:1'],
            'participants.*' => ['string', 'distinct'],
            'title' => ['nullable', 'string', 'max:255'],
            'narrative' => ['nullable', 'string', 'max:65535'],
            'location' => ['nullable', 'string', 'max:255'],
            'duration_in_minutes' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'fields' => ['nullable', 'array'],
        ]);

        $given = $request->all();
        $vault = $this->vault($request, $validated['vault']);
        $lifeEvent = $this->lifeEvent($vault, $validated['life_event']);

        $current = $lifeEvent->participants
            ->map(fn (LifeEventParticipant $participation): mixed => $participation->participant)
            ->filter(fn (mixed $person): bool => $person instanceof Contact)
            ->values();

        if ($current->isEmpty()) {
            return Response::error(__('Nobody who took part in this life event is left, so it cannot be changed here.'));
        }

        $people = isset($validated['participants'])
            ? array_map(fn (string $address): Contact => $this->contact($vault, $address), array_values($validated['participants']))
            : $current->all();

        if ($people[0]->id !== $current->first()->id) {
            return Response::error(__('The first participant has to stay the person the life event is recorded for.'));
        }

        $lifeEventType = isset($validated['life_event_type']) ? $this->lifeEventType($request, $validated['life_event_type']) : $lifeEvent->lifeEventType;

        $version = $lifeEventType->id === $lifeEvent->life_event_type_id
            ? $lifeEvent->structureVersion
            : $lifeEventType->structure?->publishedVersion;

        $values = isset($given['fields'])
            ? $request->validate(FieldValueRules::withoutFiles($version?->inputFields() ?? new Collection, $vault))['fields'] ?? []
            : null;

        $hiddenFieldIds = $this->reachesSensitiveFields($request) ? [] : ($version?->inputFields() ?? new Collection)
            ->filter(fn (Field $field): bool => $field->is_sensitive && ! array_key_exists($field->id, $values ?? []))
            ->pluck('id')
            ->all();

        $durationInMinutes = isset($validated['duration_in_minutes']) ? (int) $validated['duration_in_minutes'] : null;

        try {
            new UpdateLifeEventAction(
                user: $this->user($request),
                vault: $vault,
                contact: $current->first(),
                lifeEvent: $lifeEvent,
                lifeEventType: $lifeEventType,
                happenedOn: CarbonImmutable::parse($validated['happened_on'] ?? $lifeEvent->happened_on),
                title: array_key_exists('title', $given) ? $validated['title'] : $lifeEvent->title,
                narrative: array_key_exists('narrative', $given) ? $validated['narrative'] : $lifeEvent->narrative,
                location: array_key_exists('location', $given) ? $validated['location'] : $lifeEvent->location,
                durationInMinutes: array_key_exists('duration_in_minutes', $given) ? $durationInMinutes : $lifeEvent->duration_in_minutes,
                participants: array_slice($people, 1),
                values: $values,
                keptFieldIds: $hiddenFieldIds,
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
            'vault' => $schema->string()->description('The address of the vault holding the life event.')->required(),
            'life_event' => $schema->string()->description('The id of the life event, as list-life-events gives it.')->required(),
            'life_event_type' => $schema->string()->description('The id of another type. Choosing one moves the life event to its custom fields and lets the old values go.'),
            'happened_on' => $schema->string()->description('The day it happened, as YYYY-MM-DD.'),
            'participants' => $schema->array()->items($schema->string())->description('The addresses of the contacts who were there, in order, replacing the list. The first has to stay the person it is recorded for.'),
            'title' => $schema->string()->description('A title. Null clears it.'),
            'narrative' => $schema->string()->description('What happened. Null clears it.'),
            'location' => $schema->string()->description('Where it happened. Null clears it.'),
            'duration_in_minutes' => $schema->integer()->description('How long it lasted, in minutes. Null clears it.'),
            'fields' => $schema->object()->description('Every custom field value, keyed by field id as get-life-event gives it, replacing the whole set. A repeating group takes a list of repetitions, each as {id, values}: the id is the row get-life-event gives, and values are keyed by the ids of the fields inside the group.'),
        ];
    }
}
