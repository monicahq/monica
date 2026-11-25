# Research: Bulk actions on the contact list

## R1. Two bulk actions over the existing single ones

**Decision**: `DestroyContacts` calls `DestroyContact` once per person; `ChangeContactCategories` writes
each person's categories through `SetFieldValue` and `DestroyFieldValue`. Each returns who changed and
who was left unchanged, with the reason.

**Rationale**: FR-006 and SC-003. A bulk deletion that reuses the single one cannot drift from it: same
log entry, same revoked links, same deactivated reminders, same refusal for the contact that represents you.

**Alternatives**: one query deleting every row at once (skips the side effects); a queued job (the 500
cap keeps the request short enough).

## R2. Where a category is written

**Decision**: the person's categories live in `OwnedFields::categoryFields()`, the multiple choice fields of
their structure drawing on the account's "Contact categories" list. The first one in structure order gets
the chosen category; any other is emptied, so the person holds that category and no other. A person with
no such field, or whose field is required when the category is removed, is left unchanged.

**Rationale**: the list filter, the vCard export and the Markdown export already read categories this way.

## R3. Undo of a category change carries the previous state in the form

**Decision**: the controller flashes each person's previous categories. The notice renders an Undo form
whose hidden inputs hold them, posted to the same endpoint as the change. Undo is an ordinary change,
validated and logged like any other.

**Rationale**: the groups list already offers Undo in a `x-notice` fed by a flash (`deletedGroup`). It
keeps no server state and needs no second action.

**Alternatives**: a snapshot table (a schema change for a 4.5 second affordance); the design's floating
toast (the toaster has no action slot, and a notice can be reached with the keyboard).

## R4. The confirmation is rendered by the server

**Decision**: Delete submits the selection to `GET contacts/deletion`, which answers the confirmation
dialog through Alpine Ajax. It names the people and says who is kept.

**Rationale**: a sentence naming people and counting the rest needs `trans_choice()`, and only the server
knows whether a selected contact may be deleted (the contact policy).

## R5. The selection count is worded in the browser

**Decision**: the toolbar's "3 selected" uses `CountedPhraseWordings` and `Intl.PluralRules`, as
`resources/js/reminder-interval.js` does.

**Rationale**: the count changes on every tick, and a round trip per click would lag. Precomputing one
phrase per count (as `field-type.blade.php` does) breaks with "All" rows per page.

## R6. 500 people per request

**Decision**: both endpoints refuse more than 500 people, the largest numbered page size.

**Rationale**: "All" rows per page can show thousands. Deleting each through `DestroyContact` costs
several queries per person.

## R7. One endpoint per action on every surface

**Decision**: `PUT contacts/categories` and `DELETE contacts` in the web routes and in the API v1, each
taking a list of people. The API answers 422 over 500 people, and lists who was left unchanged and why.
Two MCP tools call them through `answerThroughApi()`.

**Rationale**: principle X and `.claude/rules/mcp.md`: the controller is the single implementation.

## R8. Wording taken from the single delete

**Decision**: "Delete :name?", "Delete the contact" and "Everything written on the record is deleted with
them." are reused. Only the plural forms are new.

**Rationale**: `.claude/rules/policies.md`: take the wording the reader already sees. The design's "along
with their notes, reminders and relationships" is not what the single delete says.
