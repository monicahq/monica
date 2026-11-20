---

description: "Task list for withholding the visitor's network address from Nightwatch"
---

# Tasks: Withhold the visitor's network address from monitoring

**Input**: Design documents from `/specs/144-nightwatch-ip-redaction/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/request-trace.md, quickstart.md

**Tests**: Included. Constitution principle III makes tests a merge condition, and
`contracts/request-trace.md` names the two tests that prove the contract.

**Organization**: Tasks are grouped by user story. Both stories touch the same two files, so nothing
inside them runs in parallel.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to

---

## Phase 1: Setup

None. The branch, `laravel/nightwatch` v1.30.6 and the test helpers `nightwatchRequest()` and
`nightwatchCallbacks()` in `tests/Unit/Providers/AppServiceProviderTest.php` already exist.

---

## Phase 2: Foundational

None. The redaction callback this feature extends is already registered in
`AppServiceProvider::configureNightwatch()`, which `boot()` calls.

---

## Phase 3: User Story 1 - A request trace carries no network address (Priority: P1) MVP

**Goal**: The `ip` field of every request trace is empty, and no proxy forwarding header leaves with it.

**Independent Test**: `php artisan test --compact --filter=it_withholds_the_address_from_a_nightwatch_request`

### Tests for User Story 1

- [X] T001 [US1] In `tests/Unit/Providers/AppServiceProviderTest.php`, add `#[Test] public function it_withholds_the_address_from_a_nightwatch_request(): void`. Build the record with the existing `nightwatchRequest('https://monica.test/vaults', [...])` helper (which sets `ip: '127.0.0.1'`), passing the headers `X-Forwarded-For => '203.0.113.7, 10.0.0.1'`, `X-Real-IP => '203.0.113.7'`, `Forwarded => 'for=203.0.113.7'`, `CF-Connecting-IP => '203.0.113.7'`, `True-Client-IP => '203.0.113.7'` and `Content-Type => 'application/json'`. Assert `$request->ip` is `''` with `assertSame`, assert `assertFalse($request->headers->has(...))` for each of the five forwarding headers, and assert `Content-Type` is still `'application/json'`. No comment in the test. Run it and confirm it fails.

### Implementation for User Story 1

- [X] T002 [US1] In `app/Providers/AppServiceProvider.php`, inside the existing `Nightwatch::redactRequests(function (RequestRecord $request): void { ... })` callback of `configureNightwatch()`, after the line removing `X-Telegram-Bot-Api-Secret-Token`, add `$request->ip = '';` (never `null`: `Records\Request::$ip` is typed `string`, research R1) and remove the five headers `X-Forwarded-For`, `X-Real-IP`, `Forwarded`, `CF-Connecting-IP`, `True-Client-IP` with `$request->headers->remove(...)`, one call per header, matching the Telegram line. Add no helper, constant or new method.
- [X] T003 [US1] In the docblock above `configureNightwatch()` in `app/Providers/AppServiceProvider.php`, extend the existing sentence listing what must not leave with the trace so it names the client address and the forwarding headers a proxy adds. One clause, no new sentence.
- [X] T004 [US1] Run `php artisan test --compact tests/Unit/Providers/AppServiceProviderTest.php` and confirm T001 and the existing URL and Telegram header tests pass.

**Checkpoint**: The address no longer leaves in any request trace.

---

## Phase 4: User Story 2 - A trace identifies a person by number only (Priority: P2)

**Goal**: Prove the already shipped behaviour: the user details are `['id' => ...]` and nothing else.

**Independent Test**: `php artisan test --compact --filter=it_identifies_a_nightwatch_user_by_id_only`

### Tests for User Story 2

- [X] T005 [US2] In `tests/Unit/Providers/AppServiceProviderTest.php`, add `#[Test] public function it_identifies_a_nightwatch_user_by_id_only(): void`. Read the resolver with `$this->app->make(Core::class)->userDetailsResolver` (a public property on `Laravel\Nightwatch\Core`), call it with `User::factory()->make(['id' => 42, 'first_name' => 'Michael', 'last_name' => 'Scott', 'email' => 'michael@dundermifflin.com'])`, and assert with `assertSame(['id' => 42], ...)`. Add `use RefreshDatabase;` only if the test touches the database, which `make()` does not. Run it; it passes without a code change.

**Checkpoint**: Both rules of `contracts/request-trace.md` are proven.

---

## Phase 5: Polish & Cross-Cutting Concerns

- [X] T006 Run `vendor/bin/pint --dirty --format agent`.
- [X] T007 Run `composer test:parallel` and confirm the whole suite passes, including `tests/Feature/NightwatchSamplingTest.php`.
- [X] T008 Run the `definition-of-done` skill. Expected outcome: no translation, API, MCP, documentation or changelog work (research R5); record any item it raises.

---

## Dependencies & Execution Order

- T001 → T002 → T003 → T004 (same files, test first).
- T005 depends on nothing and can run before or after US1, but it edits the same test file, so it is not marked [P].
- T006 → T007 → T008 after both stories.

## Parallel Opportunities

None worth taking: two stories, one test file and one provider.

## Implementation Strategy

US1 is the whole change. US2 adds a test for behaviour that already ships. Do both in one commit,
`fix(admin): hide visitor address from monitoring`. The scope list in the `pull-requests` skill has
no monitoring or privacy scope; `admin` (the configuration of the instance) is the nearest, to confirm before committing.
