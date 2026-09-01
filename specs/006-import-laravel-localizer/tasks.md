---

description: "Task list for locale resolution modelled on laravel-localizer"
---

# Tasks: Locale resolution modelled on laravel-localizer

**Input**: Design documents from `/specs/006-import-laravel-localizer/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/](contracts/)

**Tests**: included, and not optional here. FR-018 requires them and Principle III of the constitution
is marked non-negotiable. Research R12 records that the behaviour being replaced has no test at all
today, so there is no regression suite to lean on and every test below is written from the
specification rather than from what currently passes.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to
- Exact file paths in every description

## Path Conventions

Laravel application at the repository root. `app/` for application code, `config/` for configuration,
`bootstrap/app.php` for middleware registration, `tests/Unit` mirroring the application namespace and
`tests/Feature` for anything driven through a request.

## Story mapping note

The specification carries three user stories and two functional requirements, FR-013 and FR-014, that
belong to no story: they describe the JSON API. They are folded into User Story 1, whose promise is
that the product answers in the reader's language on every surface without a route asking for it. The
API is the surface where that promise is currently broken, so it belongs in the first increment rather
than the last. Research R11 has the detail.

---

## Phase 1: Setup

- [X] T001 Create branch `2026-08-31-import-laravel-localizer` from an up to date `main`, per the constitution's workflow gates. Done ahead of implementation, as `2026-08-31-laravel-localizer`
- [X] T002 Confirm `composer.json` and `composer.lock` are untouched by this feature and stay that way, since research R3 establishes that `$request->getLanguages()` removes the only reason to add a dependency

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: the resolver's skeleton, the source that always answers, and the writing half. Every user
story below adds a source to this class. Nothing in Phase 3 or later can start until this is done.

- [X] T003 Create `app/Helpers/LocaleResolution.php` with `declare(strict_types=1)`, the four typed constants from [contracts/locale-resolution.md](contracts/locale-resolution.md) (session key `locale`, cookie name `locale`, cookie lifetime one year in minutes, user attribute `locale`), and a private accessor reading `array_keys(config('monica.locales'))` so FR-007 holds with no second list
- [X] T004 Add `fromApplication(): string` to `app/Helpers/LocaleResolution.php`, returning `config('app.locale')`, non-nullable, so neither order can fail to produce an answer
- [X] T005 Add `remember(string $locale): void` to `app/Helpers/LocaleResolution.php`, writing the session key, queueing the cookie for one year and calling `app()->setLocale()`, all three in the one call FR-016 requires
- [X] T006 Create `tests/Unit/Helpers/LocaleResolutionTest.php` with `declare(strict_types=1)`, the `Tests\Unit\Helpers` namespace, `#[Test]` attributes and snake_case `it_` method names per `.claude/rules/testing.md`, covering `fromApplication()` and `remember()` writing all three places

**Checkpoint**: the resolver exists and can record an answer. No request resolves through it yet.

---

## Phase 3: User Story 1 - The interface keeps speaking the language I picked (Priority: P1) 🎯 MVP

**Goal**: every surface of the product answers in the language the reader chose or stored, with no
route opting in. Includes the API, whose locale middleware `6fa1e09` removed without replacement.

**Independent test**: pick a language from the switcher, then walk the signed in screens, the guest
screens and `docs/api`, and confirm each is in that language. Then call an API endpoint with a token
whose user prefers German and confirm the response is German.

### Tests for User Story 1

- [X] T007 [P] [US1] Extend `tests/Unit/Helpers/LocaleResolutionTest.php` with `fromSession()` and `fromUser()`: each returning its stored value, each returning null when nothing is stored, and each returning null rather than the value when what is stored is not a key of `config('monica.locales')`, which is FR-006
- [X] T008 [P] [US1] Extend `tests/Unit/Helpers/LocaleResolutionTest.php` with the two orders as they stand at this phase: `forWeb()` preferring a session choice over an account preference over the fallback, and `forApi()` preferring an account preference over the fallback
- [X] T009 [P] [US1] Create `tests/Feature/Localization/WebLocaleResolutionTest.php` asserting FR-008, that a guest screen, a signed in screen and `docs/api` are each resolved without naming a middleware, and that a session choice beats an account preference through a real request
- [X] T010 [P] [US1] Create `tests/Feature/Localization/ApiLocaleResolutionTest.php` using `Sanctum::actingAs()` and `$this->json()`, asserting an API response comes back in the token user's stored preference, that a request with no session resolves without raising (FR-012, and research R8 explains why the current guard is unnecessary), and the two negative guarantees of FR-013: no `Set-Cookie` for the locale on an API response, and the session untouched
- [X] T011 [P] [US1] Extend `tests/Feature/Controllers/App/Auth/LocaleControllerTest.php` to assert the queued cookie alongside the session it already asserts
- [X] T012 [P] [US1] Extend `tests/Feature/Controllers/App/Settings/Account/Preferences/PreferenceControllerTest.php` the same way, keeping its existing assertion that the account preference is what `UpdatePreferences` wrote

### Implementation for User Story 1

- [X] T013 [US1] Add `fromSession(): ?string` to `app/Helpers/LocaleResolution.php`, reading through the `Session` facade rather than `$request->session()`, per research R8, and returning null for anything not shipped
- [X] T014 [US1] Add `fromUser(): ?string` to `app/Helpers/LocaleResolution.php`, reading `users.locale` of the authenticated user and returning null for anything not shipped
- [X] T015 [US1] Add `forWeb(Request $request): string` and `forApi(Request $request): string` to `app/Helpers/LocaleResolution.php` with the sources available at this phase, session then user then application for the web, user then application for the API
- [X] T016 [US1] Rewrite `app/Http/Middleware/SetLocale.php` in place to call `forWeb()` then `remember()`, dropping the `$request->hasSession()` guard the current version carries. Keep the file's name and path so the registration `6fa1e09` already made in `bootstrap/app.php` needs no change
- [X] T017 [US1] Create `app/Http/Middleware/SetApiLocale.php` calling `forApi()` and setting the active locale and nothing else. It must not call `remember()`, which would write a session and queue a cookie
- [X] T018 [US1] Register `SetApiLocale` on the `api` group in `bootstrap/app.php`, closing the gap `6fa1e09` left when it removed `set.locale` from `routes/api.php` without a replacement
- [X] T019 [P] [US1] Replace the hand written `session()->put('locale', ...)` in `app/Http/Controllers/App/Auth/LocaleController.php` with one `remember()` call
- [X] T020 [P] [US1] Replace the two hand written writes in `app/Http/Controllers/App/Settings/Account/Preferences/PreferenceController.php`, the `session()->put('locale', ...)` and the `app()->setLocale(...)` that follows it, with one `remember()` call. Remove the comment explaining the second write, which no longer describes anything
- [X] T021 [US1] Run the tests from T007 to T012 and confirm they pass

**Checkpoint**: the product behaves as it did before this feature, on every surface including the
repaired API, with the logic in one place and covered by tests for the first time. Shippable alone.

---

## Phase 4: User Story 2 - A new visitor lands in a language they can read (Priority: P2)

**Goal**: a first time visitor with no session, no account and no stored choice sees the interface in a
language their browser announces, when the product ships it.

**Independent test**: open the product in a private window with a browser announcing French, and
confirm the sign in screen is French. Repeat with a browser announcing only unshipped languages and
confirm the fallback.

### Tests for User Story 2

- [X] T022 [P] [US2] Extend `tests/Unit/Helpers/LocaleResolutionTest.php` with one case per row of the matching table in [data-model.md](data-model.md): a shipped key arriving as is, `fr_CH` falling back to `fr_FR` because the region is not shipped and the language is, bare `fr` reaching `fr_FR`, `en_US` reaching `en`, `pt_BR` and `pt_PT` each reaching themselves, bare `pt` reaching the locale declaring `answers_bare_language`, `sv` yielding nothing, several languages resolved in the browser's weight order, and a malformed header yielding nothing
- [X] T023 [P] [US2] Extend `tests/Unit/Helpers/LocaleResolutionTest.php` with the precedence the two orders disagree on: under `forWeb()` an account preference beats an announced language, under `forApi()` an announced language beats an account preference
- [X] T024 [P] [US2] Extend `tests/Feature/Localization/WebLocaleResolutionTest.php` with a first time visitor whose browser announces a shipped language landing in it, and one announcing only unshipped languages landing in the fallback
- [X] T025 [P] [US2] Extend `tests/Feature/Localization/ApiLocaleResolutionTest.php` with the remaining rows of the table in [contracts/api-language.md](contracts/api-language.md), including a header disagreeing with the token user's preference where the header wins, and a header naming nothing shipped falling through to the preference rather than to the fallback

### Implementation for User Story 2

- [X] T026 [US2] Add `'answers_bare_language' => true` to the `pt_BR` entry in `config/monica.php`, with a comment saying it names which Portuguese answers a browser that gives no region. Research R5 records that this is a product decision following CLDR and is one line to reverse
- [X] T027 [US2] Add `fromAnnouncedLanguage(Request $request): ?string` to `app/Helpers/LocaleResolution.php`, reading `$request->getLanguages()` and applying the three rules of research R4 in order: return a shipped key as is, else the one shipped locale for its language where exactly one exists, else the locale declaring `answers_bare_language`. It must never return anything that is not a shipped key
- [X] T028 [US2] Slot `fromAnnouncedLanguage()` into both orders in `app/Helpers/LocaleResolution.php`: fourth in `forWeb()`, ahead of the fallback, and first in `forApi()`, ahead of the account preference
- [X] T029 [US2] Run the tests from T022 to T025 and confirm they pass

**Checkpoint**: browser detection works on both surfaces, and all nine locales are reachable by it,
which is SC-003 and the thing research R2 shows a naive implementation gets wrong.

---

## Phase 5: User Story 3 - My language survives coming back tomorrow (Priority: P3)

**Goal**: a signed out reader who chose a language keeps it after their session expires.

**Independent test**: choose a language while signed out, delete the session cookie while leaving the
`locale` cookie, return, and confirm the language held.

Note that `remember()` already writes the cookie, from T005. This phase adds the reading half.

### Tests for User Story 3

- [X] T030 [P] [US3] Extend `tests/Unit/Helpers/LocaleResolutionTest.php` with `fromCookie()`: returning its value, returning null when absent, and returning null rather than the value when the cookie holds a locale the product does not ship, which is the edge case of a locale withdrawn after the cookie was written
- [X] T031 [P] [US3] Extend `tests/Unit/Helpers/LocaleResolutionTest.php` with the two adjacent precedences the cookie sits between under `forWeb()`: an account preference beats it, and it beats an announced language
- [X] T032 [P] [US3] Extend `tests/Feature/Localization/WebLocaleResolutionTest.php` with a signed out reader whose session has gone but whose cookie remains keeping their language, and with the case that signing in to an account with a different preference makes the account preference win
- [X] T033 [P] [US3] Extend `tests/Feature/Localization/ApiLocaleResolutionTest.php` to assert the cookie takes no part in `forApi()`, so a request carrying a `locale` cookie is still answered from the header then the preference

### Implementation for User Story 3

- [X] T034 [US3] Add `fromCookie(): ?string` to `app/Helpers/LocaleResolution.php`, returning null for anything not shipped
- [X] T035 [US3] Slot `fromCookie()` into `forWeb()` only, third, between the account preference and the announced language. It must not appear in `forApi()`
- [X] T036 [US3] Run the tests from T030 to T033 and confirm they pass

**Checkpoint**: all five sources are in place and every requirement in the specification is met.

---

## Phase 6: Polish & Cross-Cutting Concerns

- [X] T037 [P] Run `php artisan monica:localize --check` and confirm it reports nothing missing. Anything reported means a user facing string was added that the specification did not ask for, which FR-019 forbids
- [X] T038 [P] Run `vendor/bin/pint --dirty --format agent` and fix what it reports
- [X] T039 [P] Run PHPStan and confirm it reports nothing new
- [X] T040 Run the whole suite with `php artisan test --compact` and confirm it is green
- [X] T041 **Left for a person.** Walk the three manual checks in [quickstart.md](quickstart.md), which the suite cannot make: a first visit landing in the browser's language, a choice outliving the session, and an account preference beating a device cookie
- [X] T042 Read each of the three new files and confirm none reproduces text from `asbiin/laravel-localizer`. What is borrowed is the shape described in research R1, and SC-013 asks that no third party text and no licence notice enter the repository. No command asserts this, so it is a reading task
- [X] T043 **Not started, nothing pushed.** Write the pull request with a Conventional Commits title reading as one short outcome, naming `asbiin/laravel-localizer` as the source of the design, and mentioning that it repairs the API locale gap left by `6fa1e09`. No mention of the tool that wrote any of this

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: no dependencies.
- **Foundational (Phase 2)**: depends on Setup. **Blocks every user story**, because all three add
  methods to the class T003 creates.
- **User Story 1 (Phase 3)**: depends on Foundational. Independent of US2 and US3.
- **User Story 2 (Phase 4)**: depends on Foundational. Touches `forWeb()` and `forApi()`, which US1
  creates, so in practice it follows US1 even though its behaviour is independent.
- **User Story 3 (Phase 5)**: depends on Foundational. Same practical ordering as US2, and independent
  of it: US2 and US3 touch different methods and could be built in either order.
- **Polish (Phase 6)**: depends on whichever stories are being shipped.

### User Story Dependencies

None of the three depends on another's behaviour. The coupling is mechanical: all three edit
`app/Helpers/LocaleResolution.php`, and US2 and US3 each insert a source into an order US1 wrote. Ship
in priority order and that coupling never bites.

One consequence worth seeing: US1 alone leaves `forApi()` reading account preference then fallback,
which is what the API did before `6fa1e09`. That is a complete repair of the current defect on its own,
which is why the API sits in the MVP.

### Within Each User Story

Tests first, then the source method, then slotting it into the orders, then the run. The two controller
edits in US1, T019 and T020, are independent of the middleware work and of each other.

### Parallel Opportunities

- T007 to T012 in US1: six test files or files being extended, all different, all writable at once.
- T019 and T020 in US1: two controllers, different files.
- T022 to T025 in US2, and T030 to T033 in US3: the test tasks in each phase.
- T037, T038 and T039 in Polish: three independent commands.

The implementation tasks inside each story are mostly **not** parallel, because T013, T014, T015,
T027, T028, T034 and T035 all edit `app/Helpers/LocaleResolution.php`.

---

## Parallel Example: User Story 1

```text
Together, six different files:
  T007  fromSession and fromUser cases        tests/Unit/Helpers/LocaleResolutionTest.php
  T009  the web surface through requests      tests/Feature/Localization/WebLocaleResolutionTest.php
  T010  the API surface and its guarantees    tests/Feature/Localization/ApiLocaleResolutionTest.php
  T011  the switcher's cookie                 tests/Feature/Controllers/App/Auth/LocaleControllerTest.php
  T012  the preferences screen's cookie       tests/Feature/Controllers/.../PreferenceControllerTest.php

Note T008 extends the same file as T007, so those two are sequential with each other.

Then together:
  T019  LocaleController
  T020  PreferenceController
```

---

## Implementation Strategy

### MVP First (User Story 1 only)

Phases 1, 2 and 3. This gives the product what it has today, on every surface, with the logic in one
place and a test suite where there was none, plus the repair of the API gap currently on `main`. Neither
of the two new reader facing behaviours is in it, so nobody sees a change, which makes it the safest
thing to merge first.

### Incremental Delivery

1. Setup and Foundational, then US1. Nothing visible changes. Merge.
2. US2. A first time visitor now lands in their own language. This is the one increment a reader
   notices, and the one to watch after deploying.
3. US3. A signed out reader's choice starts outliving their session.
4. Polish.

Each of the three can go in its own pull request, and each leaves the product working.

### Parallel Team Strategy

Not worth splitting across people. Three of the five sources are four lines each, and every
implementation task after Foundational edits the same file, so two people would spend more time on
conflicts than the work is worth. The test tasks parallelise, which is where the volume is.

---

## Notes

- Every test file starts with `declare(strict_types=1)`, mirrors the application namespace under
  `Tests\Unit\`, marks methods with `#[Test]` and names them in snake_case beginning with `it_`. Use
  `RefreshDatabase` only where the database is touched, which is anything reading `users.locale`. The
  rules are in `.claude/rules/testing.md`.
- When a test needs data to prove a point, use The Office (US).
- No migration, no schema change, no new dependency, no new top level folder and no constitution
  amendment. If any of those appears in the diff, something has gone off plan.
