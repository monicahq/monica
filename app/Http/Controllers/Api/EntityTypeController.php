<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\CreateEntityType;
use App\Actions\DestroyEntityType;
use App\Actions\UpdateEntityType;
use App\Exceptions\CannotDeleteAnEntityTypeInUse;
use App\Http\Controllers\Controller;
use App\Http\Resources\EntityTypeResource;
use App\Models\EntityType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The kinds of object an account invented, when nothing the product ships with
 * fits: Animal, Company, Recipe, Wine bottle.
 *
 * A type belongs to the account rather than to a vault, so no vault appears in
 * the address, and one of another account is not found rather than refused,
 * because saying "you may not" would already say the type exists.
 *
 * The address is the uuid. The browser reaches the same type by its slug, which
 * is readable and is what a URL wants; a program wants something nobody has to
 * have seen before, and the slug is handed out in the answer so a caller can
 * still build a link into the application.
 */
class EntityTypeController extends Controller
{
    /**
     * The account's types, in the order it arranged them.
     *
     * Not newest first, which is how the other account wide lists here answer.
     * A type carries the place its owner put it in, and that is the order the
     * vault navigation and the settings screen show, so answering any other way
     * would hand a caller a different list from the one a person sees. The id
     * breaks a tie, so the order is total and a page boundary cannot drop or
     * repeat a type.
     *
     * Types nobody may file records under are listed with the rest, because the
     * screen lists them: one that is off is meant to be found and switched back
     * on, and a caller cannot offer that from a list it never sees.
     *
     * The page is shaped by hand for the same reason as everywhere else in this
     * API: the reference promises `page`, `per_page` and `total`, and nothing
     * more.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(
            (int) $request->query('per_page', (string) config('monica.api_default_items_per_page')),
            (int) config('monica.api_maximum_items_per_page'),
        ));

        $entityTypes = $request->user()->account->entityTypes()
            ->orderBy('position')
            ->orderBy('id')
            ->paginate($perPage);

        return response()->json([
            'data' => EntityTypeResource::collection($entityTypes->items())->resolve(),
            'meta' => [
                'page' => $entityTypes->currentPage(),
                'per_page' => $entityTypes->perPage(),
                'total' => $entityTypes->total(),
            ],
        ]);
    }

    public function show(Request $request, string $entityType): JsonResponse
    {
        return new EntityTypeResource($this->find($request, $entityType))->response()->setStatusCode(200);
    }

    /**
     * Declare a kind of object, which writes down the noun and nothing else.
     *
     * What one of its records holds is a template, described afterwards, so a
     * type exists for a while with no structure to show. The type goes at the
     * end of the navigation, and it may be declared already switched off, for
     * somebody setting an account up before anybody is meant to file anything.
     *
     * The slug is settled here and never rebuilt. Whoever declares the type may
     * choose it, and what they give is cleaned the same way a derived one is, so
     * nothing can put a space or an accent in a URL. Left out, it is made from
     * the singular name.
     */
    public function create(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name_singular' => ['required', 'string', 'max:255'],
            'name_plural' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],
            'icon' => ['nullable', 'string', Rule::in(EntityType::ICONS)],
            'colour' => ['nullable', 'string', Rule::in(EntityType::COLOURS)],
            'is_active' => ['nullable', 'boolean'],
            'slug' => [
                'nullable',
                'string',
                'max:'.EntityType::SLUG_LENGTH,
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('entity_types', 'slug')->where('account_id', $request->user()->account_id),
            ],
        ]);

        $entityType = new CreateEntityType(
            user: $request->user(),
            nameSingular: $validated['name_singular'],
            namePlural: $validated['name_plural'],
            description: $validated['description'] ?? null,
            icon: $validated['icon'] ?? null,
            colour: $validated['colour'] ?? null,
            isActive: $request->boolean('is_active', true),
            slug: $validated['slug'] ?? null,
        )->execute();

        return new EntityTypeResource($entityType)->response()->setStatusCode(201);
    }

    /**
     * The wording of the type, and how it is drawn. Nothing else about it.
     *
     * The slug is deliberately untouched, because a rename is a change of
     * wording rather than a change of thing: Animal becoming Pet is the same
     * type, and every link that already went out has to keep working. Where the
     * type sits, whether it may still be filed under and what is filed under it
     * are each somewhere else.
     *
     * This replaces rather than patches, which is why every field is demanded.
     * The operation underneath writes a field it is not given as the product
     * default, so a caller sending only a new plural name would erase what the
     * type is for and reset how it is drawn.
     */
    public function update(Request $request, string $entityType): JsonResponse
    {
        $entityType = $this->find($request, $entityType);

        $validated = $request->validate([
            'name_singular' => ['required', 'string', 'max:255'],
            'name_plural' => ['required', 'string', 'max:255'],
            'description' => ['present', 'nullable', 'string', 'max:65535'],
            'icon' => ['required', 'string', Rule::in(EntityType::ICONS)],
            'colour' => ['required', 'string', Rule::in(EntityType::COLOURS)],
        ]);

        new UpdateEntityType(
            user: $request->user(),
            entityType: $entityType,
            nameSingular: $validated['name_singular'],
            namePlural: $validated['name_plural'],
            description: $validated['description'],
            icon: $validated['icon'],
            colour: $validated['colour'],
        )->execute();

        return new EntityTypeResource($entityType->refresh())->response()->setStatusCode(200);
    }

    /**
     * Delete a kind of object, which is only allowed while no record of it
     * exists.
     *
     * A type that still holds records refuses to go, and the answer is to delete
     * those records first, deliberately, or to switch the type off and keep
     * them. Taking a hundred animals down with the word "Animal" is not
     * something one call should be able to do.
     *
     * The deletion is soft, and the slug is not released with it, so a link that
     * went out pointing at this type cannot later land on a different one.
     */
    public function destroy(Request $request, string $entityType): Response
    {
        try {
            new DestroyEntityType(
                user: $request->user(),
                entityType: $this->find($request, $entityType),
            )->execute();
        } catch (CannotDeleteAnEntityTypeInUse $e) {
            throw ValidationException::withMessages(['entityType' => $e->getMessage()]);
        }

        return response()->noContent(204);
    }

    /**
     * The type the address names, within the caller's account.
     *
     * Scoping the query is what makes the compartment hold, and letting it throw
     * is what makes another account's type indistinguishable from one that never
     * existed. A deleted type answers the same way, since the relation excludes
     * it.
     */
    private function find(Request $request, string $uuid): EntityType
    {
        return $request->user()->account->entityTypes()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
