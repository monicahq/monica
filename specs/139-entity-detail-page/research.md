# Research: Record of an invented type, read and edited

## R1. Web addresses

- **Decision**: `GET vaults/{vault}/entities/{entityType}/{entity}/{page?}` (`entities.show`),
  `GET .../{entity}/edit` (`entities.edit`), `PUT .../{entity}` (`entities.update`). The `edit` route is
  declared before the `{page?}` one. The controller answers 404 when the record is not of the type in the URL.
- **Rationale**: mirrors `contacts.show` with its optional page segment; the type in the path keeps the
  sidebar and breadcrumb on the right type. `Entity` already binds by `uuid` through `ResolvesModelInVault`.
- **Alternatives**: `?page=` query string (code style forbids it without reason).

## R2. Drawing values

- **Decision**: reuse `x-detail-list`, `x-detail-list.row` and `x-field-value` exactly as
  `contacts/_section.blade.php` reads a section. Repeated fields pass all their rows to `x-field-value`
  (no Load more: a record of an invented type holds few rows). Values formatted by `FieldValueFormatter`.
- **Rationale**: FR-006 and principle IX: accessibility lives in the field type, not in the screen.
- **Alternatives**: a second value renderer (two copies drift); `ContactFieldRowsViewModel` (tied to Contact).

## R3. "Not recorded"

- **Decision**: an empty, non-repeated field renders `__('Not recorded')` in `text-placeholder` inside its
  row, decided in the view model (`value === null && rows === []`).

## R4. Editing

- **Decision**: new action `UpdateEntity(user, entity, name, values, labels, labelWordings, yearIsUnknown,
  monthIsUnknown, keptFieldIds = [])`. It sanitises and requires the name, checks account and vault, then
  calls `SetFieldValues` once per section of the record's own `structureVersion`, passing `keptFieldIds`.
  Logs `UserActionEnum::EntityUpdate` (new case `entity_updated`).
- **Rationale**: `SetFieldValues` already accepts `Entity` and owns every value rule; `CreateEntity` and the
  contact section API use it. The record stays on its version (principle VI).
- **Alternatives**: changing the version on edit (a migration of the record; out of scope).
- The edit form reuses `entities/form.blade.php` and `EntityFormViewModel`, extended with an optional
  `Entity`: prefilled from its values, action and method switched to `PUT`, title "Edit :name". No
  `structure_version_id` check on update: the version is the record's own.
- No policy: editing is refused only by scoping (account, vault), which `.claude/rules/policies.md` keeps
  out of policies. A turned off type still allows edits (spec US2.5).

## R5. API

- **Decision**: `GET|QUERY vaults/{vault}/entities/{entity}` and `PUT vaults/{vault}/entities/{entity}`
  on `Api\EntityController` (`show`, `update`). `EntityResource` gains `fields`, shaped as
  `LifeEventResource::fields` (field_id, key, label, type, row, value, is_hidden), sensitive values hidden
  without the sensitive scope, and `links.web`. Update: fields left out are kept (`keptFieldIds`), as
  `Api\ContactSectionController::update` does.
- **Rationale**: one value format across the API; update semantics required by `.claude/rules/mcp.md`.

## R6. MCP

- **Decision**: `ListEntities`, `CreateEntity`, `GetEntity`, `UpdateEntity` under `app/Mcp/Tools`, each
  `MirrorsAnApiCapability` with `$mirrors` naming its route; `MirrorsAnApiCapability` gains `entityType()`
  and `entity()` lookups. Registered in the server like the other tools.

## R7. Documentation

- **Decision**: new `resources/docs/api/42-entities.php` (four endpoints), an entry in
  `resources/docs/api-changelog/2026-11-19.php`, and in `docs/portal`: update `3-people/6-profiles.md`
  ("What you can do with a profile today": open, read, edit) and remove the "no page of its own yet"
  sentence from `9-templates-and-customisation/9-profile-types.md`. The product word is "profile".
