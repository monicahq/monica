# Research: Filters on the admin accounts list

## R1. Where the filters live

**Decision**: in `AccountsViewModel`, which already holds the search. Its constructor takes four more
strings and keeps each only when it is an offered value (FR-009), the way `SupportQueue` does.

**Rationale**: the accounts list has no API and no MCP tool, so nothing else reads the query.
`SupportQueue` is a separate helper only because three surfaces share it.

**Rejected**: an `AccountList` helper beside `SupportQueue`, a second class with one caller.

## R2. State

| State | Condition on `userIncludingDeleted` |
| --- | --- |
| active | exists, not trashed, `is_active` |
| deactivated | exists, not trashed, not `is_active` |
| deleted | exists, trashed |
| nobody | no row, trashed or not |

Same order as the `match` in `rows()`, so a filter and the printed state never disagree.

## R3. Access kind

**Decision**: an `accessKind` scope on `Account`, beside `accessKind()`, in the same precedence:
lifetime (a paid order), then granted (`access_granted_at`), then subscription (a valid `default`
subscription), then free (none of the three). Each kind excludes the ones above it.

**Rationale**: the PHP rule and its SQL twin sit ten lines apart, so a change to one is seen next to
the other. A valid subscription is written as `BillingFigures::validSubscription()` writes it
(`active()` or `onTrial()`); Cashier's `active()` scope already counts the grace period, as
`subscribed()` does.

**Rejected**: loading every account and calling `accessKind()`, which cannot paginate.

## R4. Flag

Pennant's answer for an account is the row stored for that account, else the row stored for nobody
(the resolver of every flag but `mcp-server` reads it, and `mcp-server` answers true there too).

**Decision**: `FlagState::whereOn(Builder $accounts, string $flag)`:

- flag on for nobody's scope: `whereNotExists` a row for the account with value `false`;
- otherwise: `whereExists` a row for the account with value `true`.

`accountsOn()` stays as it is: two counts on `features`, inside the Flags screen's own speed target
(spec 135). `FlagStateTest` asserts both give the same number (SC-005).

**The scope column** holds `App\Models\Account|{id}`. The prefix is read from
`Feature::serializeScope()` on an unsaved account, not written as a literal. The correlated
condition compares it to a string built from `accounts.id`: `CONCAT(?, accounts.id)` on MySQL and
MariaDB, `? || accounts.id` on SQLite and PostgreSQL, switched on the driver as
`RebuildOrderingKeys` already does. SQLite's own `concat()` needs 3.44, so `||` is the safe form.

**Rejected**: plucking the stored scopes into an id list, since Pennant stores an answer for every
account it is asked about and PostgreSQL refuses more than 65,535 bound values.

## R5. Joined

`created_at >= now()->sub…` for 7 days, 30 days, 12 months. No index is added: the list already sorts
on `created_at` today without one, and a period only narrows that scan.

## R6. Hosted only

The access value is dropped when `config('monica.self_hosted')` is true, so a copied link from a
hosted instance does nothing on a self hosted one (FR-002, FR-009).

## R7. Speed (SC-004)

Every filter is one `where`, `whereHas` or correlated `exists` on an index: `users.account_id`
(unique), `features (name, scope)` (unique), `subscriptions` and `stripe_orders (account_id,
stripe_status)`. Not
benchmarked at 100,000 accounts; the plan accepts that risk rather than seed a benchmark.
