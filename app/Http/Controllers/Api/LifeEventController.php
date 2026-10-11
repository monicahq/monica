<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateLifeEvent;
use App\Actions\DestroyLifeEvent;
use App\Actions\UpdateLifeEvent;
use App\Enums\ApiKeyScopeEnum;
use App\Enums\LifeEventSourceEnum;
use App\Exceptions\FieldIsRequired;
use App\Exceptions\FieldValueIsNotUnique;
use App\Exceptions\LifeEventTypeIsNotAvailable;
use App\Exceptions\NotEnoughRepetitions;
use App\Exceptions\NotEnoughStorageLeft;
use App\Exceptions\TooManyRepetitions;
use App\Helpers\FieldValueRules;
use App\Http\Controllers\Controller;
use App\Http\Resources\LifeEventResource;
use App\Models\Contact;
use App\Models\Field;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\LifeEventType;
use App\Models\StructureVersion;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class LifeEventController extends Controller
{
    public function index(Request $request, string $vault): JsonResponse
    {
        $vault = $this->vault($request, $vault);

        $validated = $request->validate([
            'contact' => ['nullable', 'string'],
        ]);

        $contact = isset($validated['contact'])
            ? Contact::query()->where('vault_id', $vault->id)->addressedBy($validated['contact'])->firstOrFail()
            : null;

        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $lifeEvents = LifeEvent::query()
            ->where('vault_id', $vault->id)
            ->when($contact, fn (Builder $query, Contact $contact): Builder => $query->whereHas('participants', fn (Builder $query): Builder => $query
                ->where('participant_type', $contact->getMorphClass())
                ->where('participant_id', $contact->id)))
            ->with(LifeEventResource::RELATIONS)
            ->orderByDesc('happened_on')
            ->orderByDesc('id')
            ->paginate($perPage);

        $withSensitive = $this->reachesSensitiveFields($request->user());

        return response()->json([
            'data' => array_map(function (LifeEvent $lifeEvent) use ($withSensitive, $request): array {
                $resource = new LifeEventResource($lifeEvent);
                $resource->withSensitive = $withSensitive;

                return $resource->resolve($request);
            }, $lifeEvents->items()),
            'meta' => [
                'page' => $lifeEvents->currentPage(),
                'per_page' => $lifeEvents->perPage(),
                'total' => $lifeEvents->total(),
            ],
        ]);
    }

    public function show(Request $request, string $vault, string $lifeEvent): JsonResponse
    {
        $lifeEvent = $this->lifeEvent($this->vault($request, $vault), $lifeEvent);

        return $this->answer($request, $lifeEvent, 200);
    }

    public function create(Request $request, string $vault): JsonResponse
    {
        $vault = $this->vault($request, $vault);

        $validated = $request->validate($this->rules());

        $lifeEventType = $this->lifeEventType($request, $validated['life_event_type']);
        $people = $this->participants($vault, $validated['participants']);
        $values = $this->values($request, $vault, $lifeEventType->structure?->publishedVersion);

        try {
            $lifeEvent = new CreateLifeEvent(
                user: $request->user(),
                vault: $vault,
                contact: $people[0],
                lifeEventType: $lifeEventType,
                happenedOn: CarbonImmutable::parse($validated['happened_on']),
                title: $validated['title'] ?? null,
                narrative: $validated['narrative'] ?? null,
                location: $validated['location'] ?? null,
                durationInMinutes: isset($validated['duration_in_minutes']) ? (int) $validated['duration_in_minutes'] : null,
                participants: array_slice($people, 1),
                values: $values ?? [],
                createdFrom: LifeEventSourceEnum::Api,
            )->execute();
        } catch (LifeEventTypeIsNotAvailable $e) {
            throw ValidationException::withMessages(['life_event_type' => $e->getMessage()]);
        } catch (FieldIsRequired|FieldValueIsNotUnique|NotEnoughRepetitions|NotEnoughStorageLeft|TooManyRepetitions $e) {
            throw ValidationException::withMessages(['fields' => $e->getMessage()]);
        }

        return $this->answer($request, $this->lifeEvent($vault, $lifeEvent->uuid), 201);
    }

    public function update(Request $request, string $vault, string $lifeEvent): JsonResponse
    {
        $vault = $this->vault($request, $vault);
        $lifeEvent = $this->lifeEvent($vault, $lifeEvent);

        $validated = $request->validate($this->rules());

        $lifeEventType = $this->lifeEventType($request, $validated['life_event_type']);
        $subject = $this->subject($lifeEvent);
        $people = $this->participants($vault, $validated['participants']);

        if ($people[0]->id !== $subject->id) {
            throw ValidationException::withMessages(['participants' => __('The first participant has to stay the person the life event is recorded for.')]);
        }

        $version = $lifeEventType->id === $lifeEvent->life_event_type_id
            ? $lifeEvent->structureVersion
            : $lifeEventType->structure?->publishedVersion;

        $values = $this->values($request, $vault, $version);

        $hiddenFieldIds = $this->reachesSensitiveFields($request->user()) ? [] : ($version?->inputFields() ?? new Collection)
            ->filter(fn (Field $field): bool => $field->is_sensitive && ! array_key_exists($field->id, $values ?? []))
            ->pluck('id')
            ->all();

        try {
            new UpdateLifeEvent(
                user: $request->user(),
                vault: $vault,
                contact: $subject,
                lifeEvent: $lifeEvent,
                lifeEventType: $lifeEventType,
                happenedOn: CarbonImmutable::parse($validated['happened_on']),
                title: $validated['title'] ?? null,
                narrative: $validated['narrative'] ?? null,
                location: $validated['location'] ?? null,
                durationInMinutes: isset($validated['duration_in_minutes']) ? (int) $validated['duration_in_minutes'] : null,
                participants: array_slice($people, 1),
                values: $values,
                keptFieldIds: $hiddenFieldIds,
            )->execute();
        } catch (LifeEventTypeIsNotAvailable $e) {
            throw ValidationException::withMessages(['life_event_type' => $e->getMessage()]);
        } catch (FieldIsRequired|FieldValueIsNotUnique|NotEnoughRepetitions|NotEnoughStorageLeft|TooManyRepetitions $e) {
            throw ValidationException::withMessages(['fields' => $e->getMessage()]);
        }

        return $this->answer($request, $this->lifeEvent($vault, $lifeEvent->uuid), 200);
    }

    public function destroy(Request $request, string $vault, string $lifeEvent): Response
    {
        $vault = $this->vault($request, $vault);
        $lifeEvent = $this->lifeEvent($vault, $lifeEvent);

        new DestroyLifeEvent(
            user: $request->user(),
            vault: $vault,
            contact: $this->subject($lifeEvent),
            lifeEvent: $lifeEvent,
        )->execute();

        return response()->noContent(204);
    }

    private function answer(Request $request, LifeEvent $lifeEvent, int $status): JsonResponse
    {
        $resource = new LifeEventResource($lifeEvent);
        $resource->withSensitive = $this->reachesSensitiveFields($request->user());

        return $resource->response()->setStatusCode($status);
    }

    private function vault(Request $request, string $address): Vault
    {
        return $request->user()->account->vaults()
            ->addressedBy($address)
            ->firstOrFail();
    }

    private function lifeEvent(Vault $vault, string $address): LifeEvent
    {
        return LifeEvent::query()
            ->where('vault_id', $vault->id)
            ->where('uuid', $address)
            ->with(LifeEventResource::RELATIONS)
            ->firstOrFail();
    }

    /** @return array<string, array<int, string>> */
    private function rules(): array
    {
        return [
            'life_event_type' => ['required', 'string'],
            'happened_on' => ['required', 'date_format:Y-m-d'],
            'participants' => ['required', 'array', 'min:1'],
            'participants.*' => ['string', 'distinct'],
            'title' => ['nullable', 'string', 'max:255'],
            'narrative' => ['nullable', 'string', 'max:65535'],
            'location' => ['nullable', 'string', 'max:255'],
            'duration_in_minutes' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'fields' => ['nullable', 'array'],
        ];
    }

    private function lifeEventType(Request $request, string $uuid): LifeEventType
    {
        return $request->user()->account->lifeEventTypes()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    /**
     * @param  array<int, string>  $addresses
     * @return list<Contact>
     */
    private function participants(Vault $vault, array $addresses): array
    {
        return array_values(array_map(
            fn (string $address): Contact => Contact::query()->where('vault_id', $vault->id)->addressedBy($address)->firstOrFail(),
            $addresses,
        ));
    }

    private function subject(LifeEvent $lifeEvent): Contact
    {
        $subject = $lifeEvent->participants
            ->map(fn (LifeEventParticipant $participation): mixed => $participation->participant)
            ->first(fn (mixed $person): bool => $person instanceof Contact);

        if (! $subject instanceof Contact) {
            throw ValidationException::withMessages(['participants' => __('Nobody who took part in this life event is left, so it cannot be changed here.')]);
        }

        return $subject;
    }

    /** @return array<int, mixed>|null */
    private function values(Request $request, Vault $vault, ?StructureVersion $version): ?array
    {
        if ($request->input('fields') === null) {
            return null;
        }

        $fields = $version?->inputFields() ?? new Collection;

        return $request->validate(
            FieldValueRules::withoutFiles($fields, $vault),
            [],
            $fields->mapWithKeys(fn (Field $field): array => ["fields.{$field->id}" => mb_strtolower($field->label)])->all(),
        )['fields'] ?? [];
    }

    private function reachesSensitiveFields(User $user): bool
    {
        $token = $user->currentAccessToken();

        if (! $token instanceof PersonalAccessToken) {
            return false;
        }

        return in_array(ApiKeyScopeEnum::SensitiveFields->value, $token->abilities ?? [], true);
    }
}
