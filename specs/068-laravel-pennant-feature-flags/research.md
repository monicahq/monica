# Research: Feature flags

Read in the Pennant v1.26.0 source (`src/Drivers/DatabaseDriver.php`, `src/Drivers/Decorator.php`).

## R1. Package and store

- **Decision**: `laravel/pennant ^1.26` in `require`, `database` store, also in tests.
- **Rationale**: v1.26.0 requires `illuminate/* ^13.0`. The database store is shared by web servers and
  workers (FR-005), and tests on it run on SQLite, MySQL and PostgreSQL (SC-005).
- **Rejected**: `PENNANT_STORE=array` in `phpunit.xml`; it hides how the database store behaves (R3).

## R2. Asking a flag

- **Decision**: `Account` uses Pennant's `HasFeatures`; every check reads
  `$account->features()->active(Flag::class)`, never the default scope.
- **Rationale**: the default scope is the signed in `User`, and nobody is signed in in a job, a command
  or a scheduled sweep. Naming the account gives every surface one answer (FR-002), the same reason
  `User::authorize()` wraps `Gate::forUser()`. A flag's `resolve(Account $account)` is typed, so a
  check through the default scope fails loudly instead of answering for the wrong record.
- **Rejected**: `Feature::resolveScopeUsing()` returning `auth()->user()?->account`: null in a job.

## R3. "Every account" is the flag's row for the null scope

Facts: `DatabaseDriver::get()` stores the resolved value the first time a scope is asked.
`activateForEveryone()` calls `setForAllScopes()`, an `UPDATE` of rows that already exist, so an
account asked for the first time afterwards still resolves through `resolve()`. A resolver typed
`Account` is not called for a null scope: Pennant returns `false` and stores it.

- **Decision**: every flag's `resolve(Account $account)` returns `Feature::for(null)->active(...)` for
  itself. Switching for every account writes the null scope row, then `activateForEveryone()` or
  `deactivateForEveryone()` rewrites every stored row. Last switch wins: a later switch for one
  account overrides it.
- **Rationale**: covers accounts created later (clarification Q3), defaults to off with nothing stored
  (FR-001), and keeps every decision in Pennant's one table, which `pennant:purge` empties (FR-011).
- **Rejected**: an `InstanceSetting` per flag (a fixed enum of typed settings, one case per key).

## R4. Declaring a flag

- **Decision**: one class per flag in `app/Features` (constitution 2.5.0), named by `#[Name('kebab-case')]`
  so the stored name survives a rename. `Feature::discover()` in `AppServiceProvider::boot()` registers
  them, so `Feature::defined()` lists them before anyone asks. The test flag is
  `tests/Fixtures/Features/ExampleCapability.php`, discovered by the tests that need it.
- **Rejected**: closures in a provider: no class to find, rename or delete with the capability.

## R5. Absent means not found

- **Decision**: a middleware `EnsureFlagIsOn`, aliased `flag`, aborts 404 unless `FlagState::isOn()`
  says yes: the account's answer, or for a visitor the answer for every account, so a public page
  such as the marketplace can sit behind a flag. An MCP tool gates itself in `shouldRegister()`.
- **Rejected**: Pennant's `EnsureFeaturesAreActive`: asks the default scope and answers 400.

## R6. Who changed what

- **Decision**: four `AdministratorActionEnum` cases, for one account and for every account, on and off.
  `LogAdministratorAction` with a null administrator attributes a command switch to the console, as
  `MakeAdministratorCommand` already does.

## R7. Deleting an account

- **Decision**: `DestroyAccount` calls `Feature::for($account)->forget(Feature::stored())`. The scope is a
  string (`App\Models\Account|<id>`, no morph map), so no foreign key cascades.

## R8. Testing

- Pennant caches answers per request: call `Feature::flushCache()` between acting and asserting.
- `Feature::define(ExampleCapability::class, true)` forces a value for one test (FR-006).
