---

description: "Task list for MCP tools for reading the account logs"
---

# Tasks: MCP tools for reading the account logs

**Input**: Design documents from `specs/061-mcp-account-log-tools/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md),
[contracts/mcp-tools.md](contracts/mcp-tools.md), [quickstart.md](quickstart.md)

**Tests**: required. Constitution principle III and `CLAUDE.md`. Tests follow
`.claude/rules/testing.md`: `declare(strict_types=1);`, `#[Test]`, snake_case `it_...` names,
`RefreshDatabase`, factories, The Office data, no comments. A rule is tested once, in the layer that
owns it: the account boundary in each tool's own test (not again in `McpCompartmentTest`), the address
removal in `CreateEmailSentTest` (not again in `RecordEmailSentTest`).

**Model for every tool**: `app/Mcp/Tools/GetRelationshipType.php` and `ListRelationshipTypes.php`:
`use MirrorsAnApiCapability;`, `protected string $mirrors`, `#[Description]`, `#[IsReadOnly]`,
`handle(Request $request): Response`, `$request->validate([...])`, `schema(JsonSchema $schema)`
returning `array<string, Type>`. Descriptions are the ones in the contract.

**Model for every tool test**: `tests/Unit/Mcp/Tools/GetRelationshipTypeTest.php`:
`MonicaServer::actingAs($user)->tool(Tool::class, [...])`, `->assertOk()->assertSee(...)`,
`assertHasErrors(['sentence'])`, plus `it_names_the_api_capability_it_mirrors`. Build rows with
`Log::factory()->create(['account_id' => $user->account_id, ...])` and
`EmailSent::factory()->create(['account_id' => $user->account_id, ...])`.

## Format: `[ID] [P?] [Story] Description`

## Phase 1: Setup

None. No dependency, folder, migration or route is added.

---

## Phase 2: Foundational (blocks every story)

- [X] T001 Add to `app/Mcp/Tools/MirrorsAnApiCapability.php`, after `relationshipCategory()`: `protected function log(Request $request, string $id): Log` and `protected function emailSent(Request $request, string $id): EmailSent`. Each throws `$this->doesNotExist(...)` when `preg_match('/^[1-9][0-9]*$/', $id)` fails, then reads `$this->user($request)->account->logs()->with('user')->find((int) $id)` (resp. `account->emailsSent()->find((int) $id)`) and throws the same when null. Sentences: `__('That log entry does not exist.')` and `__('That email does not exist.')`. Import `App\Models\Log` and `App\Models\EmailSent` (research R5).

**Checkpoint**: the lookups exist.

---

## Phase 3: User Story 1 - Ask an assistant what happened in the account (P1) MVP

**Goal**: an assistant pages through the activity and reads one entry.

**Independent Test**: `ListLogsTest` and `GetLogTest` pass; `list-logs` and `get-log` answer through a
real MCP client.

### Tests for User Story 1

- [X] T002 [P] [US1] Create `tests/Unit/Mcp/Tools/ListLogsTest.php`: `it_lists_the_activity_of_the_account_newest_first` (two entries, assert order by `assertSeeInOrder` on their ids and `meta.total` 2); `it_leaves_out_the_activity_of_another_account`; `it_answers_the_page_that_was_asked_for` (three entries, `page: 2, per_page: 2`, one entry, `meta.page` 2); `it_brings_an_absurd_page_size_back_into_range` (`per_page: 1000` answers `meta.per_page` equal to `config('monica.api_maximum_items_per_page')`); `it_refuses_a_page_size_that_is_not_a_number` (`per_page: 'many'` has errors); `it_writes_nothing_to_the_activity` (`assertDatabaseCount('logs', n)` unchanged after the call); `it_names_the_api_capability_it_mirrors` (`api.v1.logs`).
- [X] T003 [P] [US1] Create `tests/Unit/Mcp/Tools/GetLogTest.php`: `it_returns_one_entry_of_the_activity` (an entry with `source` `LogSourceEnum::Mcp`, assert its `action` and `mcp`); `it_reports_an_entry_of_another_account_as_not_existing`; `it_reports_an_identifier_that_is_not_a_number_as_not_existing` (`log: 'abc'`); both `assertHasErrors(['That log entry does not exist.'])`; `it_names_the_api_capability_it_mirrors` (`api.v1.logs.show`).

### Implementation for User Story 1

- [X] T004 [P] [US1] Create `app/Mcp/Tools/ListLogs.php` mirroring `api.v1.logs`. Validate `'page' => ['nullable', 'integer']`, `'per_page' => ['nullable', 'integer']`. `$perPage = max(1, min($validated['per_page'] ?? (int) config('monica.api_default_items_per_page'), (int) config('monica.api_maximum_items_per_page')))`. Query `$this->user($request)->account->logs()->with('user')->orderByDesc('id')->paginate($perPage, ['*'], 'page', max(1, $validated['page'] ?? 1))`. Answer `Response::json(['data' => LogResource::collection($logs->items())->resolve(), 'meta' => ['page' => ..., 'per_page' => ..., 'total' => ...]])`, the envelope of `app/Http/Controllers/Api/LogController.php`. Schema: `page` and `per_page` as `$schema->integer()` with the contract's descriptions, the default and maximum interpolated from config (research R2).
- [X] T005 [P] [US1] Create `app/Mcp/Tools/GetLog.php` mirroring `api.v1.logs.show`. Validate `'log' => ['required', 'string']`. Answer `Response::json(new LogResource($this->log($request, $validated['log']))->resolve())`. Schema: `log` string, required, "The identifier of the entry, as list-logs gives it."
- [X] T006 [US1] Register `ListLogs::class` and `GetLog::class` in `$tools` in `app/Mcp/Servers/MonicaServer.php`, after `ListLifeEventCategories::class`, with their imports.

**Checkpoint**: run T002 and T003; `tests/Feature/Mcp/McpCatalogueTest.php` passes.

---

## Phase 4: User Story 2 - Ask an assistant whether an email went out (P2)

**Goal**: an assistant pages through the emails sent and reads one with its body, and a stored body
never holds a web address.

**Independent Test**: `ListEmailsSentTest`, `GetEmailSentTest` and `CreateEmailSentTest` pass;
`EmailSentControllerTest` still passes unchanged.

### Tests for User Story 2

- [X] T007 [P] [US2] Add to `tests/Unit/Actions/CreateEmailSentTest.php`: `it_removes_every_web_address_from_the_copy` (body `<p>Hi Dwight, <a href="https://monica.test/magic-link/abc?signature=1&expires=2">Sign in</a></p><p>Copy this address: <span>https://monica.test/magic-link/abc?signature=1&amp;expires=2</span></p>`; assert the stored body contains `Sign in` and `Copy this address:` and does not contain `http`); `it_keeps_no_address_from_a_real_sign_in_email` (render `new MagicLinkCreated(new MagicLink(['expires_at' => now()->addMinutes(5)]), 'tok', 5)->toMail($user)->render()` as the body; assert the stored body contains `Sign in to` and does not contain `http` nor `tok`).
- [X] T008 [P] [US2] Create `tests/Unit/Mcp/Tools/ListEmailsSentTest.php`: `it_lists_the_emails_sent_to_the_account_newest_first` (two emails with different `sent_at`, order and `meta.total`); `it_leaves_the_copy_of_each_email_out` (body `Somebody just signed in to your account.` absent); `it_leaves_out_the_emails_of_another_account`; `it_answers_the_page_that_was_asked_for`; `it_names_the_api_capability_it_mirrors` (`api.v1.emailsSent`).
- [X] T009 [P] [US2] Create `tests/Unit/Mcp/Tools/GetEmailSentTest.php`: `it_returns_one_email_with_its_copy` (body `Somebody just signed in to your account from a device we have not seen before.` present); `it_reports_an_email_of_another_account_as_not_existing` and `it_reports_an_identifier_that_is_not_a_number_as_not_existing`, both `assertHasErrors(['That email does not exist.'])`; `it_names_the_api_capability_it_mirrors` (`api.v1.emailsSent.show`).

### Implementation for User Story 2

- [X] T010 [US2] In `app/Actions/CreateEmailSent.php` `sanitize()`, after the Purify pass, set `$this->updatedBody = preg_replace('~https?://[^\s<>"]+~i', '', $cleaned) ?? ''` (research R4). Keep the class docblock accurate.
- [X] T011 [US2] In `app/Http/Resources/EmailSentResource.php`, add `public function __construct(EmailSent $resource, private readonly bool $withBody = false) { parent::__construct($resource); }` and replace the `routeIs(...)` condition with `$this->when($this->withBody, ...)`. In `app/Http/Controllers/Api/EmailSentController.php` `show()`, build `new EmailSentResource($email, withBody: true)` (research R3). Run `tests/Feature/Controllers/Api/EmailSentControllerTest.php` unchanged.
- [X] T012 [P] [US2] Create `app/Mcp/Tools/ListEmailsSent.php` mirroring `api.v1.emailsSent`: as T004, querying `account->emailsSent()->orderByDesc('sent_at')->orderByDesc('id')` and `EmailSentResource::collection(...)`, the envelope of `app/Http/Controllers/Api/EmailSentController.php`.
- [X] T013 [US2] Create `app/Mcp/Tools/GetEmailSent.php` mirroring `api.v1.emailsSent.show`. Validate `'email_sent' => ['required', 'string']`. Answer `Response::json(new EmailSentResource($this->emailSent($request, $validated['email_sent']), withBody: true)->resolve())`. Schema: `email_sent` string, required, "The identifier of the email, as list-emails-sent gives it." Depends on T011.
- [X] T014 [US2] Register `ListEmailsSent::class` and `GetEmailSent::class` in `app/Mcp/Servers/MonicaServer.php` after `GetLog::class`, with their imports.

**Checkpoint**: run T007 to T009 and `EmailSentControllerTest`.

---

## Phase 5: User Story 3 - Know what the assistant can now read (P3)

**Goal**: `/docs/mcp`, its Markdown, the server instructions and the API reference describe the new
tools and the address removal.

**Independent Test**: the docs tests pass with 55 tools.

### Tests for User Story 3

- [X] T015 [P] [US3] In `tests/Unit/ViewModels/Docs/McpDocsViewModelTest.php`, change `it_counts_the_tools_it_lists` to `'MCP currently exposes 55 tools.'`, and add `Read the logs` to what `it_writes_every_section_and_configuration_in_the_markdown` asserts. `it_marks_only_the_groups_that_change_data` stays as it is, since the new group reads only.
- [X] T016 [P] [US3] In `tests/Feature/Controllers/App/Docs/McpDocsControllerTest.php`, assert the page shows `Read the logs` and `list-emails-sent`, following the existing heading assertions. Do the same for `McpDocsMarkdownControllerTest.php` only if it asserts per-section headings today.

### Implementation for User Story 3

- [X] T017 [US3] In `app/ViewModels/Docs/McpDocsViewModel.php`: add `['id' => 'logs', 'label' => 'Read the logs']` to `SECTIONS` before `troubleshooting`; add `public const array LOGS` holding the two paragraphs of research R7 word for word; add the group `['name' => 'Account logs', 'changesData' => false, 'tools' => [...]]` after `Life event categories`, with `list-logs` "Lists what has been done in your account, newest first, a page at a time.", `get-log` "Returns one entry of the activity: who, what, from where and when.", `list-emails-sent` "Lists the emails sent to your account and whether each arrived, a page at a time.", `get-email-sent` "Returns one email, including what it said with links and web addresses removed."; in `markdown()`, write `## Read the logs` and `...$this->paragraphs(self::LOGS)` after the relationship types section, following it.
- [X] T018 [US3] In `resources/views/app/docs/mcp.blade.php`, add `<section id="logs" aria-labelledby="logs-heading">` with `<h2 id="logs-heading">Read the logs</h2>` and the `McpDocsViewModel::LOGS` paragraphs, copying the `relationship-types` section markup exactly (lines 273 onward).
- [X] T019 [P] [US3] In `app/Mcp/Servers/MonicaServer.php` `#[Instructions]`, append: "The account's activity and the emails it was sent are read with list-logs and list-emails-sent, a page at a time, newest first, with the total. Read one email with get-email-sent to see what it said."
- [X] T020 [P] [US3] In `resources/docs/api/09-emails-sent.php`, extend the `$emailBody` description with the sentence "Links and web addresses are removed from the copy before it is kept, so a sign-in link can never be read back." Run `tests/Feature/Controllers/App/Docs` to confirm the API docs still render.

**Checkpoint**: run T015 and T016.

---

## Phase 6: Polish

- [X] T021 Run `php artisan monica:localize`, translate the two new sentences (`That log entry does not exist.`, `That email does not exist.`) in every configured locale following `.claude/rules/translations.md` and the glossary, then `php artisan monica:localize --check`.
- [X] T022 [P] Add the feature to `docs/FEATURES.md` through the `feature-documentator` skill.
- [X] T023 Run `vendor/bin/pint --dirty --format agent`, `vendor/bin/phpstan analyse --no-progress`, then the whole suite with `php artisan test --compact`, since `EmailSentResource` and `CreateEmailSent` are shared.
- [X] T024 Walk [quickstart.md](quickstart.md) "By hand" steps 1 to 4 if an MCP client is available; otherwise say they were not run. Not run on 2026-09-25: no MCP client was connected in the session.

---

## Dependencies

- T001 blocks T003, T005, T009, T013.
- US1 (T002 to T006) and US2 (T007 to T014) are independent of each other once T001 is done.
- T011 blocks T013. T010 has no dependency.
- US3 (T015 to T020) needs T006 and T014, since the docs test compares the listing with the server.
- Polish runs last.

## Parallel examples

- After T001: T002, T003, T004, T005, T007, T008, T009, T012 touch separate files.
- US3: T015, T016, T019, T020 touch separate files; T017 then T018.
- `MonicaServer.php` is edited by T006, T014 and T019: do them one after another.

## Implementation strategy

1. T001, then US1 (MVP): an assistant can read the activity, which is the screen named in the request.
2. US2: the emails, the address removal and the resource change, checked against the unchanged API test.
3. US3 and polish. Commit once the full suite, PHPStan, Pint and `monica:localize --check` are clean.

## Deviations from the plan

- The plan listed additions to `McpCompartmentTest` and `RecordEmailSentTest`. They are dropped: the
  account boundary is proven in each tool's test and the address removal in the action's test, where
  the rule lives (`.claude/rules/testing.md`, test a rule once). The action test renders a real
  `MagicLinkCreated` email instead, which is what the listener test would have added.
