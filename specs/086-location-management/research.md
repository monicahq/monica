# Research: Location Management

## R1. A dedicated model, and the amendment that allows it

Decision: one `Location` model, and principle VII lists Location as a fixed concept, in the same pull
request (MINOR). Main is at 2.5.1 and the unmerged vehicle branch also moves to 2.6.0: whichever
merges second takes the next MINOR and merges the two lists.
Rationale: the user asked for a single model with `parent_id`, and a path and a cycle check need a
typed parent column.
Rejected: an entity type on templates (no parent column, no cycle rule); a model without amendment
(Governance forbids leaving the constitution contradicted).

## R2. No flag

Decision: the screen ships to every account. A `locations` flag was built first and removed at the
user's request on 2026-10-25.

## R3. The form is a page state, not an Alpine panel

Decision: `new`, `edit` and "new inside" are GET routes rendering the list screen with the panel open
and prefilled. A failed save redirects back to the same URL with the errors and old input. Alpine Ajax
swaps the panel in place (`x-target`) so opening it does not reload the list.
Rationale: the design opens the form above the list. Server state keeps validation errors, old input
and the "inside X" title working without JavaScript.
Rejected: an Alpine panel fed from JSON per row (errors must reopen it client side); a query string
for the parent (code style forbids it without reason).

## R4. Delete refusal is a policy

Decision: `LocationPolicy::delete` denies when the location has children, with
`trans_choice(':count location is inside it. Move it first.|:count locations are inside it. Move them first.')`.
`DestroyLocation` calls `$user->authorize('delete', ...)`; the list asks `inspect()` and renders a
refused control `aria-disabled` with `aria-describedby` on the reason.
Rationale: the screen and the action both state the rule, which is the case `.claude/rules/policies.md`
gives a policy for. The design's delete button stays on every row.
Rejected: hiding the button (policies.md: a refused control stays on the screen).

## R5. Parent rules live in the actions

Decision: `CreateLocation` and `UpdateLocation` load the parent through the account and throw
`ModelNotFoundException` for one of another account. `UpdateLocation` walks up from the new parent and throws
`InvalidArgumentException` with a translated message when it meets the location itself. The controller
validates the parent with `Rule::exists()` scoped to the account, and catches the cycle refusal into
`withErrors(['parent' => ...])`. The form's select leaves out the location and its descendants.
One query per level, for a handful of levels.
Rejected: a closure table or materialised path (no listing needs them at this size).

## R6. One emoji

Decision: `app/Rules/SingleEmoji.php`: the value is one grapheme (`/^\X$/u`) containing
`\p{Extended_Pictographic}`, a regional indicator or the keycap mark U+20E3. PCRE 10.44 here supports
the property. Column `string(32)`, since a family emoji with skin tones takes 25 bytes.
Rejected: a fixed list of twelve (the design offers a free field); any string of two characters
(the design's own shortcut, which accepts letters).

## R7. Paths and the tree are built in memory

Decision: `LocationsViewModel` loads the account's locations once and builds the tree, the paths
(" › "), the nearest ancestor address and the counts from that one collection. Sorting is
`SORT_NATURAL | SORT_FLAG_CASE` on the name at each level. One query and no SQL recursion, for tens
of locations, the same on the three databases.

## R8. Nested lists, not an ARIA tree

Decision: nested `<ul>`, one per level; screen readers announce the level (FR-016) with no script.
Rejected: `role="tree"`, which promises arrow key navigation the design does not provide.

## R9. Hard delete

Decision: deleted outright; only an empty one can be, and the changelog keeps its name (principle VIII).
Rejected: soft delete (a restore path for an empty record).

## R10. API and MCP deferred

Decision: none in this change, stated in the pull request (principle X). The actions take every input
the endpoints will need, so adding them later is transport only.
