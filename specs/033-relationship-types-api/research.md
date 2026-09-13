# Research: relationship category and type endpoints

## 1. How an item is addressed

**Decision**: uuid, as `entity-types` and `templates` are, constrained with `[0-9a-fA-F-]{36}` so a
wrong shape answers 404 before a query runs.

**Rationale**: both tables carry a unique `uuid`, unused today because the settings routes bind on
`id` with `whereNumber`. No account scoped endpoint exposes a sequential id, which would leak how
many an instance holds. Binding on `id` was rejected for that reason; neither record has a slug.

## 2. Where availability is set

**Decision**: its own address, `PUT .../activation`, served by four new actions,
`ActivateRelationshipType`, `DeactivateRelationshipType`, `ActivateRelationshipCategory` and
`DeactivateRelationshipCategory`, copied from `ActivateEntityType`.

**Rationale**: `UpdateRelationshipType` takes `isActive` because the settings form carries a
checkbox, so today retiring a type means resending its whole wording, and any slip there rewrites
it. `ActivateEntityType` and `ActivateTemplate` already establish the separate action, and a state
that is sent rather than toggled is idempotent, which a `PUT` has to be.

**Alternatives**: the activation controller calling `UpdateRelationshipType` with the current
wording, rejected as logic in the transport. Dropping `isActive` from `UpdateRelationshipType` and
pointing the web controller at the new actions, rejected as a change to a shipped screen this
feature was not asked to touch; it stays open later and nothing here blocks it.

**Consequence**: four `UserActionEnum` cases and four journal sentences, translated in all 31
locales. `is_active` then has two writers, which is one boolean assignment, and `CLAUDE.md` says to
repeat that rather than hide it behind a name.

## 3. Retiring a category, which no screen can do

**Decision**: offered, for categories as for types.

**Rationale**: `RelationshipTypeChoices` already drops an inactive category and every type under it
from the picker, so the column is read and honoured, and `spec/models/024` lists deactivation among
what a user may do. Spec 024 shipped no control and said the record keeps room. The endpoint reaches
a live capability rather than inventing one, and a settings control added later needs no change
here. Mirroring the browser exactly was rejected: principle X is a floor.

## 4. Direction on a correction

**Decision**: `is_symmetric` is optional on the update. Absent, the direction holds. Present and
frozen, the request answers 422 carrying the exception's own sentence.

**Rationale**: `UpdateRelationshipType` already throws `CannotChangeARelationshipTypeDirection` for
a supplied type or one in use, so the controller catches and rethrows as `ValidationException`, as
`EntityTypeController@destroy` does with the in-use exception. Copying
`RelationshipTypeController::wantedDirection`, which silently substitutes the current direction, was
rejected: a browser form ignoring a disabled field is right, an API accepting a change it did not
make is not.

## 5. Counting relationships without an N+1

**Decision**: `withCount('relationships')` on the type queries and `withCount('types')` on the
category queries, the resource reading the loaded count.

**Rationale**: `entity_count` is a denormalised column, so `EntityTypeResource` reads a field.
Relationship types have none: `relationshipCount()` runs a query per row, which a page of 25 turns
into 25. The count decides whether a type may be deleted, so it cannot be dropped from the answer.

## 6. Reaching the types of one category

**Decision**: `GET relationship-categories/{relationshipCategory}/types`, beside the flat
`GET relationship-types` that answers the whole account.

**Rationale**: the nesting and the short child segment match `templates/{template}/versions`. A
`category` query parameter was rejected because `code-style.md` asks for a query string only with a
reason, and the nested address is already the house pattern.

## 7. Deviations from `.claude/skills/api-writer/SKILL.md`

The skill is stale, which `CLAUDE.md` records as a TODO, and precedence puts shipped code above it.
Its worked example, `collections`, is not a model, controller or resource here. What ships is
followed instead: `id` is the uuid, page bounds come from `config('monica.api_*_items_per_page')`,
route names carry the `api.v1.` prefix, the uuid constraint replaces the numeric one, and the parity
test is `tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php`. Its permission cases (owners,
editors, viewers) describe roles this product does not have, so each is a cross-account case.

## 8. Rewording a supplied word is one way

`UpdateRelationshipType` blanks the three `*_translation_key` columns once the user supplies words,
so the supplied wording cannot be restored and the API offers no way back. Keeping the key beside
the override is a change to the vocabulary record, not to its coverage.
