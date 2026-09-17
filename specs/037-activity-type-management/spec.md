# Feature Specification: Activity type management

**Feature Branch**: `2026-09-16-activity-type-management` | **Created**: 2026-09-16 | **Status**: Draft

**Input**: "implement management of activity types and categories following this design [Activity types.dc.html]. add a category should be done in a modal. add a type should be done in a dedicated screen. we should reuse the notion of fields and structure introduced in the last pr, especially when using the Add field button in the add type screen."

## Overview

An account already carries activity categories and types, their actions and the category API, and nothing in the interface reaches any of it. [Spec 035](../035-generic-structure-engine/spec.md) shipped the engine and deferred these screens. Until they exist nobody can name a category, nobody can create a type, and the count a category reports is zero by construction, which leaves the rule that a category holding types cannot be deleted unable to refuse anything. The rules are [spec/models/035](../../spec/models/035-activity-type-in-progress.md), [spec/models/028](../../spec/models/028-activity-category-done.md) and [spec.md §9.3](../../spec/spec.md). Ships here: the Activity types screen, category management in place, the type form on its own screen, the custom fields of a type through the engine templates already use, and a category count that counts. Deferred, each so the later feature stays honest: the ActivityType API, which follows separately the way the relationship type API followed its own screens; the 40 supplied types, so an account starts empty and fills the list itself; search across categories; and the activity record, without which no usage count can be measured.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Read the vocabulary (Priority: P1)

Somebody opens Activity types in account settings and sees every category their account holds, how many types each one carries, and the types of whichever category they pick, in the order the pickers will offer them.

**Why this priority**: every other story is reached from this screen, and it alone replaces a blank corner of settings with the account's own vocabulary. **Independent test**: seed categories and types, open the screen, and confirm the rail, the counts, the selected category's list and the marks on whatever is switched off.

**Acceptance Scenarios**:

1. **Given** an account with categories and types, **When** the screen opens, **Then** the categories stand in position order, each showing how many types it holds, and the selected one's types are listed in their own order.
2. **Given** the entry that lists every type, **When** it is picked, **Then** every type of the account appears, grouped in category order and then in type order.
3. **Given** a category holding nothing, **When** it is picked, **Then** the panel says so and offers to add a type there.
4. **Given** a type or a category that is switched off, **When** it is listed, **Then** it is shown rather than hidden, and marked as not offered.
5. **Given** a category or a type of another account, **When** it is reached by its URL, **Then** the answer is not found, and a list shows nothing of that account.

### User Story 2 - Add and edit a type (Priority: P1)

Somebody adds an activity type on a screen of its own, giving it a name, a category, an optional description, an optional icon and colour, and whether it is offered when an activity is recorded. The same screen edits a type that already exists, retires it, and deletes one that may be deleted.

**Why this priority**: the account starts with no type at all, so without this the list has nothing to show. **Independent test**: add a type, confirm it appears under its category, reopen it, move it to another category, switch it off, and delete it.

**Acceptance Scenarios**:

1. **Given** the add screen, **When** it is saved with a name and a category, **Then** the type is created last in that category and the list reopens showing it.
2. **Given** the add screen, **When** the name is missing, **Then** the screen says which field is wrong and nothing is created.
3. **Given** a type, **When** its category is changed, **Then** it leaves the old category's count and joins the new one's, and nothing already recorded changes.
4. **Given** a type, **When** it is switched off, **Then** it stays listed and marked as not offered, and the screen says it leaves the picker while the activities recorded with it keep their type, their icon and their fields.
5. **Given** a type the product supplied, **When** it is opened, **Then** deletion is refused with its reason and retiring it is offered instead, while a type the user made asks to confirm and says that it cannot be undone.

### User Story 3 - Manage the categories in place (Priority: P2)

Somebody adds a category through a modal on the list screen, renames one without leaving the screen, moves one up or down, switches one off, and deletes one they made that holds nothing.

**Why this priority**: eight categories cover most accounts, so this is asked for less often than a type, and it is still the half of the screen that gives the other half its shape. **Independent test**: add a category through the modal, rename it, move it, switch it off and on, then delete it, reading the list after each.

**Acceptance Scenarios**:

1. **Given** the list screen, **When** Add a category is chosen, **Then** a modal asks for a name and nothing else, and the category is added last and selected.
2. **Given** the modal, **When** the name is empty or only spaces, **Then** it is refused, the modal stays open carrying what was typed, and nothing is added.
3. **Given** any category, supplied or not, **When** it is renamed, **Then** every screen naming it reads the new words.
4. **Given** a category, **When** it is switched off, **Then** the screen first says how many of its types leave activity recording with it and that the activities already recorded stay exactly as they are.
5. **Given** a category still holding types, or one the product supplied, **When** deletion is asked for, **Then** it is refused, and the screen says to move the types elsewhere or to switch the category off instead.

### User Story 4 - Give a type its custom fields (Priority: P2)

From the type's screen somebody adds the fields an activity of that type asks for, through the same builder a template uses, and never reads the words template, structure or version while doing it.

**Why this priority**: a type is useful before it has fields, so this follows the type form rather than blocking it, and it is the part the design calls Add field. **Independent test**: add a field to a type, publish it, and confirm the type reports its fields while what was published before has not moved.

**Acceptance Scenarios**:

1. **Given** a type carrying no field, **When** its screen is read, **Then** it says so and offers to add them.
2. **Given** the add screen of a type that does not exist yet, **When** its fields are asked for, **Then** the type is saved first and the builder opens on it.
3. **Given** a type whose fields are published, **When** one is changed, **Then** a draft is made and what is published does not move until the draft is published.
4. **Given** any screen this feature ships, **When** its words are read, **Then** template, structure and version appear in none of them.

### Edge Cases

- A type is moved into a category that is switched off: allowed, and the type then reads as not offered because its category is not.
- A category is switched off while one of its types is open: it stays choosable on that type and is marked as switched off.
- Two categories are given the same name: allowed, because a name is a label and the record is the identity.
- Somebody works with a keyboard alone: moving a category or a type up and down is offered as a control, never only as a drag.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Account settings MUST carry an Activity types screen, reached from the settings navigation next to Relationship types.
- **FR-002**: The screen MUST list every category in position order with the number of types it holds, plus an entry listing every type of the account.
- **FR-003**: The screen MUST list the selected category's types in position order, including the ones switched off, each marked as not offered.
- **FR-004**: Users MUST be able to add a category through a modal asking for its name alone, which refuses an empty name without losing what was typed.
- **FR-005**: Users MUST be able to rename any category, including one the product supplied.
- **FR-006**: Users MUST be able to move a category in the list and a type inside its category, through controls reachable without a pointer.
- **FR-007**: Users MUST be able to switch a category off and on again, and the screen MUST say, before switching off, how many types leave activity recording with it and that what is recorded is untouched.
- **FR-008**: The system MUST refuse to delete a category the product supplied and a category still holding a type, saying in each case what to do instead.
- **FR-009**: Users MUST be able to add an activity type on a screen of its own, requiring a name and a category, accepting an optional description, icon and colour, and drawing a type carrying neither icon nor colour neutrally rather than refusing it.
- **FR-010**: The type screen MUST edit an existing type, move it to another category, and mark a supplied type as supplied.
- **FR-011**: Users MUST be able to switch a type off, and the screen MUST say it leaves the picker while the activities recorded with it keep their type, their icon and their fields.
- **FR-012**: The system MUST refuse to delete a supplied type, and MUST ask for confirmation before deleting one the user made, saying that it cannot be undone.
- **FR-013**: The type screen MUST let somebody give the type its custom fields through the same engine and the same actions a template uses, and MUST save an unsaved type before opening that builder.
- **FR-014**: Every screen this feature ships MUST say "custom fields" and MUST NOT show the words template, structure or version.
- **FR-015**: The number of types a category holds MUST be counted from the types actually filed under it, so the number the screen shows and the number guarding deletion are one number.
- **FR-016**: Account isolation MUST hold on every screen and every write: a category or a type of another account answers not found.
- **FR-017**: Every string this feature adds MUST be translatable in every configured locale, and a phrase carrying a number MUST choose its wording by number rather than by comparison against one.

### Key Entities

- **Activity category**: the group the vocabulary is filed under. Carries a name, whether the product supplied it, whether it is active, and its position. It organises, and gives a type no behaviour.
- **Activity type**: the nature of an activity. Carries a name, one category, an optional description, icon and colour, whether the product supplied it, whether it is active, and its position inside its category.
- **Custom fields of a type**: the versioned field structure the type already owns, reached from the type's screen and called custom fields wherever a person can read it.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Somebody who has never seen the screen can add a category and file a new type under it without leaving the two screens this feature ships.
- **SC-002**: Every destructive or wide reaching action on these screens says what it changes and what it leaves alone before it runs, and none of them runs from a single unconfirmed press.
- **SC-003**: Both screens are fully operable with a keyboard alone, reordering included, and every control carries a name a screen reader announces.
- **SC-004**: The number a category shows and the number deciding whether it can be deleted are the same number in every case tested.
- **SC-005**: Template, structure and version appear in no string either screen can show, in any locale.

## Assumptions

- A type row carries no interaction setting and no interaction badge. The design file still holds logic for one and renders none of it, and the product specification settles it: whether a contact's history changes is decided by an activity's participants alone.
- No usage count is shown and no deletion is blocked on usage, because the activity record does not exist yet. The rule shipping here refuses a supplied type; the usage condition joins it with that record.
- No search is offered. The category rail and the entry listing every type are how a type is found.
- Both screens are specified against an empty list, because the account keeps no supplied type yet.
- The existing actions for categories and for creating and deleting a type are reused unchanged, the ones this feature adds being the ones no screen could call before it, and the icon and the colour offered are the ones the product already offers for the same purpose elsewhere rather than a second catalogue.
