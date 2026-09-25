# Research: Entity types in the vault navigation

## R1. One rule, asked in two places

**Decision**: a local scope `EntityType::scopeVisibleInVault(Builder $query, Vault $vault)`. The
sidebar lists what it returns; the list controller answers not found for a type it does not return.

The scope keeps a type when either holds:

- `is_active`, and a `vault_default_templates` row for this vault, `applies_to = entity`, this
  `entity_type_id`, whose structure version is `Published` and whose template is active;
- an `entities` row in this vault of this type, not soft deleted (archived rows count).

**Rejected**: a policy (the rule is a visibility rule, not an ability somebody exercises; policies.md
keeps scoping out of policies); computing it in a view model (the controller would then read a view
model, which the constitution forbids).

## R2. Where the entries are computed

**Decision**: `App\View\Components\Vault\Sidebar::render()` passes `entityTypes` next to
`reminderCount`, from the scope ordered by `position`. Every one of the 20 screens that draws the
sidebar gets the entries without a change. One query per page.

## R3. The address and the current entry

**Decision**: `GET vaults/{vault}/entities/{entityType}` named `entities.index`, bound by the type's
slug (`ResolvesModelInAccount`), `EntityController::index`. The page passes
`current="entity-type-{slug}"` and the sidebar marks that entry.

## R4. The list

**Decision**: `EntitiesViewModel` gives the type (plural name, icon, colour, `isActive`) and the
records: name, avatar or initials, `isArchived`, ordered by `sort_name`, all of them (no paging:
nothing creates records in bulk yet). Rows are not links (spec Assumptions).

## R5. Copy

- Empty: `__('No :name in this vault yet', ['name' => $plural])`, shown through `x-empty-state`.
- Turned off: "This type is turned off. You can view and edit the existing profiles, but you cannot
  add new ones." French value as the brief gives it.
- Archived mark: the contact list's existing "Archived" badge wording.

## R6. The API

No endpoint lists entities today. Constitution X asks for one for every capability of the
interface; it is deferred to the feature that builds the record lifecycle, where the list, create,
update, archive and delete endpoints arrive together. Named in the pull request as a deviation.
