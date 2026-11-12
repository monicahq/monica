# Research: Edit a Possession

## R1. One save, one transaction, one journal entry

**Decision**: `UpdatePossession` gains `?array $values = null` (null leaves the field values as they
are, as `tagNames` already does). It checks every value before writing anything and writes them inside
its own transaction.

**Why**: FR-006 asks for everything or nothing. Calling `UpdatePossession` then
`UpdatePossessionFieldValues` from the controller commits the first before the second can refuse, and
journals the change twice (both log `PossessionUpdate`).

**Rejected**: two actions in a controller transaction (business logic in the controller, two journal
entries); calling `UpdatePossessionFieldValues` from inside `UpdatePossession` (two journal entries).

## R2. The value rules move into one helper

**Decision**: `app/Helpers/PossessionFieldValues.php`, next to `PossessionTagNames` and
`PossessionDeclaredValue`: `fields()` checks a set of values against a type and returns its fields
keyed by uuid, or throws the refusals keyed `values.<uuid>`; `write()` sets or clears each value.
`CreatePossession`, `UpdatePossessionFieldValues` and `UpdatePossession` use it.

**Why**: the check is about twenty lines already copied in two actions; a third copy is the point where
`CLAUDE.md` says to extract. `write()` is shared by the two update actions; creation keeps its insert.

## R3. What the form does not show is passed back unchanged

**Decision**: the web `update` passes `identifier`, `status` and `quantity` from the possession itself.

**Why**: `UpdatePossession` defaults them to null, Owned and one. Read from the request they would
reset silently, breaking FR-007. The API keeps its replace semantics; it is a different contract.

## R4. One form view model, two modes

**Decision**: `PossessionFormViewModel` takes `?Possession $possession = null`. Each reader falls back
from the old input to the possession, then to today's empty default. `isEditing()`, the action, the
method, the title, the trail and the cancel address follow from it.

**Why**: the add form and the edit form are one screen (FR-002); a second view model would copy
twelve methods. The insurance and maintenance view models already carry `isEditing`.

## R5. The type is shown, not picked

**Decision**: when editing, `form.blade.php` shows the type as text with its colour and renders
`_type-fields` straight from `fieldsBlock()`, without the picker. No `type` input is sent.

**Why**: FR-004. `fieldsBlock()` reads the possession's type and fills each field from the old input,
then from the stored value. Stored values already have the shape the inputs take (`1`/`0`, `Y-m-d`,
the rating digit), so no conversion is needed.

## R6. Routes

`GET vaults/{vault}/possessions/{possession}/edit` (`possessions.edit`) and
`PUT vaults/{vault}/possessions/{possession}` (`possessions.update`) on `PossessionController`. The
possession binds through `ResolvesModelInVault`, which answers not found for another vault or account,
so the controller needs no check of its own (as `show` and `destroy` do today).

## R7. Smaller points

- The split button keeps its `unavailable` prop: `ButtonSplitTest` covers it and it may serve again.
- The `Featured` checkbox on edit says how many the vault features, as on the add form.
- The guide's line "Editing a possession also comes later" is replaced by how to edit.
