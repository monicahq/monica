# Phase 0 research: API keys and logs endpoints

Everything here was settled by reading this codebase and the beaver codebase the request pointed at.
Where the two disagree, this codebase wins, and the entry says so.

## 1. Where the endpoints live in the URL space

**Decision**: `/api/v1/api-keys`, `/api/v1/api-keys/{apiKey}`, `/api/v1/logs`, `/api/v1/logs/{log}`.
The controllers sit flat under `app/Http/Controllers/Api`, as `ApiKeyController` and `LogController`.

**Rationale**: beaver groups both under `administration` (`/administration/api`,
`/administration/logs`), and this application has nothing that word describes. Its administration
namespace is the instance wide administrator space, which governs moderation and storage and never
the contents of an account, so borrowing the word would name the wrong thing. The existing `Api`
namespace here is flat (`Api/MeController`, `Api/StatusController`) even though the equivalent web
screen for the profile sits several folders deep, so flat is the precedent. The two names also match
the two sidebar groups the request asked for, "API keys" and "Logs".

**Alternatives considered**: mirroring the web tree (`Api/Settings/Account/Security/ApiKeyController`),
which the `api-writer` skill asks for. Rejected because that depth describes where a screen sits in
the settings tree, not what the resource is, and `MeController` already declined to do it.

## 2. How a list is paginated

**Decision**: page based pagination, on both lists, ordered by descending id. A caller moves with
`page` and sizes with `per_page`, which defaults to twenty five and is clamped between one and one
hundred. The response carries the rows under `data` and nothing but `page`, `per_page` and `total`
under `meta`.

**Rationale**: the reference already promises exactly this, under Getting started, and says why it
was written down before any list existed: "The convention is written down first so that the ones that
arrive next all follow it, rather than each inventing its own." These are the ones that arrive next.
Nothing implements it yet, so it could have been changed, but a convention published to callers is
still a promise, and the reason to break it turned out to be weaker than the reason to keep it.

The envelope is built by hand in each controller rather than left to the framework, because Laravel's
paginated resource response carries `current_page`, `from`, `last_page`, `links`, `path`, `to` and
`total`, which is a different shape from the one the reference promises. Three lines in each of two
controllers is cheaper than a resource collection class per resource, and repeating a few short lines
is what this project asks for over extracting a helper.

Ordering is by id rather than by `latest()`, which orders by `created_at`. Two rows written in the
same second are otherwise in no defined order, and a page boundary falling between them can show one
row twice or skip it.

**What this costs**: `total` is a count query per list, and a deep page has to be counted past. For
one person's keys, of which there are a handful, and one account's journal, this is not measurable.
It would be at a scale this product does not have, and cursor pagination is the answer if it ever
does. SC-007 of the spec was written on the strength of that argument and has been amended to say
what is actually being promised.

**Alternatives considered**: cursor pagination, which is what this plan chose before the published
convention was found, and which beaver does not do either (it offsets). It reaches a deep page
without counting past everything before it, and it cannot show a row twice when rows are being
written while somebody pages. It was rejected because it would have meant rewriting a section of the
reference that exists precisely to stop each endpoint inventing its own paging, and because it cannot
answer `total`.

**Observed while researching, deliberately not fixed here**: `LogsViewModel::logs()` cursor paginates
on `latest()` alone, which orders by a column that is not unique, so a page boundary falling between
two entries written in the same second can duplicate one or skip it. It is a screen this feature does
not touch, and it is worth its own pull request.

## 3. Where the page size limit is configured

**Decision**: two new keys in `config/monica.php`, `api_default_items_per_page` (twenty five) and
`api_maximum_items_per_page` (one hundred), each reading an environment variable the way every other
value in that file does. Both numbers are the ones the reference already promises.

**Rationale**: beaver reads `config('app.maximum_items_per_page')`, which does not exist here, and
`config/app.php` is a framework file rather than the product's. `config/monica.php` is where this
product's own settings live, its keys are flat and snake_case, and the constitution's note that
hosted and self hosted differ only in quotas and rate limits makes a page ceiling exactly the kind of
thing an operator should be able to move.

**Alternatives considered**: a private constant on each controller, which is what the view models do
for screen page sizes. Rejected because a screen's page size is a design choice and an API's is an
operator's.

## 4. How the create endpoint learns which key it just minted

**Decision**: `CreateApiKey::execute()` returns Sanctum's `NewAccessToken` instead of the plain text
string. The two existing call sites, the web `ApiKeyController` and the API `LoginController`, read
`->plainTextToken` and are otherwise unchanged. The new endpoint reads `->accessToken` for the id and
the timestamps, and `->plainTextToken` for the value it hands back once.

**Rationale**: the create response has to describe the key (its id, its name, when it was made) and
carry its secret, and `NewAccessToken` is the object Sanctum already hands back holding both. The
action keeps one job and one return, and both call sites move in the same change, so nothing can
drift.

**Alternatives considered**: re-reading the newest token after the action, which is what beaver does
(`$request->user()->tokens()->latest()->first()`). Rejected because it is a second query for
something the first one already had, and because `latest()` on `created_at` can pick the wrong row
when two keys are minted in the same second. A second action beside the first was also rejected: two
actions for one operation is what Principle I exists to prevent.

## 5. Where the secret appears in the create response

**Decision**: the resource never carries the secret. The create endpoint returns the `api_key`
resource under `data` and the plain text value as a sibling `token` key at the top level, with a 201
status.

**Rationale**: the login endpoint already answers with a top level `token`, so a client that knows
how to read one credential out of this API knows how to read the other. Keeping it out of the
resource means the resource can be used by the list and the read endpoints with no flag saying
"secret suppressed here", which is the kind of conditional that eventually leaks.

**Alternatives considered**: beaver puts the value inside `attributes.token` and also at the top
level through `->additional()`, so its create response carries the secret twice. Rejected for the
obvious reason. Naming the field `key` rather than `token` was considered, and rejected because the
login endpoint has already named this exact thing `token`.

## 6. Which language a journal sentence comes back in

**Decision**: the authenticated API group gets the `set.locale` middleware, and `SetLocale` is made
safe to run without a session by asking `$request->hasSession()` before reading from it.

**Rationale**: a journal entry's description is a translated sentence built by the `Log` model
through `__()` and `trans_choice()`. Without this, every API caller gets the instance default
language regardless of the language they chose, which Principle V does not allow. The middleware
already prefers the session, then the user, then the fallback, and an API request simply has no
session, so the fix is one guard. Doing it here rather than later means every endpoint added after
this one inherits it.

**Alternatives considered**: returning only `action` and `parameters` and letting the client build
its own sentence. Rejected against FR-012, and because it would make every client reimplement a
translation table that already exists. Setting the locale inside the log controller was rejected
because the next endpoint that returns a translated string would have to remember to do it too.

## 7. What a journal entry shows through the API

**Decision**: `author`, `action`, `parameters`, `description`, `ip_address`, `source` and
`created_at`. The `changes` column, the `vault_id` and the raw `user_id` are left out.

**Rationale**: the first five are what the settings screen shows on a row, and the screen's own note
says the address is what makes the journal worth reading, so leaving it out would hand the API less
than the screen. `source` says which interface the action came through, which the constitution names
as part of what the changelog answers, and it becomes meaningful the moment these endpoints exist,
since actions taken through the API will start appearing in the journal marked as such.

`changes` is held back on purpose. The `Log` model's own docblock says a field marked sensitive
records that it changed and not what to, "until the specification settles how those values may be
kept", and the constitution's governance section says an open question in the spec is answered in
the spec and not by an implementation choice inside a pull request. Adding the column later is
additive, so nothing is lost by waiting.

**Alternatives considered**: beaver's `LogResource` exposes `user_name`, `action`, `parameters` and
`description`. Its shape was the starting point; `ip_address` and `source` are added because this
product's journal carries them and its screen leans on them.

## 8. What a caller can reach

**Decision**: keys are scoped through `$request->user()->tokens()`, and journal entries through
`$request->user()->account->logs()`. Both use `findOrFail`, so anything outside that scope is a 404
with no branch to write.

**Rationale**: a key belongs to the person it acts as, and there is nobody else's to see. The
journal deliberately belongs to the account rather than the person: `LogsViewModel` explains why, and
the reason survives into the API. Under one person per account the two sets differ in exactly one
place, the entries the application wrote on its own with nobody behind them, and scoping to the user
would hide precisely the entries nobody watched happen.

**Alternatives considered**: beaver scopes its logs by `user_id`, because beaver accounts hold
several people. Not applicable here, and following it would have hidden the system entries.

## 9. Rate limiting and authentication

**Decision**: all six routes join the existing `['auth:sanctum', 'throttle:60,1']` group inside the
`v1` prefix. Nothing new is configured.

**Rationale**: FR-016 and FR-017 ask for the same terms as the rest of the API, and the group already
states them. The tighter `throttle:6,1` on login and registration is there because those two take a
password, and none of these do.

## 10. Keeping the reference honest

**Decision**: two new definition files, `resources/docs/api/05-api-keys.php` and
`resources/docs/api/06-logs.php`, each returning a group name and its sections in the order list,
read one, create, delete.

**Rationale**: `ApiDocsViewModel` loads `resources/docs/api/*.php` in filename order and turns each
into a sidebar group, so the numbering is the sidebar order and the next two numbers put API keys
then Logs directly after the profile, which is the order the request asked for.
`ApiDocsViewModelTest` compares documented endpoints against registered routes in both directions,
so the `path` in a definition file has to be written exactly as the route registers it, parameter
names included (`/api-keys/{apiKey}`, not `/api-keys/{id}`). `rateLimit` has no default in
`ApiDocsSection` and must be given on every section.

**Alternatives considered**: none. This is how the portal works, and the test enforces it.

## 11. Which tests have to move

**Decision**: two new feature test files, plus edits to `tests/Unit/Actions/CreateApiKeyTest.php`
for the new return type and to `tests/Feature/Controllers/App/Docs/ApiDocsControllerTest.php` for the
two new sidebar groups. `SetLocale` gets a case for a request with no session.

**Rationale**: Principle III, and the fact that the route to reference sync test fails on its own the
moment a route is added without documentation, which is the safety net that makes step ten hard to
forget.
