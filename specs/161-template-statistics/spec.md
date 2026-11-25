# Feature Specification: Statistics per kind of record, and changing the template of many records

**Feature Branch**: `2026-11-25-template-statistics`

**Created**: 2026-11-25

**Status**: Draft

**Input**: User description: the French brief "Statistiques par type d’objet" (both deliveries), the design `Animals Statistics Wireframes.dc.html` (read on 2026-11-25, its flow wins where the two differ), "the follow up of #8014: change the template of many records of a vault at once, which has to be extremely fast".

## Context

#8014 (`specs/159-contact-template-change`) changes the template of one contact and prepared the unit a bulk change reuses (FR-019 to FR-022 there). This feature adds, for every kind of record in a vault (People, Animals, any custom type), a Statistics page saying how many records use which template and version and which fields are filled, and from it a three step flow that moves every record of one template, or of its older versions, at once. Animals is the example throughout; screens use the type's own singular and plural names.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - See how a kind of record is described (Priority: P1)

On the Animals list of the Family vault, the reader opens Statistics and sees: 18 animals, 2 templates in use, 3 on older versions, 2 archived; a Templates table where Pet (Default) holds 14 · 78% (v3 · 11 records, v2 · 3 records), Horse 4 · 22% (v1 · 4 records) and Bird 0; and, for Pet, which fields are filled (Microchip number 5 of 11 · 45%, available from v3).

**Why this priority**: the first delivery, and the place the bulk change starts from.

**Independent Test**: seed the Animals example above, open the page, compare every figure.

**Acceptance Scenarios**:

1. **Given** the Animals list, **When** the reader opens Statistics (beside Add animal, or in the header menu on a narrow screen), **Then** a page titled "Animals statistics" opens, with the trail Family › Animals › Statistics and the scope line "Family vault · Non-archived animals".
2. **Given** a search, a filter or a page chosen on the list, **When** Statistics opens, **Then** its figures still cover the whole type in the vault.
3. **Given** a version link "v2 · 3 records" or "View 14 animals", **When** followed, **Then** the list opens filtered on that template (and version), shows the filters, and each can be removed or combined with the list's own.
4. **Given** the Field completion section, **When** the reader picks a template, a version (default "All versions") and the order ("Lowest completion first" or "Form order"), **Then** fields are grouped by section, each showing filled records, the denominator and the percentage on one line.
5. **Given** no record of the type in the vault, **When** the page opens, **Then** it shows "No animals yet", the Add animal action, every offered template at 0, and "No records use this template yet." under Field completion.

---

### User Story 2 - Move every record of one template to another (Priority: P1)

The reader opens the Horse row's menu, picks "Change template…", keeps all 4 horses, picks Pet · v3, reads that 2 horses have values set aside ("Stable name · 2 animals") and that 1 reminder ("Farrier visit") pauses, and confirms "Move 4 animals". The result says "3 animals moved · 1 skipped", with Bijou skipped because "This animal’s template changed after the preview."

**Why this priority**: the second delivery and the reason the feature exists.

**Independent Test**: on the seeded example, run the flow end to end and compare the preview with what the records hold afterwards.

**Acceptance Scenarios**:

1. **Given** step 1 "Select animals", **When** it opens from a row, **Then** it shows "Horse · 4 animals · Family vault", the batch as every non-archived record on that template, a version filter updating the count, a link to view those records, and the other active templates of the type that have a published version, with that version.
2. **Given** step 2 "Preview", **When** it opens, **Then** it shows "Horse → Pet v3", the six counts of the brief (ready to move, unchanged, values set aside, values restored, reminders paused, reminders resumed), the plain sentence on set aside values, a disclosure of the effects by field and by record, and "Move N animals" where N is the count ready to move.
3. **Given** records that cannot move, **When** the preview shows, **Then** a warning lists each with its reason and says how many remain in the batch.
4. **Given** step 3 "Result", **When** the move ends, **Then** it shows moved, skipped and failed counts with a reason per record, "View affected animals" and "Back to statistics", and the statistics count only the records that moved.
5. **Given** the Pet row with records on v2, **When** the reader picks "Update to v3…", **Then** step 1 opens with Pet v3 locked as the destination and the older versions selected, and steps 2 and 3 are the same.

---

### User Story 3 - Do the same through the API and the MCP server (Priority: P2)

A script reads the statistics of a type in a vault, previews a bulk change, starts it, and reads its result; an assistant does the same through matching tools, previewing and showing the result to the person before moving anything.

**Why this priority**: constitution principle X.

**Independent Test**: call each endpoint and its tool for the seeded example and compare with the screens.

**Acceptance Scenarios**:

1. **Given** the same request, **When** sent through the interface, the API and the MCP server, **Then** the figures, the preview, the result and the refusal wording are the same.
2. **Given** a type, template or vault of another account, **When** requested, **Then** it does not exist.

### Edge Cases

- A template turned off that still holds records shows "Inactive"; one without records shows 0, with no link and no menu.
- No other template to move to: "Change template…" is absent; "Update to v3…" stays when records sit on an older version.
- A record moved, archived or deleted by somebody else between preview and confirmation is skipped with its reason; the others still move.
- A field no record's version offers shows no rate; a field offered but never filled shows "0 of N · 0%".
- The same batch confirmed twice moves each record once.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Every list of records in a vault (People, Animals, custom types) MUST offer Statistics as a secondary action beside its add action, and inside the header menu, with its label, when the actions do not fit on one line.
- **FR-002**: The page MUST cover every non-archived record of the type in the vault, whatever the list's search, filters or page, and MUST state that scope under its title.
- **FR-003**: Overview MUST show the number of records, the number of templates in use, the number on a version other than their template's published one (hidden at zero), and the number archived, which no other figure counts.
- **FR-004**: For People, partial contacts MUST count in the total, MUST be shown in their own figure, and MUST be left out of field completion.
- **FR-005**: Templates MUST list one row per template of the type, offered or still holding records, with its count, its share, each version in use with its count, the Default mark for the vault's default, and the Inactive mark for a turned off template.
- **FR-006**: Each count MUST link to the list filtered on that template, or template and version; the list MUST show those filters as removable and combinable, and the entity list MUST show each record's template and version.
- **FR-007**: Field completion MUST count, per field, the records holding at least one value against the records whose version offers the field, grouped by section, for one template and optionally one version, sorted by lowest completion or form order.
- **FR-008**: Set aside values MUST NOT count as filled, and a sensitive field MUST follow the same visibility rules as on the records.
- **FR-009**: Each row with records MUST offer "Change template…" when another active template of the type has a published version, and "Update to vN…" when some of its records sit on an older version than the published one.
- **FR-010**: A batch MUST be every non-archived record of the source template in the vault, narrowed only by version, never record by record.
- **FR-011**: The preview MUST give the six counts of User Story 2, the effects per field with their record counts, and the blocked records with their reasons, from the same matching rule the move applies, without changing anything.
- **FR-012**: A sensitive field the reader may not see MUST be counted in the preview, never named.
- **FR-013**: Confirming MUST move only records still on the version the preview saw; any other MUST be skipped with its reason.
- **FR-014**: Each record MUST move as the single-record change of #8014 does: snapshot first, values set aside and never deleted, reminders paused and resumed, revertible from its version history; a failure on one record MUST neither undo nor stop the others.
- **FR-015**: A batch MUST write one entry in the account's log for the whole decision, not one per record.
- **FR-016**: The result MUST give moved, skipped and failed counts, each skipped or failed record with its reason, and links back to the statistics and to the records it moved (the list filtered on the change, removable like the other filters; skipped and failed records stay listed on the result).
- **FR-017**: The API MUST offer the statistics, the preview, the change and its result, and the MCP server MUST mirror each through the API, the preview and the statistics declared as only reading.
- **FR-018**: The help centre, the API reference, the MCP tools list and What's new MUST describe the feature; every string MUST be translated; every screen MUST meet WCAG 2.2 AA on desktop and at 375 px without horizontal scrolling.

### Key Entities

- **Kind of record**: People, or an entity type such as Animals, with its singular and plural names.
- **Template and version**: what a record follows; a record points at one version.
- **Batch**: one template change decided once for many records, with its source, its destination, its preview and its result per record.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: The Statistics page of a type holding 10,000 records in a vault opens in under one second.
- **SC-002**: A batch of 10,000 records shows its preview in under 5 seconds and finishes moving in under 2 minutes, without the reader having to keep the page open.
- **SC-003**: What the preview announces equals what the records hold after the move, for every record that moved.
- **SC-004**: No value is deleted: the values each record holds, shown or set aside, are the same in number before and after.
- **SC-005**: Every figure on the page equals a direct count of the records, immediately after a batch ends.
- **SC-006**: A reader moves every record of one template to another in at most five actions from the list.

## Assumptions

- The design's flow wins over the brief: no record-by-record selection or exclusion (a type may hold thousands of records), a step indicator Select · Preview · Result, a "Form order" sort, and hidden sensitive fields counted rather than named.
- A batch runs in the background; step 3 shows its progress, then its result, and can be reopened later.
- Partial contacts move with the batch like any other contact; archived records never do.
- Anybody who can reach the vault may run a batch: an account carries no role.
- Moving across vaults, between kinds of record, or choosing a destination version other than the published one is out of scope.
