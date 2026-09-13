# Research: Manage relationships

Decisions behind [plan.md](plan.md). One line of rationale each; the requirement itself is in [spec.md](spec.md).

## D1 — The page is a fourth reserved tab key, not a route of its own

**Decision**: `contacts.show` already takes an optional `{page?}`. `relationships` joins `overview` and `version-history` as a reserved key that resolves to no template page.

**Rationale**: one route, one controller, one view model, and the tab bar keeps working with nothing added to it. A new route would need its own breadcrumb, shell and tab state.

**Rejected**: a `contacts.relationships.index` route. It would draw the same header twice and split where "which tab am I on" is decided.

**Risk**: a template page whose key is literally `relationships` would be shadowed. `version-history` already carries that risk and nothing guards it; out of scope to fix here, noted.

## D2 — Selection lives in a `Relationship` scope, shaping in a view model

**Decision**: `scopeForContact` on `Relationship` returns every undeleted row touching a contact from either side, eager-loading the type, its category and both contacts. A `RelationshipsBlock` object beside `ContactViewModel` groups and shapes it.

**Rationale**: [030](../030-immediate-family-widget/research.md) put its selection in scopes so an API controller could reuse it unchanged, and D8 below leans on the same thing. Both sides of the table are already indexed on `(vault_id, contact_*_id, relationship_type_id, deleted_at)`, so the union is two index reads.

**Rejected**: a `relationships()` Eloquent relation on `Contact`. Eloquent cannot express "either of two foreign keys" as one relation without a union, and the scope is what the API needs anyway.

## D3 — Grouping is by category, ordered by the account

**Decision**: group by `relationship_category`, ordered by `position`, as the user chose. The mock's four buckets are not built.

**Rationale**: the category already exists, is already ordered in settings, and is already what the picker groups by. A hard-coded bucket would be a second, invisible classification that settings could not reach.

## D4 — A row names the role, which the mock omits

**Decision**: each row shows how the link reads from this contact's side, its `role_a` or `role_b` depending on which end the contact sits at, in addition to the name, avatar and age the mock draws.

**Rationale**: the mock groups by family and friendship alone, so a father and a brother are drawn identically under Family. The page would lose the one fact the relationship carries. This is the one place the implementation departs from the design.

## D5 — Manage is per group, in Alpine, and holds nothing on the server

**Decision**: `x-data="{ manage: false }"` on each group's panel, toggled by the chip, with `x-show` on the row controls. No request, no session, no preference.

**Rationale**: it hides controls on a page already rendered. Nothing about it needs to survive a reload, and the mock's own state is local.

**Accessibility**: the chip carries `aria-pressed`, the controls it reveals are in the DOM order they are read in, and hiding uses `x-show` so focus never lands on something invisible.

## D6 — Two actions, no more

**Decision**: `UpdateRelationship` and `DestroyRelationship` under `app/Actions`, taking promoted readonly inputs and exposing `execute()`, beside the existing `CreateRelationship`.

**Rationale**: Principle I. `UpdateRelationship` reuses `CreateRelationship`'s ancestry walk, which is why that walk moves to a shared place rather than being copied.

**Journal**: two new `UserActionEnum` cases, `RelationshipUpdate` and `RelationshipDeletion`, beside `RelationshipCreation`.

## D7 — The type picker becomes a shared object

**Decision**: the ~45 lines of `typeGroups()` / `typeMap()` move out of `RelationshipFormViewModel` into `RelationshipTypeChoices`, which both the add form and the edit form read. It takes one optional type to keep even when deactivated.

**Rationale**: reused in two places, non-trivial, and a concept worth naming, which is the bar `.claude/CLAUDE.md` sets. The optional keep is FR-024: a link keeps reading a nature the account has withdrawn.

**Rejected**: giving `RelationshipFormViewModel` a nullable relationship. Half its surface is candidate search and the three ways of naming the other person, none of which an edit uses, because FR-026 freezes who the link joins.

## D8 — Status is written for the first time, and is already read

**Decision**: nothing is added to the table. `status`, `started_on` and `ended_on` exist and are cast; this feature is the first thing to write them.

**Rationale**: `Contact::scopeCurrentPartnersOf` already filters `status = current`, so FR-022 costs nothing: marking a partner ended drops them from the Immediate family panel with no change there. Ordering `ended_on` after `started_on` is validated in the action, not the database.

## D9 — No API this feature

**Decision**: no endpoint, no resource, no `routes/api.php` entry.

**Rationale**: the approved spec says nothing about the API, and widening it here is not mine to do. The rules sit in actions and a scope an API controller can call unchanged.

**This is the second deferral.** [029](../029-add-relationship-form/spec.md) deferred create, [030](../030-immediate-family-widget/plan.md) deferred read, and Principle X names "managing relationships" explicitly. The debt is now four operations. It is raised in the completion report rather than settled quietly here.
