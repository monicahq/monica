# Research: activity category endpoints

## 1. Whether a supplied category may be renamed

**Decision**: yes. Being supplied governs deletion and nothing else.

**Rationale**: `spec/spec.md` §9.3 says it in as many words, and the constitution makes `spec/` the
source of truth for what the product does. `spec/models/028` agrees: the user may create, rename,
reorder and deactivate, and only deletion is closed off.

**Consequence, and a defect it exposes**: the shipped relationship categories refuse the same rename,
through `CannotChangeASuppliedRelationshipCategory`, which contradicts the identical sentence in
§8.2. That is a defect in the code, not in the specification. It shipped with spec 024 and spec 033
carried it into the API and the reference without noticing. It is not repaired here, because this
feature owns no relationship code, and it is named in the pull request so it does not stand silently.

**Consequence for the exception**: this record needs `CannotDeleteASuppliedActivityCategory`, a
deletion-only refusal, rather than the change-wide refusal its sibling uses.

## 2. Counting natures before natures exist

**Decision**: one method on the model, `activityTypeCount()`, returning zero, which the deletion
guard and the resource both read.

**Rationale**: FR-004 asks for the count in every answer and FR-010 refuses a deletion on it, so
neither can be left out and wait. `RelationshipType::relationshipCount()` is the shape this follows.
Putting the zero in one named place means the day `ActivityType` lands, that method becomes a real
count and the guard, the action, the controller, the routes and the reference are all untouched.

**Alternative**: leaving the field out of the answer until the record exists, rejected because a
field appearing later is a change to a published contract, and the spec asks for it now.

## 3. Where the eight come from

**Decision**: `ActivityCategoryEnum`, eight cases implementing `Translatable`, written by
`CreateDefaultActivityCategories` from `CreateAccount`, inside its transaction.

**Rationale**: this is exactly how `RelationshipCategoryEnum` and `CreateDefaultRelationshipTypes`
already work, down to the supplied row carrying `key`, `is_system` and a `name_translation_key` with
`name` left null. `Translatable` is what puts the eight names in front of `monica:localize`.

**No backfill**: the product is not in production, so an account that predates this is rebuilt with
`migrate:fresh` rather than patched, which is what the migrations skill requires to be said wherever
somebody is told to migrate.

## 4. How a category is addressed

**Decision**: uuid, constrained in the route with `[0-9a-fA-F-]{36}` so a wrong shape answers 404
before a query runs, resolved with an account-scoped `firstOrFail`.

**Rationale**: every account-scoped endpoint here does this, and no account-scoped endpoint exposes a
sequential id. There is no web route to bind, so the question of a route key does not arise: the
model carries `ResolvesModelInAccount` for the settings screen this record will eventually get.

## 5. What this costs in translation

Fifteen new strings: the eight supplied names, and seven journal sentences for seeding, adding,
renaming, moving, offering, retiring and deleting. Each has to be translated in all 31 locales, which
is 450 values. It is mechanical, and `monica:localize --check` is what proves it was done.

## 6. Availability with nothing to switch off yet

**Decision**: store it, offer the endpoint, and do not pretend it has an effect.

**Rationale**: nothing offers activity natures, so retiring a category changes nothing a person can
observe today. The column is what the picker will read, and the endpoint is what will set it. The
quickstart says so plainly rather than claiming an effect the feature cannot demonstrate. Its sibling
had the opposite luck: `RelationshipTypeChoices` already honoured the column, so that endpoint could
be proved end to end on the day it shipped.

## 7. No screen, deliberately

The request excludes the interface, so there is no controller under `App`, no view model and no
template, and `routes/web.php` is not touched. The record carries `ResolvesModelInAccount` anyway,
because that is what the settings screen will need and it costs one line now rather than a change to
the model later.
