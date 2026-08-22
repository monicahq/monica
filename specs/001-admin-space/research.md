# Phase 0 research: Admin Space

**Feature**: `001-admin-space` | **Date**: 2026-08-21

Every unknown in the Technical Context is resolved here. Each entry states what was chosen,
why, and what was rejected. The evidence is the codebase as it stands on branch
`2026-08-20-admin-space`.

## 1. How the administrator capacity is held

**Decision**: a dedicated `administrators` table, with an `Administrator` model holding
`user_id` (unique), `granted_by_user_id` (nullable) and timestamps. `User` gains an
`administrator()` relation and an `isAdministrator()` method. Withdrawing the capacity deletes
the row.

**Rationale**:

- The spec's key entity requires the capacity to carry who granted it and when (FR-009,
  Key Entities). A table gives both without adding three nullable columns to `users`, which
  already carries twenty five.
- FR-004 forbids any self-service route to the capacity. `User::$fillable` is a long list that
  several actions mass assign into. Keeping the flag out of that table removes the whole class
  of mistake rather than relying on a reviewer noticing.
- Listing who holds it (FR-009) is one query on a table with as many rows as there are
  administrators, not a scan of `users`.

**Alternatives considered**:

- A boolean `is_administrator` on `users`. Rejected: no provenance, and it puts the most
  dangerous flag in the product inside the most mass assigned table.
- A roles and permissions package (for example `spatie/laravel-permission`). Rejected: the
  product has exactly one capacity with no hierarchy, and a package would add a dependency, five
  tables and a vocabulary the codebase does not otherwise use. Constitution principle II.

## 2. How the admin space refuses everybody else

**Decision**: one middleware, `EnsureUserIsAdministrator`, aliased `administrator`, wrapping the
whole admin route group. It calls `abort(404)` when there is no signed in user, when the user
holds no `administrator` row, or when the user is not active. The group does **not** carry the
`auth` middleware.

**Rationale**: FR-002 and SC-005 require the refusal to be indistinguishable from an address
that does not exist. `auth` redirects a guest to the sign in screen, which tells the guest the
address is real. A flat 404 for every case tells nobody anything. Checking `is_active` in the
same place covers the edge case where the capacity is held by somebody who can no longer sign
in.

**Alternatives considered**:

- A gate or a policy. Rejected: a policy answers per model, and what is being protected here is
  a whole area including screens with no model behind them (the overview).
- `auth` plus `administrator`. Rejected for the redirect it produces, as above.

## 3. Deactivation reaching a session that is already open

**Decision**: a second middleware, `EnsureUserIsActive`, aliased `active`, added to the existing
authenticated group in `routes/web.php`. It signs the user out, invalidates the session and
sends them to the sign in screen when `is_active` is false.

**Rationale**: FR-023 requires deactivation to take effect on a session already open. The
session driver defaults to `database` (`config/session.php`), so deleting the user's rows in
`sessions` would work on this instance, but not on an instance configured with the file, cookie
or redis driver. A middleware check is driver independent, costs nothing (the user is already
resolved on the request) and is straightforward to test with `actingAs`.

**Alternatives considered**:

- Deleting from the `sessions` table inside the deactivate action. Rejected: silently does
  nothing on three of the five drivers Laravel ships.
- Laravel's `AuthenticateSession`. Rejected: it guards against a password change, not against a
  flag being flipped by somebody else.

## 4. The record of administrator actions

**Decision**: a new table `administrator_logs`, model `AdministratorLog`, queued job
`LogAdministratorAction`, and enum `AdministratorActionEnum`. The model refuses updates and
deletes in `booted()`, exactly as `App\Models\Log` does, throwing `LogIsAppendOnly`.

**Rationale**: FR-031 requires operator activity to stay out of the per account journal a user
reads, and FR-030 requires it to be append only. Reusing `logs` would fail the first and
`Log` already demonstrates how to enforce the second. Mirroring `LogUserAction` (queued, on the
`low` queue, reading the IP address in the constructor) keeps one way of doing this in the
codebase.

**Shape**: `user_id` (nullable, the administrator), `user_email` (the address at the time, or
`Log::SYSTEM` when the console acted), `action`, `parameters`, `account_id` (nullable, so
FR-029 can filter by account), a nullable `subject_type` and `subject_id` morph, `ip_address`,
timestamps.

**Alternatives considered**:

- A `source` column on `logs` separating operator entries. Rejected: every screen that reads
  `logs` would have to remember to exclude them, and one forgotten `where` shows a user
  something an operator did to them.

## 5. Attributing a moderation decision

**Decision**: `ApproveMarketplaceListingVersion` and `RejectMarketplaceListingVersion` take a
`User $administrator` instead of a `string $moderator`. They keep writing a name into
`marketplace_listings.moderated_by` (from `$administrator->name`) and dispatch
`LogAdministratorAction` with the user.

**Rationale**: the spec assumes the public attribution stays a name rather than a foreign key,
and the column already is one. The action still needs the user for the operator record (FR-016,
FR-028), so passing the user and deriving the name leaves one source of truth. Neither action
has a caller outside its own test, so the signature change is contained.

**Alternatives considered**:

- Keeping the string and adding an optional user. Rejected: two parameters that mean the same
  person, and a caller can make them disagree.
- Logging from the controller. Rejected by constitution principle I.

## 6. Two administrators deciding the same submission at once

**Decision**: both moderation actions open their transaction with
`MarketplaceListingVersion::query()->whereKey(...)->lockForUpdate()->first()` before validating
the status. The same pattern guards the last administrator rule: `WithdrawAdministratorAccess`
counts inside a transaction with `lockForUpdate()` on `administrators`.

**Rationale**: the actions today read the status, then write in a transaction. Between the two,
a second request can pass the same check, so both decisions apply and the second silently wins.
The row lock makes the second request read the status the first committed and throw
`ListingIsNotAwaitingModeration`, which is what the acceptance scenario and the edge case ask
for. The same race sinks FR-007 when two withdrawals arrive together.

**Portability**: `lockForUpdate()` compiles to `FOR UPDATE` on MySQL and PostgreSQL and is a
no operation on SQLite, which serialises writers anyway. No driver specific SQL, so the
constitution's three database rule holds.

## 7. Telling the author about a decision

**Decision**: two mailables, `MarketplaceListingApproved` and `MarketplaceListingRejected`, two
new `EmailType` cases, dispatched from inside the two moderation actions through the existing
`SendEmail` job on the `high` queue. The recipient is the user on the listing's account.

**Rationale**: the spec assumes no new channel is introduced. `SendEmail` is what the
application already uses, it records an `EmailSent` row, and it handles the Resend and plain
Laravel paths. Dispatching from the action means a decision taken from anywhere sends the
email.

## 8. Searching for an account

**Decision**: search `users` on lowercased `LIKE '%needle%'` over `email`, `first_name` and
`last_name`, using `whereRaw('lower(...) like ?')`, and page with `simplePaginate`.

**Rationale**: FR-018 asks for full email, partial email and partial name.
`MarketplaceViewModel` already establishes `whereRaw('lower(col) like ?')` in this codebase, for
the documented reason that MySQL and SQLite collations and PostgreSQL disagree about case.
`simplePaginate` issues no `COUNT(*)`, which is the query that would hurt on the hundred
thousand account instance in SC-009, and the marketplace already uses it.

**Known trade off**: a leading wildcard cannot use an index, so the search is a scan. At a
hundred thousand users that is acceptable on every supported database. Full text search or a
trigram index is the answer if an instance outgrows it, and both are database specific, so
neither belongs here. This is recorded rather than solved.

## 9. Where the numbers on the overview come from

**Decision**: aggregates run when the screen is opened, inside the overview view model. No
cache, no summary table.

**Rationale**: the spec assumes figures are computed at open and puts trends out of scope. The
eight figures in FR-032 are seven `count()` calls and one `sum()`. The two that scan
(`sum(storage_used_in_bytes)`, and the count of users who signed in over the last seven days)
are cheap enough at the stated scale, and `last_login_at` and `created_at` take indexes if they
ever are not.

## 10. Designating the first administrator

**Decision**: an Artisan command `monica:make-administrator {email}`, class
`MakeAdministratorCommand`. It grants the capacity to an existing user by email address, refuses
politely when no such user exists, says so when they already hold it, writes an
`AdministratorLog` entry attributed to `Log::SYSTEM`, and prints a confirmation.

**Rationale**: FR-006 requires a route outside the application that stays available so an
instance with no administrator can be recovered. The console is that route. Command names are
kebab-case and commands confirm on success, per the code style rules. `LocalizeCommand` is the
shape to follow.

## 11. Keeping the admin space visually separate

**Decision**: a fourth layout, `resources/views/layouts/admin.blade.php`, alongside `account`,
`shell`, `guest` and `marketplace`. It carries its own bar, marked as the admin space, its own
navigation across the five screens, no dock, and no link back into a vault.

**Rationale**: FR-010 asks for structural and visual separation. The codebase already answers
"a different kind of screen" with a layout rather than with conditionals inside one, and there
are four precedents.

## 12. Where the screen assembles what it shows

**Decision**: view models under `app/ViewModels/Admin/`, querying the database, following
`LogsViewModel` and `MarketplaceViewModel`.

**Conflict, recorded rather than resolved**: the constitution states that view models "MUST NOT
query the database". The view models in this repository do (`LogsViewModel` reads
`$user->account->logs()`, `MarketplaceViewModel` reads `MarketplaceListing::query()`), so the
sentence describes an intent the codebase does not hold to. This plan follows the codebase, and
the discrepancy is raised in the Constitution Check of `plan.md` for the project owner to settle,
either by amending the constitution or by moving the queries into the controllers across the
whole application. It is not settled here, because settling it in one feature would leave the
codebase inconsistent either way.

## 13. Query strings

**Decision**: the account search term and the two filters on the administrator log travel in the
query string (`?query=`, `?administrator=`, `?account=`).

**Rationale**: the code style rules discourage query strings without a good reason. An email
address in a path segment is a poor fit (the `@` and the dots), a search that cannot be linked
to is a search nobody shares with a colleague, and `routes/web.php` already documents the same
reasoning for the marketplace facets.
