<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateEntity;
use App\Actions\UpdateEntity;
use App\Enums\ApiKeyScopeEnum;
use App\Exceptions\EntityNeedsAName;
use App\Exceptions\EntityTemplateChanged;
use App\Exceptions\EntityTemplateNotOffered;
use App\Exceptions\FieldIsRequired;
use App\Exceptions\FieldValueIsNotUnique;
use App\Exceptions\NotEnoughRepetitions;
use App\Exceptions\NotEnoughStorageLeft;
use App\Exceptions\TooManyRepetitions;
use App\Helpers\FieldValueRules;
use App\Http\Controllers\Controller;
use App\Http\Resources\EntityResource;
use App\Models\Entity;
use App\Models\EntityType;
use App\Models\Field;
use App\Models\StructureVersion;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class EntityController extends Controller
{
    private const array RELATIONS = [
        'vault',
        'entityType',
        'structureVersion.structure.owner',
        'avatar',
        'fieldValues.field',
        'fieldValues.valueListItem',
        'fieldValues.file.vault',
        'fieldValues.relatedContact',
        'fieldValues.relatedEntity',
    ];

    public function index(Request $request, string $vault, string $entityType): JsonResponse
    {
        $vault = $this->vault($request, $vault);
        $entityType = $this->entityType($request, $vault, $entityType);

        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $entities = Entity::query()
            ->where('vault_id', $vault->id)
            ->where('entity_type_id', $entityType->id)
            ->with(self::RELATIONS)
            ->orderBy('sort_name')
            ->paginate($perPage);

        $withSensitive = $this->reachesSensitiveFields($request->user());

        return response()->json([
            'data' => array_map(function (Entity $entity) use ($withSensitive, $request): array {
                $resource = new EntityResource($entity);
                $resource->withSensitive = $withSensitive;

                return $resource->resolve($request);
            }, $entities->items()),
            'meta' => [
                'page' => $entities->currentPage(),
                'per_page' => $entities->perPage(),
                'total' => $entities->total(),
            ],
        ]);
    }

    public function create(Request $request, string $vault, string $entityType): JsonResponse
    {
        $vault = $this->vault($request, $vault);
        $entityType = $this->entityType($request, $vault, $entityType);

        $request->validate([
            'structure_version_id' => ['nullable', 'integer'],
        ]);

        $version = null;

        if ($request->filled('structure_version_id')) {
            $version = StructureVersion::query()
                ->where('account_id', $request->user()->account_id)
                ->find($request->integer('structure_version_id'))
                ?? throw ValidationException::withMessages(['structure_version_id' => __('This template cannot be used for this type.')]);
        }

        $fields = ($version ?? $entityType->versionsOnOfferIn($vault)->first())?->inputFields()->filter(fn (Field $field): bool => $field->canBeFilledIn()) ?? collect();

        $validated = $request->validate(
            array_merge([
                'name' => ['required', 'string', 'max:255'],
                'labels' => ['array'],
                'label_wordings' => ['array'],
                'year_is_unknown' => ['array'],
                'month_is_unknown' => ['array'],
            ], FieldValueRules::for($fields, $vault)),
            [],
            $fields->mapWithKeys(fn (Field $field): array => ["fields.{$field->id}" => mb_strtolower($field->label)])->all(),
        );

        try {
            $entity = new CreateEntity(
                user: $request->user(),
                vault: $vault,
                entityType: $entityType,
                name: $validated['name'],
                structureVersion: $version,
                values: $validated['fields'] ?? [],
                labels: $validated['labels'] ?? [],
                labelWordings: $validated['label_wordings'] ?? [],
                yearIsUnknown: $validated['year_is_unknown'] ?? [],
                monthIsUnknown: $validated['month_is_unknown'] ?? [],
            )->execute();
        } catch (AuthorizationException|EntityNeedsAName $e) {
            throw ValidationException::withMessages(['name' => $e->getMessage()]);
        } catch (EntityTemplateChanged|EntityTemplateNotOffered $e) {
            throw ValidationException::withMessages(['structure_version_id' => $e->getMessage()]);
        } catch (FieldIsRequired|FieldValueIsNotUnique|NotEnoughRepetitions|NotEnoughStorageLeft|TooManyRepetitions $e) {
            throw ValidationException::withMessages(['fields' => $e->getMessage()]);
        }

        return $this->respond($request, $entity, 201);
    }

    public function show(Request $request, string $vault, string $entity): JsonResponse
    {
        $vault = $this->vault($request, $vault);

        return $this->respond($request, $this->entity($vault, $entity), 200);
    }

    public function update(Request $request, string $vault, string $entity): JsonResponse
    {
        $vault = $this->vault($request, $vault);
        $entity = $this->entity($vault, $entity);

        $fields = $entity->structureVersion->inputFields()->filter(fn (Field $field): bool => $field->canBeFilledIn());

        $fieldRules = FieldValueRules::for($fields, $vault);

        foreach ($fields as $field) {
            $fieldRules["fields.{$field->id}"] = ['sometimes', ...$fieldRules["fields.{$field->id}"] ?? []];
        }

        $validated = $request->validate(
            array_merge([
                'name' => ['sometimes', 'required', 'string', 'max:255'],
                'labels' => ['array'],
                'label_wordings' => ['array'],
                'year_is_unknown' => ['array'],
                'month_is_unknown' => ['array'],
            ], $fieldRules),
            [],
            $fields->mapWithKeys(fn (Field $field): array => ["fields.{$field->id}" => mb_strtolower($field->label)])->all(),
        );

        $values = $validated['fields'] ?? [];

        try {
            $entity = new UpdateEntity(
                user: $request->user(),
                vault: $vault,
                entity: $entity,
                name: $validated['name'] ?? $entity->name,
                values: $values,
                labels: $validated['labels'] ?? [],
                labelWordings: $validated['label_wordings'] ?? [],
                yearIsUnknown: $validated['year_is_unknown'] ?? [],
                monthIsUnknown: $validated['month_is_unknown'] ?? [],
                keptFieldIds: $fields
                    ->reject(fn (Field $field): bool => array_key_exists($field->id, $values))
                    ->pluck('id')
                    ->all(),
            )->execute();
        } catch (EntityNeedsAName $e) {
            throw ValidationException::withMessages(['name' => $e->getMessage()]);
        } catch (FieldIsRequired|FieldValueIsNotUnique|NotEnoughRepetitions|NotEnoughStorageLeft|TooManyRepetitions $e) {
            throw ValidationException::withMessages(['fields' => $e->getMessage()]);
        }

        return $this->respond($request, $entity, 200);
    }

    private function respond(Request $request, Entity $entity, int $status): JsonResponse
    {
        $resource = new EntityResource($entity->fresh(self::RELATIONS));
        $resource->withSensitive = $this->reachesSensitiveFields($request->user());

        return $resource->response()->setStatusCode($status);
    }

    private function entity(Vault $vault, string $uuid): Entity
    {
        return Entity::query()
            ->where('vault_id', $vault->id)
            ->where('uuid', $uuid)
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

    private function vault(Request $request, string $address): Vault
    {
        return $request->user()->account->vaults()
            ->addressedBy($address)
            ->firstOrFail();
    }

    private function entityType(Request $request, Vault $vault, string $uuid): EntityType
    {
        return $request->user()->account->entityTypes()
            ->where('uuid', $uuid)
            ->visibleInVault($vault)
            ->firstOrFail();
    }
}
