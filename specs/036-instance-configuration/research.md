# Research: Instance Configuration

## How a stored value reaches the code that reads it

**Decision**: a service provider reads the stored settings at boot and calls `config()->set()` for each, so every existing `config('monica.*')` call site keeps working untouched.

**Rationale**: 78 call sites read `config('monica.*')` today and 13 read `config('mail.*')`, `config('services.*')` or `config('cashier.*')`. Overriding at boot changes none of them, which is the whole difference between a small feature and a sweep through the application.

**Alternatives considered**: a `Setting::get()` helper called at each site (91 edits, and a missed one silently keeps the deployment value); a custom config repository (replaces a framework class to gain nothing this does not).

## How a change reaches a long-lived process within a minute

**Decision**: the settings are read through the cache with a sixty second lifetime. A write forgets the entry, so the next web request is immediate. A `JobProcessing` listener re-applies them before each job, so a queue worker is at most sixty seconds stale.

**Rationale**: `compose.yaml` runs `queue:work` with no `--max-time`, so a worker boots once and would otherwise hold its configuration for ever, which is the restart FR-003 forbids. The scheduler needs nothing: `Dockerfile` runs `schedule:run` from cron every minute, so it boots fresh anyway. The default cache store is the database, so the entry is shared across processes rather than per process.

**Alternatives considered**: reading the table on every use (a query per setting per request, for values that change a few times a year); a broadcast invalidation signal (a moving part, and a worker that misses it is stale for ever rather than for a minute).

## How the deployment value survives being overridden

**Decision**: before overriding a key, the provider stashes what configuration already held under a parallel runtime key. That stashed value is what FR-002 shows, what FR-005 reverts to, and what FR-014 names when it disagrees with the stored one.

**Rationale**: once `config()->set()` has run, the value the deployment supplied is gone, and the screen cannot read it back. Reading `env()` instead is forbidden outside `config/` by the constitution and would miss a value that came from a config default rather than a variable. One concept covers all three requirements: what configuration held before anything stored touched it.

**Alternatives considered**: re-reading the config files (they call `env()`, so this reintroduces the forbidden call); recording the deployment value in the table (it would go stale the moment the deployment changed).

## Storing a secret

**Decision**: one `value` column, encrypted for every setting rather than only for secrets.

**Rationale**: a conditional cast means the model has to know each setting's kind before it can read its own column. Encrypting a few dozen short rows costs nothing measurable and removes that ordering problem. Nothing queries a setting by value.

**Alternatives considered**: a separate encrypted column for secrets (two columns, one of them null on most rows, and a branch at every read).

## Proving a credential, and refusing the screen

**Decision**: reuse Laravel's `password.confirm` middleware on the write routes exactly as `routes/web.php` already does for the security screen, inside the existing `administrator` group. Nothing new is built.

**Rationale**: the mechanism, its session fact, its window (`auth.password_timeout`) and its screen all exist. FR-012 asks for one proof covering the window it lasts, which is what the middleware already does. The API is exempt, which is what that feature already decided, so the token needs nothing added.

## Testing an outbound service before it is saved

**Decision**: one action per provider behind a shared contract, each exercising the provider with the submitted values rather than the live configuration. The controller runs it and saves only on success.

**Rationale**: FR-015 is what stops this screen cutting off the password reset and sign in links, so the test has to run against the values being saved, not the ones already in force. The three providers share nothing but the shape of the answer, which is why the contract holds only that shape.

**Alternatives considered**: one action with a `match` over three unrelated bodies (harder to test one provider in isolation); saving first and testing after (the failure mode this exists to prevent).

## Where the catalogue lives

**Decision**: an enum naming every setting, carrying its group, its kind, the configuration key it overrides, and whether the deployment fixes it. A second enum names the kinds.

**Rationale**: the catalogue is code, not data. An operator picks a value for a setting the product defines; they do not invent settings. This is how `ApiKeyScopeEnum` and `AdministratorActionEnum` already work, and it makes the screen, the validation, the API and the tests all read from one list.

## Reaching the settings over the API

**Decision**: an `admin` prefix in `routes/api.php` guarded by the existing `EnsureUserIsAdministrator` middleware, with a resource class per the API conventions.

**Rationale**: this feature adds the first administrator endpoints. The middleware reads the authenticated user and holds no session assumption, so it applies to a token request unchanged.

## A setting the product no longer carries

**Decision**: a stored row whose name is absent from the enum is shown in a group of its own, with its value and no control but removal. A rename is a migration that moves the row's name.

**Rationale**: Principle VIII forbids dropping the data behind something that stops applying. Reconciling against the enum on read costs one array lookup and needs no extra column.
