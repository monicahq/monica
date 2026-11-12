# 077. PossessionLoan

**Status:** in progress (`app/Models/PossessionLoan.php`)
**Spec:** 3.2, specs/118-possession-loans

## What it is

One period one [Possession](062-possession-in-progress.md) is out of your hands with one [Contact](019-contact-done.md) of its vault, or in your hands from them, without changing owner. The active loan says who has it now.

## Fields

```
id                              int                         primary key
uuid                            uuid                        route key, and identifier for the API
account_id                      int                         account of the possession
vault_id                        int                         vault of the possession
possession_id                   int                         possession it belongs to
contact_id                      int                         a contact of the same vault; null once deleted for good
direction                       string                      lent or borrowed
status                          string                      planned, active or returned
started_on                      date                        required
due_on                          date                        optional; not before started_on
returned_on                     date                        required when returned, null otherwise; not before started_on
condition_out_id                int                         a possession condition of the account, optional
condition_back_id               int                         a possession condition of the account, only when returned
deposit_amount                  decimal (12,2)              optional
deposit_currency                string (3)                  required with an amount
purpose                         text                        up to 5,000 characters, optional
is_in_provenance                boolean                     whether it writes change of custody events
departure_provenance_event_id   int                         the event of its start, while active or returned
return_provenance_event_id      int                         the event of its return, while returned
```

## Rules

- A possession has at most one active loan. Overdue is read, never stored: active, with a due day before today.
- A loan records no transaction and never changes the owner.
- The contact is chosen among the vault's contacts, or created with the loan in one step. Deleting the contact keeps the loan, which then names a deleted contact; merging contacts moves the loan to the one kept, and undoing the merge moves it back.
- A loan in the provenance keeps one change of custody event for its start and one for its return; taking it out of the provenance or deleting it deletes both.
- Loans are reached from the possession page, listed on the contact's page, through the API (`/api/v1/vaults/{vault}/possessions/{possession}/loans`) and through the MCP server. Creating, editing, returning and deleting are written to the changelog.
- Deleting the possession deletes its loans.
