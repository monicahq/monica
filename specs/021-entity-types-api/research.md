# Research: Entity type endpoints

Six decisions. Everything else follows the five API groups already shipped.

## 1. The address is the uuid, not the slug

The browser reaches a type by its slug: `EntityType` carries `#[RouteKey('slug')]`, and the settings URLs
are readable because of it. The API does not, and the model already says why in its own docblock: the uuid
is there for the API, which wants something nobody has to have seen before.

**Decision**: `entity-types/{entityType}` where the parameter is constrained to a uuid, exactly as vaults and
contacts are, and looked up with an account scoped `firstOrFail`. The slug is carried in the answer so a
caller can still build a browser link.

**Rejected**: the slug in the path. It is mutable in principle (nothing rebuilds it today, but nothing in the
schema forbids a future gesture that would), it leaks the wording of an account's private vocabulary into
logs and referrers, and it would make this the only account scoped resource addressed differently from the
rest. The uuid costs a caller one extra field to remember and settles all three.

## 2. Two gestures, two addresses

The settings form asks for the wording and the active checkbox at once, and the web controller then routes
the checkbox to `ActivateEntityType` or `DeactivateEntityType` while the rest goes to `UpdateEntityType`.
The split is real: three actions, three journal entries, and `UpdateEntityType` deliberately never touches
`is_active`.

**Decision**: `PUT entity-types/{entityType}/activation` and `PUT entity-types/{entityType}/position`, each
sending the state wanted rather than asking for a toggle, mirroring `contacts/{contact}/favourite` and the
web position route. Declaration alone accepts `is_active` in the body, because `CreateEntityType` takes it.

**Rejected**: `is_active` in the correction body. It would make one request write two journal entries under
one name, and it would let a rename typed in a script silently stop an account filing records.

The path segment is `activation` rather than `active`, because a URL segment names a thing. The body field
stays `is_active`, matching the attribute the resource hands out.

## 3. The list answers in navigation order, and narrows nothing

`EntityTypesViewModel` orders by `position` and shows every type, active or not, because the screen is where
an account arranges and revives them.

**Decision**: `orderBy('position')` then `orderBy('id')` so the order is total and a page boundary cannot drop
or repeat a type, inactive types included, and no `filter`, `query` or `sort` parameter at all. Paginated with
the hand shaped `page` / `per_page` / `total` envelope every list here uses.

**Rejected**: copying the contacts endpoint's filtering vocabulary. An account holds a handful of types, the
navigation order is the only order that means anything, and a parameter nobody needs is one the reference has
to keep promising.

## 4. The correction replaces rather than patches

`UpdateEntityType` writes an absent description as null and an absent icon or colour as the product default.
A caller sending only a new plural name would therefore erase what the type is for and reset how it is drawn.
Both names and the colour and icon are `required`, and `description` is `present` and nullable, which is the
rule `VaultController@update` already settled under Principle VIII.

## 5. What needs no code

| | |
| --- | --- |
| The slug | `SlugBuilder` cleans a chosen one and derives one from the singular name, and `CreateEntityType` already asks `withTrashed()` whether it is taken, so a deleted type never releases its slug. |
| `entity_count` | A running total kept by `CreateEntity`, `DestroyEntity`, `RestoreEntity` and the two vault actions. Nothing here counts anything. |
| The order | `UpdateEntityTypePosition` renumbers every type of the account from one and puts a position past the end last. The API validates `min:1` and hands the number over. |
| The journal | Every one of the six actions dispatches `LogUserAction` on the low queue already. |

## 6. Deviations from the `api-writer` skill

| Deviation | Why |
| --- | --- |
| Controllers flat under `Api`, not `Api/Settings/Account/EntityTypes` | `Api/VaultController` already mirrors `App/Vaults/VaultController` that way. `.claude/CLAUDE.md` ranks the code already there above the skill. |
| The route parameter is constrained to a uuid, not `[1-9][0-9]*` | Section 1. A sequential number must reach nothing. |
| `index` returns a hand shaped `JsonResponse`, not `Resource::collection` | The reference promises `page`, `per_page` and `total` and nothing more, which is what every list here already does. |
| No "restricts to owners and editors" test | The product is mono-user and an account carries no role. The cases the skill means are the cross-account ones, and they are covered on all seven addresses. |

The skill also names `Tests\Unit\Services\ApiDocumentationTest` and `resources/docs/api/05-collections.php`.
Neither exists. The route sync test is `tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php` and the nearest
sibling to copy is `resources/docs/api/08-vaults.php`.
