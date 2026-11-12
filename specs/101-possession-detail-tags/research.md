# Research: Tags on the Possession Page

## R1. Three routes, one controller

- **Decision**: `PUT …/possessions/{possession}/tags/{possessionTag}` puts a tag on, `DELETE` on the
  same URL takes it off, `POST …/possessions/{possession}/tags` with a `name` creates a tag and puts it
  on. One `App\Http\Controllers\App\Vaults\PossessionTagAttachmentController` with `update`, `destroy`, `create`.
- **Rationale**: the tag is bound by route model binding, so `ResolvesModelInAccount` refuses another
  account's tag and a tag deleted meanwhile answers not found (spec edge case), with no query of our
  own. PUT and DELETE are idempotent, which covers the two-tabs edge cases.
- **Alternatives**: one POST taking a name for both cases (a deleted tag would be silently recreated).

## R2. Two actions, plus the existing creation

- **Decision**: `AttachPossessionTag` and `DetachPossessionTag`, each taking `user`, `possession`,
  `possessionTag`. They check both records are in the user's account, change the link only when it
  differs, and log `PossessionUpdate` only then. Creating from the panel calls `CreatePossessionTag`
  (which logs `PossessionTagCreation`) then `AttachPossessionTag`.
- **Rationale**: matches `UpdatePossessionFeatured` (no change, no log) and
  `Attach/DetachPossessionTransactionDocument` naming. No new journal case: a tag change is a change
  to the possession, as `UpdatePossession` already logs it.
- **Alternatives**: a combined "create and attach" action (one more class for two calls).

## R3. The colour a tag created from the panel gets

- **Decision**: `Account::nextPossessionTagColour(): VaultColourEnum` returns the palette colour after
  the colour of the account's most recently created tag, Blue when there is none, wrapping after
  Slate. The view model announces it; the controller passes it to `CreatePossessionTag`.
- **Rationale**: one method answers both the announcement and the creation, so they cannot disagree.
- **Not changed**: the settings screen, the API and the MCP tools keep Blue as the default (092 FR-004).

## R4. The panel closes after a change

- **Decision**: the picker's `x-data` wraps three regions with ids, and every request targets them
  (`x-target="possession-tag-chips possession-tag-options possession-tag-count"`). The wrapper is
  not replaced, so the panel survives the merge; `@ajax:after` then closes it and returns focus to
  Add a tag, unless the response holds a validation error, which stays visible in the panel. The controller redirects
  `back()` with a `status` flash; the toaster is `x-sync`, so the message shows.
- **Rationale**: the pattern the preferences and relationship type screens use. The header is shared
  with the Transactions section, so `back()` lands on whichever page asked.
- **Failure**: `@ajax:error` reloads the page, which refreshes a list holding a deleted tag.

## R5. Keyboard and filtering in a new `possession-tag-picker.js`

- **Decision**: an Alpine component for the combobox: open and close, outside click, Tab, Escape
  returning focus to "Add a tag", Up and Down moving `aria-activedescendant`, Enter choosing, filtering
  the server-rendered options by `data-name` ignoring case, and offering "Create" when no name equals
  the trimmed query. Registered in `resources/js/app.js` like `possessionTypePicker`.
- **Rationale**: the account's tags are few and already on the page; filtering on the server would
  add a round trip per keystroke for nothing. The listbox contract lives in one file only, so no
  component is extracted (components rule: second file).
- **Alternatives**: `x-combo-box` (one label from a list, not multi-select); `tag-input.js` (free
  text chips for a form, no listbox semantics).

## R6. One confirmation for both ways of taking a tag off

- **Decision**: each chip renders an `x-modal.warning` (method `delete`, `x-target` as R4) with its
  close button as trigger, and an id on that button. Unticking in the panel closes the panel and
  clicks that button, so one modal serves both paths and focus returns to the chip on cancel.
- **Rationale**: `x-modal.warning` already traps focus, teleports and answers Escape (FR-008, FR-014).
- **Copy**: title "Remove “:name” from this possession?", body "The tag stays in your account for
  your other possessions.", confirm "Remove", cancel "Keep it". No "cannot be undone", since it can be.

## R7. What the view model adds

- **Decision**: `PossessionViewModel::header()['tags']` becomes a list of `{id, name, colour, tint}`;
  a new `tagOptions()` lists every account tag sorted by name with `carried` and `others` (possessions
  carrying it, minus this one), from one `withCount('possessions')` query; `tagCount()` and
  `nextTagColour()` feed the foot and the create line. URLs are built in the view model.
- **Counts**: `trans_choice('{0}Unused|:count other|:count others', …)` is refused by the check (a
  pinned `{0}` is wording for none and must be guarded); the view model returns `__('Unused')` at zero
  and `trans_choice(':count other|:count others')` above.

## R8. Outside this feature

The design's links from a chip to "every possession tagged" (no filter yet), Undo, and the
"close after each pick" variant (spec, Assumptions). The API and MCP tools already set a possession's
tags by name through `UpdatePossession`, so constitution X needs nothing new.
