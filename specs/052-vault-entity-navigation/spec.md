# Feature Specification: Entity types in the vault navigation

**Feature Branch**: `2026-09-25-vault-entity-navigation` | **Created**: 2026-09-25 | **Status**: Draft

**Input**: "Ajouter les types d’entités personnalisés dans la sidebar du vault, directement sous
People, au même niveau." (full brief in French in the conversation of 2026-09-25)

## Context

A vault's navigation lists People and nothing for the entity types the account made (Animals,
Companies). Records of those types can exist, through the supplied Basic animal template and the
data model, but no screen reaches them. This feature adds one navigation entry per type and a
read-only list of that type's records in the current vault.

**Scope, decided 2026-09-25**: navigation and the list only. Creating, viewing, editing,
archiving, restoring, deleting and duplicating a record are not built here; their rules for a
turned-off type are recorded under Assumptions for the feature that builds them.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Reach a type from the vault (Priority: P1)

In the Schrute Farms vault, Dwight sees **Animals** right under **People**, with the icon and
colour the type was given, and opens it to see the animals of that vault only.

**Independent Test**: an account with two types in two vaults; open each vault and check entries,
order, labels, the highlighted entry and the records listed.

**Acceptance Scenarios**:

1. **Given** active types with a usable default template in the vault, **Then** one entry per type
   sits directly under People, in the order set on Contacts and more, labelled with its plural name,
   icon and colour.
2. **Given** Dwight opens an entry, **Then** the list shows that type's records of the current vault
   only, and the entry is marked as the current page.
3. **Given** a record of the same type in another vault, **Then** it is never listed here.
4. **Given** an archived record, **Then** it is listed and marked as archived.

---

### User Story 2 - An entry appears only when it leads somewhere (Priority: P1)

**Acceptance Scenarios**:

1. **Given** an active type whose default template for this vault is active and published,
   **Then** its entry shows, even with no record.
2. **Given** a type with at least one record in this vault that is not deleted, **Then** its entry
   shows, even when the type is turned off or has no usable template.
3. **Given** neither, **Then** the entry is hidden, and opening its address answers not found.
4. **Given** the last record is archived, **Then** the entry stays.
5. **Given** a turned-off type whose last record in this vault is deleted, **Then** the entry goes.
6. **Given** two vaults, **Then** each decides its entries from its own defaults and records.

---

### User Story 3 - A turned-off type says so (Priority: P2)

**Acceptance Scenarios**:

1. **Given** a turned-off type with records, **When** Dwight opens its list, **Then** it reads:
   "This type is turned off. You can view and edit the existing profiles, but you cannot add new
   ones." (French: "Ce type est désactivé. Vous pouvez consulter et modifier les fiches existantes,
   mais vous ne pouvez pas en ajouter.")
2. **Given** turning the type off or on, **Then** no record and no template changes.

---

### User Story 4 - An empty list (Priority: P3)

**Acceptance Scenarios**:

1. **Given** a type with no record in this vault, **Then** the list shows an empty state naming the
   type ("No Animals in this vault yet"), and offers no way to add one, since adding is not built.

### Edge Cases

- A type deleted from settings disappears from every vault.
- A type with a long plural name truncates in the navigation without pushing its neighbours.
- Nothing in the navigation or the list names Animal or Company: every type is handled the same way.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The vault navigation MUST list the visible entity types directly under People, in
  their settings order, each with its plural name, icon and colour.
- **FR-002**: A type MUST be visible in a vault when it is active and the vault's default template
  for it is active and published, or when the vault holds a non-deleted record of it (archived
  included); otherwise it MUST be hidden.
- **FR-003**: Each entry MUST open the list of that type's records in the current vault, and the
  list MUST answer not found for a type hidden in that vault or belonging to another account.
- **FR-004**: The list MUST show every non-deleted record of the type in the vault, archived ones
  marked, and nothing from another vault.
- **FR-005**: The list of a turned-off type MUST show the notice of US3.
- **FR-006**: An empty list MUST show an empty state naming the type.
- **FR-007**: No rule may depend on a particular type.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Every type that has a usable template or records in a vault is reachable from that
  vault in one click.
- **SC-002**: No list ever shows a record from another vault or account.
- **SC-003**: Every new string is translated in every configured locale.

## Assumptions

- The list is read only: a row shows the record's name, avatar and archived mark, and is not a
  link, since no record screen exists yet.
- For the feature that builds them: a turned-off type keeps viewing, editing, archiving, restoring
  and deleting its records; creating and duplicating are hidden and refused on the server;
  turning it back on restores creation when a compatible, active, published template exists.
- The notice promises editing, as the brief asks, although editing ships with the next feature.
- Ordering records in the list follows the contact list: by sort name.
