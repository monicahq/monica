<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateEntity;
use App\Exceptions\EntityNeedsAName;
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
use App\Models\Vault;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EntityController extends Controller
{
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
            ->with(['vault', 'entityType', 'structureVersion.structure.owner', 'avatar'])
            ->orderBy('sort_name')
            ->paginate($perPage);

        return response()->json([
            'data' => EntityResource::collection($entities->items())->resolve(),
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

        $fields = $entityType->defaultVersionIn($vault)?->inputFields()->filter(fn (Field $field): bool => $field->canBeFilledIn()) ?? collect();

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
                values: $validated['fields'] ?? [],
                labels: $validated['labels'] ?? [],
                labelWordings: $validated['label_wordings'] ?? [],
                yearIsUnknown: $validated['year_is_unknown'] ?? [],
                monthIsUnknown: $validated['month_is_unknown'] ?? [],
            )->execute();
        } catch (AuthorizationException|EntityNeedsAName $e) {
            throw ValidationException::withMessages(['name' => $e->getMessage()]);
        } catch (FieldIsRequired|FieldValueIsNotUnique|NotEnoughRepetitions|NotEnoughStorageLeft|TooManyRepetitions $e) {
            throw ValidationException::withMessages(['fields' => $e->getMessage()]);
        }

        return new EntityResource($entity->load(['vault', 'entityType', 'structureVersion.structure.owner', 'avatar']))
            ->response()
            ->setStatusCode(201);
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
