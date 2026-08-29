# Phase 0 Research: Sent emails endpoints

**Feature**: `specs/005-sent-emails-api` | **Date**: 2026-08-29

The spec left nothing marked NEEDS CLARIFICATION, so this phase resolved the technical unknowns
instead: what the existing API already decided, and where the two API skills have fallen out of step
with the code they describe.

## 1. What the API already decided

### Decision: mirror `Api\LogController` in every respect

**Rationale**: The journal endpoints are the closest sibling. They are read only, account scoped,
paginated, and shipped in the feature immediately before this one (`specs/004-api-keys-logs`). Every
choice below is theirs, and copying them keeps a caller parsing one shape across the whole API.

| Aspect | What the code does | Where |
| --- | --- | --- |
| Envelope | `type` / `id` / `attributes` / `links.self` | `LogResource`, `ApiKeyResource` |
| `id` | cast to string | both resources |
| Timestamps | Unix integers via `->timestamp`, `?->timestamp` when nullable | both resources |
| List body | hand built `{'data': [...], 'meta': {page, per_page, total}}` | `LogController::index` |
| Page size | `max(1, min($requested, monica.api_maximum_items_per_page))` | `LogController::index` |
| Defaults | 25 default, 100 maximum | `config/monica.php:301,303` |
| Scoping | `$request->user()->account->relation()` then `findOrFail` | `LogController::show` |
| Route prefix | `api.v1.` | `routes/api.php` |
| Middleware | `auth:sanctum`, `throttle:60,1`, `set.locale` | `routes/api.php` |

**Alternative considered**: `EmailSentResource::collection($paginator)` returning an
`AnonymousResourceCollection`, which is what the `api-writer` skill asks for and what Laravel does by
default. Rejected: it emits Laravel's own paginator envelope, with `links`, `first_page_url`, `from`,
`to` and the rest. `LogController` carries a comment saying why it does not, and the published
reference promises `page`, `per_page` and `total` and nothing more. Emitting more now would be a
promise nobody asked for and could not be withdrawn later without breaking somebody.

### Decision: order by `sent_at` descending, then by `id` descending

**Rationale**: The screen orders by `sent_at` (`EmailsSentViewModel::emailsSent()`), and the spec asks
for newest sent first. `sent_at` is nullable and two emails can share a timestamp to the second, so
`id` breaks the tie. Without a tiebreaker the order is undefined between equal rows and an email can
appear on two pages or on neither, which SC-003 forbids.

**Alternative considered**: ordering by `id` alone, as the journal does. Rejected: it would disagree
with the screen for any email whose record was written late.

## 2. Where the delivery state should live

### Decision: an `EmailDeliveryStatusEnum`, derived by an accessor on the model

**Rationale**: The rule for the three states exists today, inline in
`resources/views/app/settings/account/logs/_email-sent-row.blade.php`, as a `match` over the three
timestamps with bounced winning over delivered. FR-003 requires the API to decide it identically. One
rule written twice is one rule that will be read two ways, so it moves onto the model as
`delivery_status`, and both the screen and the resource read it from there.

Cases and values: `Bounced` / `bounced`, `Delivered` / `delivered`, `Sent` / `sent`. Enum naming
follows `.claude/rules/code-style.md`: singular, PascalCase cases, `Enum` suffix.

**The enum carries no words.** It gets no `label()` and does not implement `Translatable`. The screen
keeps its own `match` for the badge tone and the wording, because "On its way" is copy belonging to
that screen and the tone belongs beside it. `TranslatableEnumsTest` only binds enums that declare a
`label()` or a `description()`, so an enum with neither is correctly exempt rather than skirting a
rule.

**Alternative considered**: deriving the state in the resource and leaving the blade partial alone.
Rejected: it is the duplication FR-003 exists to prevent.

**Alternative considered**: giving the enum a `label()` and having the partial use it. Rejected: the
partial needs the tone and the word together, so it would still hold a `match`, and the enum would
acquire nine locales of translation for a word the API never emits.

### Note on the third value

The screen says "On its way"; the API says `sent`. The API names the fact, that the email left and
nothing has come back, and the screen says it in words a person reads. Naming the enum case `Sent`
rather than `OnItsWay` keeps the wire value stable if the screen ever rewords the badge.

## 3. Carrying the copy of the email on one endpoint only

### Decision: a conditional attribute keyed on the route, in one resource

**Rationale**: FR-007 wants `body` when one email is asked for, FR-008 forbids it in list rows, and
SC-005 wants a page whose size does not grow with the copy on it. `$this->when($request->routeIs('api.v1.emailsSent.show'), ...)`
expresses exactly that in one line, and `LogResource::collection(...)->resolve()` already resolves
against the current request, so it works on both paths without the controller passing anything.

**Alternative considered**: a second `EmailSentDetailResource`. Rejected: two classes that differ by
one key, and every future field added to one and forgotten in the other.

**Alternative considered**: a flag set by the controller, `EmailSentResource::make($email)->withBody()`.
Rejected: it puts state on a resource whose whole job is to be stateless, for no gain over reading
the route.

## 4. The reference, and what the two API skills get wrong

The reference at `/docs/api` is data, not prose: one PHP file per sidebar group under
`resources/docs/api`, loaded by `glob` in filename order, so a new group is a new file and nothing
else. `Tests\Unit\ViewModels\Docs\ApiDocsViewModelTest` compares the documented endpoints against
`routes/api.php` in both directions, so a route without documentation and documentation without a
route each fail the suite.

**Both API skills describe a structure this codebase does not have.** They were written against the
Beaver codebase this API was modelled on. `.claude/CLAUDE.md` puts the code that is already there
above the skills, so the code wins on every row below. This table is the justification, recorded once
here rather than argued again during implementation.

| The skill says | The codebase has |
| --- | --- |
| `App\Services\ApiDocumentation` | `App\ViewModels\Docs\ApiDocsViewModel` and `ApiDocsSection` |
| `Marketing\Docs\ApiDocsController` | `App\Http\Controllers\App\Docs\ApiDocsController` |
| `Tests\Unit\Services\ApiDocumentationTest` | `tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php` |
| `tests/Feature/Controllers/Marketing/Docs/ApiDocsControllerTest.php` | `tests/Feature/Controllers/App/Docs/ApiDocsControllerTest.php` |
| a `paginated($data, $path)` helper | no such helper, the `meta` block is written out in the file |
| a `permissions` key on a section | no such key, `ApiDocsSection` ignores it |
| `config('app.maximum_items_per_page')` | `config('monica.api_maximum_items_per_page')` |
| `route('api.collections.show', ...)` | the group prefixes route names with `api.v1.` |
| index returns `AnonymousResourceCollection` | index returns `JsonResponse` with a hand built envelope |
| owners, editors and viewers, and three permission tests | no roles at all, so those cases do not exist |

The last row matters most for the test plan. An account carries no role and holds exactly one person,
so the skill's cases 7, 9 and 11 have nothing to test. The compartment this feature has to prove is
the account boundary, and that is one test on each endpoint.

**Recommendation, out of scope for this branch**: both skill files, and the stale TODO in
`.claude/CLAUDE.md` saying there is no `Api` folder and no `routes/api.php`, should be corrected in a
separate change. Fixing them here would mix a documentation repair into a feature branch.

## 5. Things confirmed present, so nothing to build

- `Account::emailsSent()` exists (`app/Models/Account.php:178`). No relationship to add.
- `EmailSentFactory` exists, with a `withoutUser()` state for mail sent to an address belonging to
  nobody. No factory work beyond states for delivered and bounced.
- `tests/Unit/Models/EmailSentTest.php` exists, so the accessor test joins it rather than opening a
  new file.
- The three delivery timestamps are already cast to `datetime` on the model.
- The docs portal needs no route, no controller and no registration. The file is the registration.

## 6. Localization

No new user-facing string is introduced. The reference files under `resources/docs/api` are written
in English and do not go through `__()`, which is the existing choice for that surface, and the screen
keeps the badge wording it already has. `php artisan monica:localize` still runs as the checklist in
`.claude/CLAUDE.md` requires, to prove nothing went missing rather than to add anything.
