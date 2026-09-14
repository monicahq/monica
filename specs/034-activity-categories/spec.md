# Feature Specification: Activity category endpoints

**Feature Branch**: `2026-09-13-activities` | **Created**: 2026-09-13 | **Status**: Draft

**Input**: "implement activity type categories models + actions to manage them + api methods + docs, based on spec/models/028-activity-category-not-yet.md, no UI, only the backend needed for the future."

## Overview

An activity category is the group an account files its activity natures under: Relationships and family, Work and education, Health and wellbeing, Travel and experiences. It organises the vocabulary in settings and pickers, filters the journal and gives aggregate statistics a level to count at. It never decides whether an activity counts as an interaction, which fields it carries, or how it is shared. The rules are [spec/models/028](../../spec/models/028-activity-category-not-yet.md) and [spec.md §9.3](../../spec/spec.md).

This is the record, the operations on it and its API, and nothing a person can click. Activity natures do not exist yet, so the groups start empty and everything that counts what a group holds reads zero until they do.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Read the groups an account starts with (Priority: P1)

Every account is given eight groups when it is created. A caller has to be able to ask which groups an account holds, in the order they are offered, and read one of them by its identifier.

**Why this priority**: It is the story that hands over an identifier, so every other one depends on it, and an account that is given a usable set at creation is already worth reading. **Independent test**: create an account, list its groups, and confirm the eight supplied ones come back in order, worded in the language asked for, each marked as supplied and holding nothing.

**Acceptance Scenarios**:

1. **Given** a newly created account, **When** the caller lists its groups, **Then** the eight supplied ones come back, Relationships and family, Work and education, Health and wellbeing, Home and daily life, Leisure and culture, Travel and experiences, Personal and Other, each in its recorded position.
2. **Given** a caller holding a valid credential, **When** they list the groups, **Then** each carries its identifier, its name, its place in the order, whether the product supplied it, whether it is still offered, how many natures it holds, when it was created and last changed, and a link to itself.
3. **Given** a supplied group nobody has renamed, **When** the caller reads it, **Then** its name comes back in the language the caller asks for, and a group somebody has renamed comes back in their words in every language.
4. **Given** groups that are no longer offered, **When** the caller lists them, **Then** they come back among the rest rather than being left out.
5. **Given** a group belonging to another account, or one that was deleted, **When** the caller asks for it by identifier, **Then** the answer is not found and nothing about it is disclosed.

---

### User Story 2 - Add a group, rename it, remove it (Priority: P2)

An account that thinks in groups the product did not supply has to be able to add one, and any group has to be renameable, because the words belong to whoever is reading them. A group added by mistake has to be removable.

**Why this priority**: It is what makes the vocabulary the account's own rather than the product's, and it is what an import needs. **Independent test**: add a group, rename it, rename a supplied one, then delete the added one, and confirm a supplied group refuses deletion.

**Acceptance Scenarios**:

1. **Given** a caller supplying a name, **When** they add the group, **Then** it exists, sits last in the list, holds nothing, may be chosen, is not marked as supplied, and the answer describes it.
2. **Given** a caller sending a name that is empty or only spaces, **When** the request is answered, **Then** it is refused as invalid and nothing is added.
3. **Given** a caller renaming a group, supplied or not, **When** the change is made, **Then** the name becomes what was sent and the identifier, the place in the order, whether it is offered and everything filed under it are untouched.
4. **Given** a caller renaming a supplied group, **When** the answer comes back, **Then** the group stays marked as supplied and keeps the stable identity the product gave it, so what the product does with it is unchanged.
5. **Given** a caller deleting a group they added that holds no nature, **When** the request is answered, **Then** it is gone from the lists and the deletion is recoverable.
6. **Given** a caller deleting a group the product supplied, **When** the request is answered, **Then** it is refused, the answer names that reason and points at stopping the group being offered instead, and nothing is deleted.
7. **Given** a caller deleting a group that still holds natures, **When** the request is answered, **Then** it is refused, the answer says how many it holds and asks for them to be moved first, and nothing is deleted.

---

### User Story 3 - Decide what is offered and in which order (Priority: P3)

Two decisions about a group are not corrections to its wording: whether it may still be chosen when an activity is recorded, and where it sits. Each is a gesture of its own and neither may be made by accident while renaming.

**Why this priority**: A group is usable without either, so they come last, and they are what let an account retire a whole area of its vocabulary without losing what was written under it. **Independent test**: stop a group being offered and offer it again, then move it up the order, and confirm every group is renumbered from one and nothing filed under any of them is touched.

**Acceptance Scenarios**:

1. **Given** a caller who sends the state they want rather than asking for a toggle, **When** the request is answered, **Then** the group ends in that state, and sending the same state twice confirms it rather than inverting it.
2. **Given** a group the product supplied, **When** the caller stops it being offered, **Then** it is allowed, because being supplied governs deletion and never availability.
3. **Given** a group that is no longer offered, **When** it is offered again, **Then** it returns to the place it always had rather than to the end.
4. **Given** a caller who moves a group in the list, **When** the request is answered, **Then** it sits there, every group of the account is numbered from one again with the ones no longer offered counted among them, and a place past the end puts the group last.
5. **Given** a caller who names a group of another account in any of these requests, **When** the answer comes back, **Then** it is not found and nothing is changed.

---

### Edge Cases

- A group is renamed and then given back the exact words the product supplied. It is the account's wording from then on, and the supplied wording does not come back, which is the same bargain every renameable supplied record makes here.
- Every group of an account is stopped. The list still reads, and nothing is offered when an activity is recorded.
- A group is deleted between a caller reading the list and asking for the deletion. The second request answers not found rather than reporting success twice.
- An account is left with no group of its own because all eight supplied ones were stopped. That is a valid state: stopping is not deleting, and each can be offered again.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The product MUST give every account eight activity categories when it is created, Relationships and family, Work and education, Health and wellbeing, Home and daily life, Leisure and culture, Travel and experiences, Personal and Other, each in a defined order, marked as supplied, offered, and carrying the stable identity the product gives it.
- **FR-002**: A supplied group's name MUST be held as the product's wording and read in the language of whoever is asking, until somebody renames it, after which their words stand in every language.
- **FR-003**: The API MUST offer a way to list the groups of the caller's account in their recorded order, with the ones no longer offered included.
- **FR-004**: Each group the API describes MUST carry its identifier, its name, its place in the order, whether the product supplied it, whether it may still be chosen, how many activity natures it holds, when it was created, when it was last changed, and a link to itself.
- **FR-005**: The API MUST offer a way to read one group of the caller's by identifier, described exactly as the list describes it.
- **FR-006**: The API MUST offer a way to add a group from a name, placing it last, offered, holding nothing, and not marked as supplied.
- **FR-007**: The API MUST refuse a name that is empty, only spaces, or longer than the record allows.
- **FR-008**: The API MUST offer a way to rename any group, supplied or not, leaving its identifier, its stable identity, its place in the order, whether it is offered and everything filed under it untouched.
- **FR-009**: The API MUST offer a way to delete a group that the product did not supply and that holds no activity nature, recoverably.
- **FR-010**: The API MUST refuse to delete a group the product supplied, and MUST refuse to delete one that still holds activity natures, naming that reason in each case and pointing at stopping the group being offered instead.
- **FR-011**: The API MUST offer a way to say whether a group may still be chosen when an activity is recorded, as a state that is sent rather than a toggle, at its own address, for a supplied group as readily as for any other.
- **FR-012**: The API MUST offer a way to move a group in the list, at its own address, renumbering every group of the account from one with the ones no longer offered counted, and placing a group asked for a place past the end last.
- **FR-013**: A group MUST NOT give an activity any behaviour. Naming a group, moving it or stopping it MUST change nothing about what is filed under it.
- **FR-014**: Every request here MUST answer not found for a group outside the caller's account, MUST disclose nothing about it, and MUST be recorded in the account's journal the way every other account-level change is recorded.
- **FR-015**: The public API reference MUST document every one of these endpoints, in the order list, read, add, rename, delete, then the two decisions, with the same worked request and answer the rest of the reference gives.
- **FR-016**: Where a rule turns on how many activity natures a group holds, that number MUST be read from the activity nature record. This feature does not build that record, so the number is zero for every group until it is built, and the half of FR-010 that turns on it MUST be written so that it starts refusing as soon as the record exists, with no change to anything here.

### Key Entities

- **Activity category**: an account-owned group for activity natures, carrying a name, a place in the order, whether the product supplied it, whether it is still offered, and the stable identity a supplied one keeps. It groups, filters and counts, and decides nothing else.
- **Activity nature**: the kind of thing an activity is. Filed under exactly one group, counted by it, and not built by this feature.
- **Account**: owns its groups and is given the eight supplied ones when it is created.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A newly created account answers with eight usable groups, with no setup step of any kind.
- **SC-002**: A caller can shape an account's whole set of groups, named, ordered and offered as they want them, without opening a browser, which is the only way to do it since no screen exists.
- **SC-003**: No request through the API can destroy a group that something is filed under. Every path that would is refused, names the alternative, and is covered by a test.
- **SC-004**: A request naming a group of another account discloses nothing, on every endpoint, and each of those refusals is covered by a test.
- **SC-005**: Every endpoint appears in the public API reference, and the reference and the routes agree in both directions, so neither can drift from the other unnoticed.
- **SC-006**: When the activity nature record is built, it can be filed under these groups and counted by them with no change to anything this feature ships.

## Assumptions

- Renaming a supplied group is allowed, which is what [spec.md §9.3](../../spec/spec.md) says in as many words: the user may create, rename, reorder and deactivate categories, and supplied categories cannot be deleted. Being supplied governs deletion and nothing else.
- The shipped relationship categories refuse a rename when they are supplied, which contradicts the same sentence in §8.2 and is a defect in the code rather than in the specification. It is named here because the two records are siblings and the difference will read as an inconsistency; putting it right is its own change and is not made here.
- Groups are addressed by uuid, as vaults, contacts, entity types and relationship categories are, and the list is paginated the way every other collection endpoint is.
- Whether a group may be chosen, and where it sits, are each sent at their own address and never as part of a rename, matching how entity types and relationship categories already route those gestures.
- Deleting is soft, as everywhere else in the application, so an accident is recoverable.
- The eight groups are given to an account when it is created. The product is not in production, so no older account is backfilled.
- Nothing a person can look at is built. The settings screen this record will eventually need is a separate feature, and so are activity natures, activities and the journal.
- Stopping a group has no visible consequence yet, because nothing offers activity natures. The state is recorded now so that the picker honours it the day it exists.
