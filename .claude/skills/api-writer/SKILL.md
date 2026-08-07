---
name: api-writer
description: Build a complete API surface for a given model or concept. Use when the user asks to add API methods, expose a resource via API, or mirror a web controller as an API. Activates when user mentions API methods, API controller, API routes, or wants to expose an existing web resource via API.
---

# API writer

The API mirrors the web controllers under `Api`, returns Eloquent Resources, and reuses the same Actions. You MUST use the existing `collections` API (controller, resource, routes, tests, docs) as the reference implementation.

A user belongs to exactly one account, so there MUST be no tenant prefix in the URLs: every endpoint MUST be scoped through `$request->user()->account`.

## Step 1 — Study the existing web controller

You MUST read the web controller, if it exists (`app/Http/Controllers/App/…`), and extract:

- The **resource name** (singular, e.g. `Collection`)
- Which **Actions** are used (`CreateXxx`, `UpdateXxx`, `DestroyXxx`)
- The **validated fields** and their rules
- The **permission model**. You MUST check the Action's `validate()` method (owners and editors write, viewers read)

## Step 2 — Create the Eloquent Resource

You MUST create `app/Http/Resources/{Resource}Resource.php`, using `CollectionResource` as the reference.

- You MUST add a `/** @mixin {Model} */` docblock and extend `JsonResource`.
- `type` MUST be the snake_case resource name (e.g. `'collection_type'`).
- `id` MUST be cast to string.
- `attributes` MUST contain the relevant model fields, and you MUST render timestamps as Unix integers via `->timestamp` (use `?->timestamp` for nullable ones like `updated_at`).
- `links.self` MUST point to the `show` route (e.g. `route('api.collections.show', $this->id)`; nested resources pass both parameters).

## Step 3 — Create the API controller

You MUST follow the [controllers skill](../controllers/SKILL.md), then create `app/Http/Controllers/Api/{Resource}Controller.php`, mirroring the web controller's location under `Api` instead of `App` (auth lives in `Api/Auth`, user-scoped administration in `Api/Administration`).

- You MUST only use these methods: `index`, `show`, `create`, `update`, `destroy`. You MUST extract extra actions to their own controller (e.g. `CollectionTypeCollectionController` for the sync endpoint).
- You MUST NOT put domain logic in the controller. You MUST call the Actions and pass `user: $request->user()` with named arguments.
- You MUST read route parameters with `$request->route()->parameter('collection')`.
- You MUST scope every lookup to the account and let it throw: `$request->user()->account->collections()->findOrFail($id)` (no try/catch, the framework returns 404).
- You MUST type-hint the return: `AnonymousResourceCollection` from `index`, `JsonResponse` from `show`/`create`/`update`, `Response` from `destroy`.
- You MUST set the status codes explicitly: `200` for `show`/`update`, `201` for `create` via `->response()->setStatusCode(...)`, and `response()->noContent(204)` for `destroy`.
- `index` MUST return `{Resource}::collection($paginated)`, ordered, and paginated with `per_page` clamped to `config('app.maximum_items_per_page')`.

## Step 4 — Register routes

- You MUST add the routes to `routes/api.php`, inside the `auth:sanctum` middleware group.
- You MUST add the controller `use` import in alphabetical order with the other imports.
- URLs MUST be plural kebab-case (`collection-types`), and route parameters camelCase (`{collectionType}`).
- You MUST name routes `{resource}` for the index and `{resource}.{action}` for the rest, in camelCase (the group adds the `api.` prefix): `collections`, `collections.show`, `collectionTypes.customFields.update`.
- You MUST constrain numeric IDs with `->where('collection', '[1-9][0-9]*')`.

## Step 5 — Write tests

You MUST create `tests/Feature/Controllers/Api/{Resource}ControllerTest.php`, and you MUST follow the conventions of the existing files there (e.g. `CollectionControllerTest.php`) and the rules in `.claude/rules/testing.md`: PHPUnit classes, `#[Test]` methods named in snake_case, `use RefreshDatabase;`, and a shared `$jsonStructure` property.

- You MUST authenticate with `Sanctum::actingAs($user)`. `User::factory()` creates an account owner.
- You MUST make requests with `$this->json('METHOD', '/api/…', $data)`.
- You MUST fake the queue with `Queue::fake()` in tests that hit an Action (Actions dispatch `LogUserAction`).
- You MUST set up data with explicit `['key' => $value]` arrays on factories, and you MUST NOT use `for()`.
- You MUST assert HTTP status and JSON only, reusing `$this->jsonStructure` with `assertJsonStructure()`/`assertJsonPath()`/`assertJsonCount()`.
- You MUST use `assignUserToAccount(user, account, role)` from the `TestCase` to build a viewer for the permission cases.

You MUST cover these cases (cross-account and missing-permission both surface as 404):
1. `it_lists_the_{resources}_of_the_account…` — 200 + count + ordering
2. `it_does_not_list_{resources}_from_another_account` — 200 + empty list
3. `it_shows_a_{resource}` — 200 + structure
4. `it_returns_not_found_for_a_{resource}_from_another_account` — 404
5. `it_creates_a_{resource}` — 201 + structure
6. `it_validates_the_…_when_creating_a_{resource}` — 422 + `assertJsonValidationErrors`
7. `it_restricts_{resource}_creation_to_owners_and_editors` — viewer gets 404
8. `it_updates_a_{resource}` — 200 + structure
9. `it_restricts_{resource}_updates_to_owners_and_editors` — 404
10. `it_deletes_a_{resource}` — 204 + `assertModelMissing` (or `assertSoftDeleted` for soft-deleting models)
11. `it_restricts_{resource}_deletion_to_owners_and_editors` — 404

## Step 6 — Document the endpoints

You MUST load and follow the **`api-docs-writer`** skill: add the endpoints to a definition file in `resources/docs/api`, which feeds the docs portal at `/docs`.

This step is not optional. `Tests\Unit\Services\ApiDocumentationTest` asserts that every route in `routes/api.php` is documented, so the suite fails until the new endpoints have documentation.
