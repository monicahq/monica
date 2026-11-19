# Research: Flag usage metrics

## R1. Where a flag being asked is observed

**Decision**: one listener on Pennant's `FeatureRetrieved` event, kept when its scope is an `Account`
and its value is not `false`.

**Rationale**: `Decorator::get()` (Pennant 1.26) dispatches the event on every check, the ones
answered from its in-memory cache included, with the feature, the scope and the value. Flags are
asked in at least eight places (`EnsureFlagIsOn`, `FlagState`, `AddressBookBackend`, `ImportVCard`,
four controllers), most through `$account->features()->active()`, so only the event sees all of them.
"Not `false`" is Pennant's own definition of active. A resolver asks `Feature::for(null)`, and the
admin screen does too; their scope is null and they are skipped, which is FR-004.

**Rejected**: wrapping `FlagState` and the middleware (misses every direct call); the Nightwatch
docs example that adds every flag's state on every request (exposure, not use, and refused in the
clarification of 2026-11-19).

## R2. One record per request, job and command

**Decision**: `Context::push('flags', $name)` unless `Context::stackContains('flags', $name)`, and
`Context::dehydrating(fn (Repository $context) => $context->forget('flags'))`.

**Rationale**: Nightwatch 1.30.6 serialises `Context::all()` (visible context only) on requests,
job attempts, commands and scheduled tasks (`Concerns\RecordsContext`). Laravel copies the request's
context into every job it queues and hydrates it on `JobProcessing`, so without the forget a job
would name flags its request used. `Repository::dehydrate()` works on a copy, so forgetting there
leaves the request's own record intact. The repository is a scoped binding, reset by the worker
between jobs, so one job's flags do not reach the next. The stack check gives FR-003's "named once".

**Rejected**: hidden context (Nightwatch does not record it); a key per flag (`flags.shared-albums`),
which reads worse in Nightwatch and gives no benefit.

## R3. Counting the accounts a flag is on for

**Decision**: one query per flag on the `features` table, in a new `FlagState::accountsOn()`:
on = stored answers that are `true` + (instance answer on ? accounts − stored answers : 0).

**Rationale**: Pennant's database driver stores an account's answer as scope
`App\Models\Account|{id}` with a JSON value, writes it the first time the account is asked, and
`activateForEveryone()` rewrites every stored row. An account never asked gets the instance answer,
which is what `Feature::for(null)->active()` returns, `mcp-server` included (FR edge case).
Every flag is asked through an account or through nobody (`flags.md`), so the stored answers of
accounts are the rows whose scope is not the null scope. `DestroyAccount`, the only place an account
is deleted, forgets its answers first, so no row outlives its account. Matching the scope against
`accounts.id` instead needs string concatenation, written differently on each database, and `LIKE`
on a backslash is an escape in MySQL. The `(name, scope)` unique index serves the filter (SC-004).

**Rejected**: asking `active()` for every account (writes up to 100,000 rows the first time, fires
100,000 events, and fails SC-004); a stored counter (a second copy of the answer to keep in step).

## R4. Wording

**Decision**: `trans_choice('On for :on of :count account|On for :on of :count accounts', $total,
['on' => $on])` on the screen. The command prints `on for {$on} of {$total} accounts`, in English
like its existing lines.

**Rationale**: the noun agrees with the total, so the total is the count `trans_choice()` picks on,
and every segment interpolates `:count` as `code-style.md` requires. Commands are not translated
anywhere in the application today.

## R5. Reading usage in Nightwatch

**Decision**: nothing to build. The operator reads the `flags` key on each request, job or command
next to the user Nightwatch already records (`Nightwatch::user()` sends the id only).

**Open**: the Nightwatch requests page does not say whether a list can be filtered or grouped by a
context value. SC-003 depends on it and is checked by hand against a deployment (quickstart in
plan.md). Sampling below 1.0 makes the figures a sample, as the spec states.
