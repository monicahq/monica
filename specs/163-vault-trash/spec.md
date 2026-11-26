# Feature Specification: Vault trash

**Feature Branch**: `2026-11-25-vault-trash` | **Created**: 2026-11-25 | **Status**: Draft

**Input**: "Je veux pouvoir gérer les objets qui sont soft delete dans un vault. Nouvel écran dans les settings d'un vault, sur un nouveau tab (voir mockup). Vérifie que tous les objets listés dans le design peuvent être soft delete dans l'interface. Ajoute dans MCP et l'API les méthodes pour restore les objets soft deleted." Design: `Vault Trash.dc.html`.

## Context found in the code (2026-11-25)

| Kind | Deletable on screen today | Set aside or erased | Restorable today |
| --- | --- | --- | --- |
| Person, group, relationship, life event, reminder, group membership, group note | Yes | Set aside | Group and life event only |
| Animal (entity) | No, nor through the API or MCP | (never deleted) | No |
| File on a group | Yes | The attachment is erased; the content waits 30 days as an orphan | No |

Deleting a person or a group takes nothing with it, and nothing ever erases what was set aside. A person absorbed by a merge is set aside too, so the merge can be undone at any time. An account holds one user, so the design's "deleted by" line has nobody to name.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - See what was deleted from a vault (Priority: P1)

Vault settings gain a Trash tab listing everything deleted from this vault in the last 30 days, newest first: what it was, where it belonged, when it was deleted and how long it still waits.

**Independent Test**: Delete a person and a reminder, open Vault settings, choose Trash, find both rows with "Today" and 30 days left.

1. **Given** deleted items, **Then** the settings show tabs "General" (today's panels) and "Trash", the latter carrying its item count, and the trail reads the vault, "Vault settings", "Trash".
2. **Then** each row shows title, kind and where it belonged ("Life event · About Janice Litman-Goralnik · 1999"), when it was deleted ("Today", "3 days ago", "2 weeks ago") and the days left.
3. **Given** three days or fewer left, **Then** the countdown is drawn as a warning; on hover or focus it names the date of final deletion.
4. **Given** a person deleted with their life events, **Then** "Everything" lists only the person, and the Show menu's "Life events" lists those life events, each saying it was deleted with that person.
5. The Show menu offers "Everything" and each kind present, with counts. The list is paged with a range label, Previous, page numbers and Next.
6. **Given** an empty trash, **Then** it reads "The trash is empty" and says items wait 30 days.
7. Items of another vault or account never appear.

### User Story 2 - Restore what was deleted (Priority: P1)

**Independent Test**: Delete a person with two life events and a reminder, restore the person, find all four back.

1. **Given** a deleted person who went with two life events and a relationship, **When** Restore is chosen, **Then** all four are back in lists and search, and a notice reads "Janice Litman-Goralnik is back" and says what came with her.
3. **Given** a life event deleted with a person still in the trash, **Then** its Restore is unavailable and a line says restoring that person brings it back.
4. **Given** a person whose reminders the deletion stopped, **When** restored, **Then** those reminders run again.
5. **Given** a relationship or membership deleted on its own whose other end is in the trash, **Then** it cannot be restored and says which end is missing.

### User Story 3 - Every kind in the design can be deleted on screen (Priority: P1)

**Independent Test**: Delete one item of each of the nine kinds from its own screen, find nine rows in the trash.

1. An animal's page offers delete; confirmed, the animal leaves the vault, appears in the trash and its reminders stop until it is restored.
2. A file removed from a group appears in the trash; restored, it is attached again with its content intact.
3. Deleting a person sends their relationships, group memberships, reminders, and the life events nobody else takes part in, to the trash with them.
4. Deleting a group sends its memberships, notes, reminders and files with it.
5. Every deletion confirmation of the nine kinds says the item stays in the vault's trash for 30 days.

### User Story 4 - Delete for good, or empty the trash (Priority: P2)

**Independent Test**: Delete for good a person deleted with two life events, find all three gone.

1. A row's "Delete for good" asks "Delete “Marcel” for good?", names what was deleted with it, says it cannot be undone, and offers "Delete for good" and "Keep them" ("Keep it" when nothing was deleted with it; Escape keeps them too).
2. "Empty the trash" counts what goes by kind ("This deletes 2 people, 3 life events and 1 file for good.").
3. A file deleted for good leaves storage and frees its size, unless something else still points at it.

### User Story 5 - The trash empties itself (Priority: P2)

1. An item deleted 31 days ago is deleted for good by the daily cleanup, with what went with it; one deleted 29 days ago stays. Merge leftovers are never listed nor cleaned up. An item that fails to go is reported and the cleanup carries on with the others.

### User Story 6 - The same through the API and MCP (Priority: P2)

**Independent Test**: Delete a reminder on screen, list the trash through the API, restore it, find it back on screen; repeat with MCP.

1. Listing gives each item's kind, identifier, title, context, deletion date, final deletion date and owner, paged and filterable by kind.
2. Restoring, deleting for good and emptying give the same outcomes and refusals as the screen.
3. An animal can be deleted through the API and MCP.
4. Another vault's or account's item answers as not existing.
5. The existing group and life event restore endpoints and tools keep working.

### Edge Cases

- A life event deleted on its own before its person was deleted stays in the trash when the person is restored.
- A relationship or membership goes with whichever of its two ends is deleted first, and comes back when that end is restored, even if the other end is still in the trash. The screens show it once both ends are back. Keeping it in the trash would leave it pointing at an owner no longer there, out of "Everything" and out of the cleanup for good.
- An animal whose kind was deleted from the account settings meanwhile cannot be restored, and says why.
- Restoring something already restored or deleted for good in another tab says it is no longer in the trash. Two restorations of one item at once count it back once.
- Deleting a person for good also deletes for good a life event in the trash that is left with nobody in it, since it can no longer be shown or restored.
- The "me" person, archived people and records deleted with their vault never appear.
- A file in the trash still counts towards storage.
- Items set aside before the trash existed are listed and get their 30 days from the release.
- A kind behind a flag that is off for the account is absent from the tab, the API and MCP, and the daily cleanup leaves it alone: its data is kept while the capability is withdrawn (`.claude/rules/flags.md`).

## Requirements *(mandatory)*

- **FR-001**: Vault settings MUST be split into General and Trash tabs, each with its own address; Trash shows its count.
- **FR-002**: Trash MUST list items deleted from the vault within retention, newest first, paged, with title, kind, context, deletion date and days left.
- **FR-003**: "Everything" MUST leave out items whose owner is also in the trash; a single kind lists all of its items.
- **FR-004**: The Show menu MUST follow the actions menu contract (ARIA state, keyboard route, dismissal).
- **FR-005**: A countdown of three days or fewer MUST read as a warning by more than colour (a warning glyph replaces the clock), and every countdown names its date on hover and focus.
- **FR-010**: Each of the nine kinds MUST be deletable where it is shown, into the trash.
- **FR-011**: Animals MUST gain deletion on screen, through the API and through MCP.
- **FR-012**: Removing a file from a group MUST send the attachment to the trash, keeping its content.
- **FR-013**: A person and a group MUST take their dependents (US3) into the trash, each remembering its owner.
- **FR-014**: Every deletion confirmation of the nine kinds MUST mention the trash and its 30 days.
- **FR-020**: Restoring MUST put the item back with everything deleted with it, reactivating the reminders its deletion stopped.
- **FR-021**: An item whose owner, other end or kind is gone MUST NOT be restorable; its control stays, marked unavailable, pointing at the reason.
- **FR-022**: A restore MUST end with a notice naming what came back, or counting it by kind.
- **FR-030**: Deleting one item for good, and emptying the trash, MUST be confirmed first, naming what goes; a file deleted for good MUST leave storage once nothing points at it.
- **FR-032**: Items older than 30 days MUST be deleted for good daily, with their dependents; merge leftovers and kinds behind a flag that is off for the account are exempt.
- **FR-040**: The API MUST list, restore, delete for good and empty, with the screen's outcomes and refusals, and be documented; MCP MUST offer one tool per operation, answering through the API.
- **FR-050**: Every operation MUST stay within the named vault and the caller's account, and each restore, deletion for good and emptying MUST be written in the account log.
- **FR-052**: All copy MUST be translated, counted phrases varying with their number, and the tab, menu, confirmations and notices MUST meet WCAG 2.2 AA.

## Success Criteria *(mandatory)*

- **SC-001**: Each of the nine kinds can be deleted on screen and restored with every value it held.
- **SC-002**: A person deleted by mistake is back, with what went with them, in three actions from the vault.
- **SC-003**: No item stays in the trash more than a day past its 30 days.
- **SC-004**: Every Trash tab operation is available through the API and MCP with the same outcome.
- **SC-005**: No item of another vault or account can be reached through any surface.
- **SC-006**: Every merge undoable today stays undoable.

## Assumptions

- Retention is 30 days, equal to the orphaned file grace period. Files in scope are those attached to a group; a file in a person's field returns with the person; possession documents are out of scope.
- "Animal" stands for every entity type; the row shows the type's own name.
- The mockup's search box and row selection are not drawn and are out of scope. Deleting a vault is unchanged.
