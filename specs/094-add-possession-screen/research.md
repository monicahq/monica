# Research: Add a Possession Screen

Every decision copies a sibling in the codebase, named in the entry.

## R1. The schema: edit the possessions migration, add one pivot migration

- **Decision**: `2026_10_26_000002_create_possessions_table.php` gains five nullable columns (data-model).
  The link to tags is a new table in `2026_10_26_000006_create_possession_possession_tag_table.php`.
- **Rationale**: The branch is unmerged and the migrations skill says to alter existing migrations
  until production; one table per migration. A database built earlier is rebuilt with `migrate:fresh`.
- **Rejected**: an add column migration (only for a column reaching a database in use).

## R2. Way of acquisition is an enum on the possession, not a new concept

- **Decision**: `PossessionAcquisitionEnum` (`Bought`, `Gift`, `Inherited`, `Made`), `Translatable`,
  with `label()`, `dateLabel()` and `takesPerson()` (true for Gift and Inherited).
- Principle VII describes what a Possession carries, so the sentence gains the acquisition and the
  tags: constitution 2.10.0 becomes 2.11.0 (materially expanded guidance), with a Sync Impact Report;
  `spec/models/062` and `064` follow.
- **Rejected**: a value list (templates only), a new fixed concept (it has no life of its own).

## R3. The giver is a soft deleted contact, so nothing is unlinked

- **Finding**: `DestroyContact` soft deletes; no foreign key to contacts uses `nullOnDelete`.
- **Decision**: `acquired_from_contact_id` keeps pointing at a deleted contact. `acquiredFrom()`
  excludes trashed contacts by default, so the giver disappears and returns on restore. The
  constraint is `nullOnDelete()` for the hard delete a vault deletion causes.
- The actions refuse a contact of another vault or a trashed one, and drop the person when the way
  of acquisition does not take one (spec story 4.1).

## R4. Declared value: a decimal and a currency from `CurrencyEnum`

- **Decision**: `declared_value` `decimal(12, 2)`, cast `decimal:2`; `declared_value_currency`
  `string(3)`, one of `CurrencyEnum` (ten currencies). The currency is required with a value and
  dropped without one. The form defaults it to `AvailableLocales::details($locale)['currency']`, as
  `BillingViewModel` reads it.
- **Rejected**: minor units in an integer (JPY has none; conversion code for no gain).

## R5. One action writes everything, inside one transaction

- **Decision**: `CreatePossession` and `UpdatePossession` gain the acquisition, `values` (keyed by
  field uuid) and `tagNames`. All checks run before the transaction: field values through
  `PossessionFieldValueFormat::refusal()` against the type's fields, as `UpdatePossessionFieldValues`
  does; tag names sanitised, 1 to 50 characters (092). Then one `DB::transaction` writes the row, the
  values and the tags. One journal entry, `PossessionCreation`.
- **Rationale**: Calling `UpdatePossessionFieldValues` from inside would journal a second
  "Changed the possession" line, and `after_commit` is false, so a rolled back nested action would
  still have queued its log.
- **`values` on update** only when given (API full replace leaves them to `PUT .../values`).

## R6. Tags resolved by name in one helper

- **Decision**: `App\Helpers\PossessionTagNames::sync(User, Possession, list<string>)`: reuse an account
  tag whose name matches ignoring case (`mb_strtolower`), create the rest through
  `CreatePossessionTag` with Blue, `sync()` the pivot. Used by both actions.
- **API and tools** send `tags` as names, like the form. On the API's full replace `PUT`, leaving
  `tags` out clears them, as for every other field; the MCP update keeps what is left out.
- **Deleting a tag**: the pivot cascades. `DestroyPossessionTag` loses its "nothing goes with it"
  sentence; the settings row adds `trans_choice(':count possession carries it|...')` when above zero.

## R7. The type picker: a combobox and listbox, fields fetched with Alpine Ajax

- **Finding**: no listbox exists; the life event picker searches buttons and fetches its fields with
  `$ajax(fieldsUrl, { target })` into a fragment with `x-merge="replace"`.
- **Decision**: `resources/js/possession-type-picker.js`: trigger with `aria-haspopup="listbox"` and
  `aria-expanded`; search input `role="combobox"` with `aria-activedescendant`; options
  `role="option" aria-selected`; arrows, Enter, Escape, outside click; focus back to the trigger. The
  types (a few dozen) come inline from the view model. Choosing writes a hidden `type` and calls
  `$ajax` on `possessions.typeFields.index`. Types have no icon: a colour dot, its name read out.
- A second picker of this kind would be extracted (components rule: contract at the second file).

## R8. Fields by kind, giver search, tag input

- No renderer exists for a possession field (`x-field-input` takes template `Field`s). A partial
  `_field.blade.php` switches on `PossessionFieldKindEnum`: input text, number (`step="any"`), date,
  `x-checkbox`, `x-select`, and a `fieldset` of five radios for Rating. Names `values[{uuid}]`.
- The giver uses `x-record-picker` against `possessions.contactCandidates.index`, as relation fields
  use `fields.choices.index`: a vault may hold thousands of people.
- `resources/js/tag-input.js`: chips with a labelled remove button, hidden `tags[]`, suggestions from
  the account's tags passed inline, Enter and comma add, Backspace removes the last.
- Ways of acquisition use `x-option-row` with `chip` and `type="radio"`, plus a "Not recorded" radio.
- The photo area is a static box with its reason in text; no input, so nothing to disable.
