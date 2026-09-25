# Research: Contacts and more

## R1. Where the contact count comes from

**Decision**: sum `vaults.contact_count` over the account's vaults.

**Rationale**: the vault dashboard, the vault list and the vault settings already read this counter,
so the number on this screen agrees with theirs. One aggregate query, no join.

**Rejected**: counting `contacts` by `account_id` (a second source of truth that can disagree with
the dashboards).

## R2. Where the example cards live

**Decision**: a `examples()` method on `EntityTypesViewModel` returning three fixed arrays (icon,
kind, tag, name, field names, caption), every string through `__()`.

**Rationale**: the view models skill allows presentation logic, and a view model keeps Blade to one
loop. The names Judy Geller, Garfield and Central Perk are the design's copy, translatable like the
rest.

**Rejected**: writing the three cards out in Blade (three near identical blocks); a component (used
in one file, below the three file bar of `components.md`).

## R3. How the Contacts row sits beside the sortable list

**Decision**: draw the Contacts row inside the same `x-panel`, above the `#entity-types` container,
as plain markup with no `x-sortable-row`.

**Rationale**: the position form answers by replacing `#entity-types` (`x-merge="replace"`), so a
row outside it is never re-rendered or offered as a drop target. It has no position to record.

**Rejected**: a `pinned` prop on `x-sortable-row` (a component change for one row on one screen).

## R4. Wording of the counts

**Decision**: `trans_choice(':count profile|:count profiles', $n)` with `__('No profiles yet')` at
zero, for both custom types and Contacts. Contacts at zero is possible on a fresh account with no
vault.

**Rationale**: `code-style.md` treats an empty state as its own sentence and a count as a counted
phrase.

## R5. What leaves the screen

**Decision**: remove `summary()` and its two tests, the slug line, the singular name, the header
action, and the empty state. `tint()` stays; `countLabel()` keeps its name with new wording.

**Rationale**: FR-007. `summary()` has no other caller (checked with `grep`).

## R6. The word "profile" in other locales

**Decision**: each locale picks one word for "profile" and uses it in every new string on this
screen. No glossary column yet.

**Rationale**: the spec defers the glossary to the follow up rename; one word per locale on this
screen keeps the follow up a matter of reuse, not reconciliation.
