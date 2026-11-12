# Research: Possession Loans

## R1 Storage
**Decision**: a `possession_loans` table carrying `account_id`, `vault_id`, `possession_id` and
`contact_id`, model `PossessionLoan`, `ResolvesModelInVault`. Shapes in [data-model.md](data-model.md).
**Rationale**: as 116 R1 and 117 R1; `RouteProtectionTest` needs the two scoping columns.

## R2 Direction and status
**Decision**: `PossessionLoanDirectionEnum` (`lent`, `borrowed`) and `PossessionLoanStatusEnum`
(`planned`, `active`, `returned`). Overdue is derived: active, with a due day before the user's today.
**Rejected**: an `overdue` status, which would need a job to flip it.

## R3 The contact
**Decision**: `contact_id` nullable, `nullOnDelete()`. A trashed or missing contact reads "A deleted
contact" (spec Edge Cases); the loan is read `withTrashed()`. The contact MUST be of the possession's
vault, refused with `ValidationException` on `contact_id` otherwise.
**Rationale**: `DestroyContact` soft deletes, and `RollBackAccountImport` force deletes; a cascade
would make loans disappear silently (principle VIII).

## R4 Actions
**Decision**: `CreatePossessionLoan`, `UpdatePossessionLoan`, `DestroyPossessionLoan`. Create and
update lock the possession row first and refuse a second active loan on `status` with the wording in
the spec, so two requests cannot both see none. They refuse a due day or a return day before the
start, and a returned loan without a return day. Update clears the return day and condition back when
the status is not Returned. "Mark as returned" is an update.

## R5 A contact created with the loan
**Decision**: `CreatePossessionLoan` takes either `contact` or `newContactFirstName` with
`newContactLastName`, and calls `CreateContact` in the same database transaction, as
`CreateRelationship` does. The new contact is a full one (`isPartial: false`).
**Rejected**: "How you know them" from the design; a contact has no such field.

## R6 The contact search
**Decision**: reuse `x-record-picker`. A new GET `possessions.loans.contactCandidates` renders the
vault's contacts matching `query` (empty query: the first contacts by name), through
`x-record-picker.candidates`, which gains a default slot printed after the list. The loan view fills
that slot with "Create “:name” as a new contact", which sets Alpine state on the form to show the first
and last name fields. Contacts are read like `GroupMemberCandidatesViewModel` reads them, ten at most.
**Rejected**: a combobox of its own; the record picker is the vault's one way to choose a record.

## R7 Provenance
**Decision**: `is_in_provenance` on the loan, and two nullable foreign keys,
`departure_provenance_event_id` and `return_provenance_event_id`, `nullOnDelete()`. The loan actions
write the events directly (no journal line of their own): type Custody, precision Day, title "Lent to
:name", "Borrowed from :name", "Returned by :name" or "Returned to :name" in the user's locale,
`from` and `to` the two parties' names. The departure event exists while the loan is active or
returned, the return event while it is returned; unticking or deleting the loan deletes both.
**Rejected**: a `possession_loan_id` on provenance events, which changes 116's table and contract.

## R8 Contact merge
**Decision**: `MergeContacts` moves the absorbed contact's loans to the kept one, writing a
`loan_moved` ledger entry each; `UndoContactMerge` gives them back, as `membership_moved` does.

## R9 Screen behaviour
**Decision**: as 117 R9. One GET renders the section; the new loan form, help, deletion warning and
summary line are Alpine state. "Edit" is its own GET; "Mark as returned" is
`possessions.loans.return.new`, rendering the edit form with Returned and today. Writes redirect back
with a flash.

## R10 The contact's page
**Decision**: `ContactLoansBlock` beside the other blocks of `ContactViewModel`, newest first, absent
when the contact has no loan. Each row: possession name linking to its Loans section, direction,
status, period. `show.blade.php` includes `_loans.blade.php`.

## R11 Sections list
**Decision**: Loans keeps its place in `PossessionViewModel::sections()`, gains URL and count.

## R12 API and assistant
**Decision**: as 116 R11, under `possessions/{possession}/loans`. See [contracts/api.md](contracts/api.md).

## R13 Journal
**Decision**: four `UserActionEnum` cases: creation, update, return (an update that sets Returned),
deletion. A contact created with the loan writes its own creation line, as `CreateContact` does.

## R14 Constitution
**Decision**: principle VII amended to name Possession loan as a fixed concept, with
`spec/models/077-possession-loan-in-progress.md` and the 062 possession model updated.
