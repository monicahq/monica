# Research: Edit relationship form

## R1. One select carries the type and the direction

- **Decision**: the select posts `relationship` as `{typeId}-{a|b}`, where the letter is the side
  the page’s contact holds: `a` carries `role_a`. A symmetric type offers `-a` only.
- **Rationale**: a single select can post one value, and the screen must work before Alpine starts.
  Naming the page’s contact’s side makes the value mean the same whatever order the row is stored in.
- **Alternatives**: a type select plus a hidden `swapped` driven by Alpine (breaks without script,
  and `swapped` is relative to storage order, which the reader cannot see).

## R2. The action knows which contact the edit came from

- **Decision**: `UpdateRelationship` takes `Contact $contact` (the page’s contact) and
  `bool $contactHoldsRoleA`, replacing `swapped`. It refuses a relationship that does not involve
  `$contact` with `ModelNotFoundException`. `Relationship::involves(Contact)` answers that question
  for the action and for the controller’s `edit()` and `update()`, which answer 404.
- **Rationale**: the rename needs "the other side", and today nothing checks that the relationship
  in the URL involves the contact in the URL: the binding only scopes it to the vault.
- **Alternatives**: keep `swapped` and compute it in the controller (logic in the controller).

## R3. The status follows the checkbox

- **Decision**: the form posts `has_ended` (boolean). The controller maps it to
  `RelationshipStatusEnum`. The action clears `endedOn` whenever the status is `Current`, so an end
  date typed before unticking is never saved.
- **Rationale**: the clearing is a rule about the data, so it lives in the action and is tested once.
  The hidden field is also `:disabled`, so it is not posted.
- **Alternatives**: derive the status from the end date alone (rejected in the spec’s clarification).

## R4. The rename happens inside the same transaction

- **Decision**: `UpdateRelationship` takes `?string $otherFirstName` and `?string $otherLastName`.
  A non-null first name means "rename". It is refused with `ModelNotFoundException` when the other
  side has a record of their own. It calls `UpdateContactName` as the last statement of its
  transaction, and only when the name differs.
- **Details**:
  - `UpdateContactName` nulls every name part it is not given, so the call passes the other side’s
    prefix, middle name, suffix, maiden name, nickname and name order through unchanged.
  - Queues run with `after_commit => false`, so a job dispatched inside a rolled back transaction
    would still run. Putting the rename last means every refusal is thrown before it dispatches.
- **Precedent**: `CreateRelationship` calls `CreateContact` inside its transaction.
- **Alternatives**: two actions called from the controller inside `DB::transaction` (logic in the
  controller); a new combined action (a third class for one screen).

## R5. Sentences reuse the add form’s phrases

- **Decision**: the sentences are built by `RelationshipEditViewModel::sentenceGroups()` from
  `RelationshipTypeChoices` (which keeps a withdrawn type the link carries). Option `a` is
  `__(':one is :two’s :role.')` with the page’s contact, the other person and `role_a`. Option `b`
  swaps the first two. The role is lowercased and a nameless other is `__('an unnamed :role')` with
  their role, capitalised when it opens the sentence. This is what `resources/js/relationship-form.js`
  does.
- **Rationale**: the phrase is already translated in every locale, and the two forms then read alike.
- **Alternatives**: the design’s "are partners" for symmetric types (needs a plural role that does not
  exist).

## R6. Alpine inline, no new script

- **Decision**: one `x-data` on a plain `<div>` around the form holds `role`, `mode` and `ended`.
  - Each `<option>` carries `data-role`, the other person’s role, which feeds the caption on change.
  - The name fields and "Ended on" sit in `<fieldset x-show :disabled>`.
- **Rationale**: three values do not earn a file in `resources/js`. `.claude/rules/components.md`
  forbids `@js()` inside a component tag, and a fieldset stops hidden inputs from being posted.

## R7. Strings

- **New**: `Relationship`, `This relationship has ended`, `I know their name now`,
  `:name has no contact record, so their name is changed here.`
- **Reused**: `:one is :two’s :role.`, `an unnamed :role`, `Saved on both records at once.`,
  `Save changes`, `Between :one and :two.`, `I do not know their name`, `First name`, `Last name`.
- **No longer used**: `Is it still going`, `Still going`, `Over`, `:name is the :role.`,
  `Which way round` (the API docs portal writes that phrase as plain English, not as a key).
- `monica:localize` prunes and adds.
