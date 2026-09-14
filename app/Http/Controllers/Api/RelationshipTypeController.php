<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateRelationshipType;
use App\Actions\DestroyRelationshipType;
use App\Actions\UpdateRelationshipType;
use App\Exceptions\CannotChangeARelationshipTypeDirection;
use App\Exceptions\RelationshipTypeNameIsAlreadyUsed;
use App\Http\Controllers\Controller;
use App\Http\Resources\RelationshipTypeResource;
use App\Models\RelationshipCategory;
use App\Models\RelationshipType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RelationshipTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $types = $request->user()->account->relationshipTypes()
            ->select('relationship_types.*')
            ->join('relationship_categories', 'relationship_categories.id', '=', 'relationship_types.relationship_category_id')
            ->with('relationshipCategory')
            ->withCount('relationships')
            ->orderBy('relationship_categories.position')
            ->orderBy('relationship_types.position')
            ->orderBy('relationship_types.id')
            ->paginate($perPage);

        return response()->json([
            'data' => RelationshipTypeResource::collection($types->items())->resolve(),
            'meta' => [
                'page' => $types->currentPage(),
                'per_page' => $types->perPage(),
                'total' => $types->total(),
            ],
        ]);
    }

    public function show(Request $request, string $relationshipType): JsonResponse
    {
        return new RelationshipTypeResource($this->find($request, $relationshipType))->response()->setStatusCode(200);
    }

    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'relationship_category_id' => [
                'required',
                'string',
                Rule::exists('relationship_categories', 'uuid')
                    ->where('account_id', $request->user()->account_id)
                    ->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'role_a' => ['required', 'string', 'max:255'],
            'role_b' => [
                Rule::requiredIf(fn (): bool => ! $request->boolean('is_symmetric', true)),
                'nullable',
                'string',
                'max:255',
            ],
            'is_symmetric' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        try {
            $type = new CreateRelationshipType(
                user: $request->user(),
                relationshipCategory: $this->category($request, $validated['relationship_category_id']),
                name: $validated['name'],
                roleA: $validated['role_a'],
                roleB: $validated['role_b'] ?? $validated['role_a'],
                isSymmetric: $request->boolean('is_symmetric', true),
                isActive: $request->boolean('is_active', true),
            )->execute();
        } catch (RelationshipTypeNameIsAlreadyUsed $e) {
            throw ValidationException::withMessages(['name' => $e->getMessage()]);
        }

        return new RelationshipTypeResource($this->find($request, $type->uuid))->response()->setStatusCode(201);
    }

    public function update(Request $request, string $relationshipType): JsonResponse
    {
        $type = $this->find($request, $relationshipType);
        $wantsSymmetric = $request->boolean('is_symmetric', $type->is_symmetric);

        $validated = $request->validate([
            'relationship_category_id' => [
                'required',
                'string',
                Rule::exists('relationship_categories', 'uuid')
                    ->where('account_id', $request->user()->account_id)
                    ->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'role_a' => ['required', 'string', 'max:255'],
            'role_b' => [
                Rule::requiredIf(fn (): bool => ! $wantsSymmetric),
                'nullable',
                'string',
                'max:255',
            ],
            'is_symmetric' => ['nullable', 'boolean'],
        ]);

        try {
            new UpdateRelationshipType(
                user: $request->user(),
                relationshipType: $type,
                relationshipCategory: $this->category($request, $validated['relationship_category_id']),
                name: $validated['name'],
                roleA: $validated['role_a'],
                roleB: $validated['role_b'] ?? $validated['role_a'],
                isSymmetric: $wantsSymmetric,
                isActive: $type->is_active,
            )->execute();
        } catch (RelationshipTypeNameIsAlreadyUsed $e) {
            throw ValidationException::withMessages(['name' => $e->getMessage()]);
        } catch (CannotChangeARelationshipTypeDirection $e) {
            throw ValidationException::withMessages(['is_symmetric' => $e->getMessage()]);
        }

        return new RelationshipTypeResource($this->find($request, $relationshipType))->response()->setStatusCode(200);
    }

    public function destroy(Request $request, string $relationshipType): Response
    {
        try {
            new DestroyRelationshipType(
                user: $request->user(),
                relationshipType: $this->find($request, $relationshipType),
            )->execute();
        } catch (AuthorizationException $e) {
            throw ValidationException::withMessages(['relationshipType' => $e->getMessage()]);
        }

        return response()->noContent(204);
    }

    private function category(Request $request, string $uuid): RelationshipCategory
    {
        return $request->user()->account->relationshipCategories()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    private function find(Request $request, string $uuid): RelationshipType
    {
        return $request->user()->account->relationshipTypes()
            ->with('relationshipCategory')
            ->withCount('relationships')
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
