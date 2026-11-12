# Data Model: Possession Loans

## possession_loans (new)

| Column | Type | Rule |
| --- | --- | --- |
| id, uuid | bigint, uuid | uuid is the route key |
| account_id, vault_id | foreign keys | the possession's (R1) |
| possession_id | foreign key | cascade on delete |
| contact_id | foreign key, nullable | a contact of the same vault; null on delete (R3) |
| direction | string | a `PossessionLoanDirectionEnum` value, required |
| status | string | a `PossessionLoanStatusEnum` value, required |
| started_on | date | required |
| due_on | date, nullable | not before `started_on` |
| returned_on | date, nullable | required when Returned, not before `started_on`, null otherwise |
| condition_out_id, condition_back_id | foreign keys, nullable | `possession_conditions` of the account; null on delete |
| deposit_amount | decimal(12,2), nullable | 0 to 9999999999.99 |
| deposit_currency | string(3), nullable | a `CurrencyEnum` value, required with an amount |
| purpose | text, nullable | at most 5000 characters |
| is_in_provenance | boolean | default false |
| departure_provenance_event_id, return_provenance_event_id | foreign keys, nullable | null on delete (R7) |
| created_at, updated_at | timestamps | index on `(possession_id, started_on)` |

`condition_back_id` is null unless Returned. At most one row per possession is Active (R4).

`Possession` gains `loans(): HasMany`, ordered by `started_on` then `id`, and `activeLoan(): HasOne`.
`Contact` gains `possessionLoans(): HasMany`. The loan has `possession()`, `contact()` (with trashed),
`conditionOut()`, `conditionBack()`, `departureEvent()` and `returnEvent()`.

## PossessionLoanDirectionEnum

| Case | Value | Verb on the row | Summary |
| --- | --- | --- | --- |
| Lent | lent | Lent to | ":name has had it since :date" |
| Borrowed | borrowed | Borrowed from | "You have had it from :name since :date" |

## PossessionLoanStatusEnum

| Case | Value | Label on the form | Badge |
| --- | --- | --- | --- |
| Planned | planned | Planned | Planned |
| Active | active | Active | With them now, or Overdue (derived) |
| Returned | returned | Returned | Returned |

## Derived, never stored

- **Overdue**: Active and `due_on` before the user's today (R2).
- **Due line, length, "2 loans, 1 lent out", section count**: computed by the view model; counted
  phrases through `trans_choice()`.

## Journal

Four `UserActionEnum` cases: creation, update, return, deletion (R13).
