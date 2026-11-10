<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateLifeEvent;
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
use App\Models\Group;
use App\Models\LifeEvent;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class GroupLifeEventController extends Controller
{
    public function index(Request $request, string $vault, string $group): JsonResponse
    {
        $group = $this->group($this->vault($request, $vault), $group);

        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $lifeEvents = LifeEvent::query()
            ->where('group_id', $group->id)
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

    public function create(Request $request, string $vault, string $group): JsonResponse
    {
        $vault = $this->vault($request, $vault);
        $group = $this->group($vault, $group);

        $validated = $request->validate([
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

        $lifeEventType = $request->user()->account->lifeEventTypes()
            ->where('uuid', $validated['life_event_type'])
            ->firstOrFail();

        $people = array_values(array_map(
            fn (string $address): Contact => Contact::query()->where('vault_id', $vault->id)->addressedBy($address)->firstOrFail(),
            $validated['participants'],
        ));

        $values = null;

        if ($request->input('fields') !== null) {
            $fields = $lifeEventType->structure?->publishedVersion?->inputFields() ?? new Collection;

            $values = $request->validate(
                FieldValueRules::withoutFiles($fields, $vault),
                [],
                $fields->mapWithKeys(fn (Field $field): array => ["fields.{$field->id}" => mb_strtolower($field->label)])->all(),
            )['fields'] ?? [];
        }

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
                createdFrom: LifeEventSourceEnum::Group,
                group: $group,
            )->execute();
        } catch (LifeEventTypeIsNotAvailable $e) {
            throw ValidationException::withMessages(['life_event_type' => $e->getMessage()]);
        } catch (FieldIsRequired|FieldValueIsNotUnique|NotEnoughRepetitions|NotEnoughStorageLeft|TooManyRepetitions $e) {
            throw ValidationException::withMessages(['fields' => $e->getMessage()]);
        }

        $lifeEvent = LifeEvent::query()
            ->where('vault_id', $vault->id)
            ->where('uuid', $lifeEvent->uuid)
            ->with(LifeEventResource::RELATIONS)
            ->firstOrFail();

        $resource = new LifeEventResource($lifeEvent);
        $resource->withSensitive = $this->reachesSensitiveFields($request->user());

        return $resource->response()->setStatusCode(201);
    }

    private function vault(Request $request, string $address): Vault
    {
        return $request->user()->account->vaults()
            ->addressedBy($address)
            ->firstOrFail();
    }

    private function group(Vault $vault, string $address): Group
    {
        return Group::query()
            ->where('vault_id', $vault->id)
            ->where('uuid', $address)
            ->firstOrFail();
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
