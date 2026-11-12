# Research: Possession Detail

## R1. Routes

- Decision: `GET vaults/{vault}/possessions/{possession}` (`possessions.show`) and
  `DELETE vaults/{vault}/possessions/{possession}` (`possessions.destroy`) on `PossessionController`;
  `PUT vaults/{vault}/possessions/{possession}/featured` (`possessions.featured.update`) on a new
  `PossessionFeaturedController@update`. All take `whereUuid('possession')`, so `new` and `locations`
  never match.
- Rationale: `Possession` uses `ResolvesModelInVault`, so another vault or account is not found
  without a check of our own; code-style wants one controller per extra action.

## R2. Featuring gets its own small action

- Decision: `UpdatePossessionFeatured(user, possession, isFeatured)` sets the flag, arranges the
  featured order with `FeaturedPossessionOrder::arrange()` as `UpdatePossession` does, and logs
  `PossessionUpdate`.
- Rationale: calling `UpdatePossession` means passing back every attribute. A soft deleted giver
  loads as null and would be cleared for good (principle VIII); a toggle should touch one column.
- Rejected: `UpdatePossession` from the controller; a policy (the rule is the account check, written
  once, in the action).
- API: `PUT api/.../possessions/{possession}` already sets `is_featured`; nothing added (principle X).

## R3. Deleting uses `DestroyPossession` as it is

- Decision: the menu's last item opens `x-modal.warning` (the card `x-delete-action` wraps), and the
  controller redirects to `possessions.index` with `:name is deleted.`.

## R4. Field values are written by `FieldValueFormatter`

- Decision: add `FieldValueFormatter::formatPossessionValue(PossessionFieldValue)`, reusing its user
  number and date formats: text and select as stored, number in the user's separators, date in the
  user's date format, boolean `Yes`/`No`, rating `__(":rating out of 5")`: a named placeholder, since the wording does not vary by number.
- Rationale: one place already writes a value for this user; a second formatter would drift.
- Rejected: a new `PossessionFieldValueWriter` helper.

## R5. Unavailable sections and Edit (clarifications 1 and 2)

- Decision: Overview is an `<a aria-current="page">`. The nine others are rendered with
  `aria-disabled="true"`, no `href`, and `aria-describedby` pointing at one visually hidden sentence,
  "Comes in a later version.", shared by all nine (policies.md: one sentence, not nine).
- Decision: `x-button.split` gains an optional `unavailable` prop (the reason). When set, its main
  part renders `aria-disabled="true"` with that reason; the chevron still opens the menu.
- Rationale: policies.md says a refused control stays on the screen, not activable, with its reason.

## R6. The page reads once

- Decision: `PossessionViewModel` loads the possession with `possessionType.groups.fields`,
  `possessionType.standaloneFields`, `values`, `tags`, `acquiredFrom`, and the tree through
  `LocationTree::forAccount()` for "kept in … at …". Values are keyed by field id.
- Header place: the location's name, then "at" its top level location when it is nested.

## R7. What the facts panel holds

- Decision: Type, Kept in, Status (`PossessionStatusEnum::label()`), Acquired (acquisition label and
  `acquired_on` in the user's date format, or year only), From (contact name, only while not
  deleted), Quantity (only above one). Absent facts leave their row out.

## R8. Listing rows become links

- Decision: every row and featured card in the 096 listing links to `possessions.show`. The 096
  assumption "rows are not links" is superseded.
