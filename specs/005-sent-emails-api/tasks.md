---

description: "Task list for the sent emails endpoints"
---

# Tasks: Sent emails endpoints

**Input**: Design documents from `specs/005-sent-emails-api/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md),
[data-model.md](data-model.md), [contracts/emails-sent.md](contracts/emails-sent.md)

**Tests**: Included, and not optional here. Constitution Principle III makes a test a condition of
merge, and the checklist in `.claude/CLAUDE.md` repeats it. Test tasks are written before the code
they guard wherever the order matters.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel with the other `[P]` tasks in the same phase (different files, no
  dependency between them)
- **[Story]**: Which user story the task belongs to (US1, US2)
- Every task names the exact file it touches

## How independent the two stories really are

The spec has US1 (list) at P1 and US2 (read one) at P2, and they are **not** cleanly separable. Two
things in the existing codebase couple them, both found during Phase 1 design:

1. **The self link.** Every resource in this API carries `links.self` pointing at its own show
   endpoint. `EmailSentResource` therefore calls `route('api.v1.emailsSent.show', ...)`, which throws
   unless that route is registered. US1 cannot render a single row without the show route existing.
2. **The reference sync test.** `ApiDocsViewModelTest` compares documented endpoints against
   `routes/api.php` in **both** directions. A registered route with no documentation fails the suite,
   and so does documentation for a route that does not exist. Routes and reference must land together.

So US1's phase registers both routes, writes both controller methods and both reference sections. US2's
phase is its own tests. This is recorded rather than papered over: splitting it further would mean
shipping a red suite between phases, which Principle III does not allow.

---

## Phase 1: Setup

**Purpose**: Know the branch was green before anything moved.

- [X] T001 Run `php artisan test --compact` from the repository root and confirm the suite passes before any edit, so a later failure is known to belong to this branch

**No migration, no dependency, no configuration.** `emails_sent` is already migrated,
`Account::emailsSent()` already exists (`app/Models/Account.php:178`), and both page-size bounds are
already in `config/monica.php`. There is nothing to initialize.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: The delivery state, which both stories read and the existing screen must keep rendering
identically.

**⚠️ CRITICAL**: No user story work begins until this phase is complete and the suite is green.

- [X] T002 [P] Create `app/Enums/EmailDeliveryStatusEnum.php`, a string backed enum with cases `Bounced` (`bounced`), `Delivered` (`delivered`) and `Sent` (`sent`), carrying no `label()` and no `description()` and not implementing `Translatable`, per [research.md](research.md) section 2
- [X] T003 [P] Add `delivered()` and `bounced()` states to `database/factories/EmailSentFactory.php`, setting `delivered_at` and `bounced_at` respectively, leaving the existing definition and `withoutUser()` untouched
- [X] T004 Create `tests/Unit/Enums/EmailDeliveryStatusEnumTest.php` asserting the three backing values, following the shape of the sibling files in `tests/Unit/Enums/`
- [X] T005 Add a `deliveryStatus` accessor to `app/Models/EmailSent.php` returning `EmailDeliveryStatusEnum`, deriving it from the three timestamps with `bounced_at` winning over `delivered_at`, with the `@return Attribute<EmailDeliveryStatusEnum, never>` docblock the code style requires
- [X] T006 Add accessor cases to `tests/Unit/Models/EmailSentTest.php`: bounced, delivered, neither set reads as `Sent`, and both set reads as `Bounced`
- [X] T007 Add the three badge cases to `tests/Feature/Controllers/App/Settings/Account/Logs/EmailSentControllerTest.php`, asserting the screen renders "Bounced", "Delivered" and "On its way" for the three states **against the current inline `match`**, so the assertions are written before the code moves
- [X] T008 Rewire `resources/views/app/settings/account/logs/_email-sent-row.blade.php` to `match ($emailSent->delivery_status)` instead of testing the three timestamps inline, keeping its own tone and wording exactly as they are
- [X] T009 Re-run `php artisan test --compact tests/Feature/Controllers/App/Settings/Account/Logs/EmailSentControllerTest.php tests/Unit/Models/EmailSentTest.php tests/Unit/Enums/EmailDeliveryStatusEnumTest.php` and confirm T007's assertions still pass after T008, which is the whole point of writing them first

**Checkpoint**: The screen renders exactly as it did, now reading a shared accessor. Both stories can
start.

---

## Phase 3: User Story 1 - Read the record of emails we sent, from a script (Priority: P1)

**Goal**: `GET /api/v1/emails-sent` lists an account's sent emails, newest sent first, a page at a
time, with no copy of the email in the rows.

**Independent test**: Call the endpoint with a valid token on an account that has emails, and confirm
the answer carries every email newest first, each with its subject, recipient and delivery state, and
that no row carries a `body` key.

- [X] T010 [US1] Create `app/Http/Resources/EmailSentResource.php` with the `@mixin EmailSent` docblock, `type` of `email_sent`, `id` cast to string, the attributes listed in [data-model.md](data-model.md), timestamps as Unix integers via `?->timestamp`, `delivery_status` as the enum's value, `links.self` pointing at `route('api.v1.emailsSent.show', $this->id)`, and `body` behind `$this->when($request->routeIs('api.v1.emailsSent.show'), ...)` with a short comment saying why
- [X] T011 [US1] Create `app/Http/Controllers/Api/EmailSentController.php` with `index` and `show`, mirroring `app/Http/Controllers/Api/LogController.php`: the same hand built `data` and `meta` envelope, the same `max(1, min(...))` clamp against `monica.api_default_items_per_page` and `monica.api_maximum_items_per_page`, scoping through `$request->user()->account->emailsSent()`, ordering by `sent_at` then `id` both descending, and `findOrFail` in `show`
- [X] T012 [US1] Register both routes in `routes/api.php` inside the existing `auth:sanctum` group, below the logs pair: `emails-sent` named `emailsSent` and `emails-sent/{emailSent}` named `emailsSent.show` constrained by `->where('emailSent', '[1-9][0-9]*')`, adding the controller import in alphabetical order
- [X] T013 [US1] Create `resources/docs/api/07-emails-sent.php` with the group name "Emails sent" and both sections, `emails-sent-index` then `emails-sent-show`, matching the shape of `resources/docs/api/06-logs.php` and the exact response bodies in [contracts/emails-sent.md](contracts/emails-sent.md), using The Office for the sample data and no em dashes in the copy
- [X] T014 [US1] Create `tests/Feature/Controllers/Api/EmailSentControllerTest.php` with a shared `$jsonStructure` property and the list cases: `it_lists_the_emails_sent_by_the_account_newest_first`, `it_does_not_list_the_emails_sent_by_another_account`, `it_lists_nothing_when_no_email_has_left`, `it_brings_an_absurd_page_size_back_into_range`, `it_answers_the_page_that_was_asked_for`, `it_leaves_the_copy_of_the_email_out_of_the_list`, `it_names_the_three_delivery_states`, and `it_refuses_a_caller_without_a_token`
- [X] T015 [US1] Run `php artisan test --compact tests/Feature/Controllers/Api/EmailSentControllerTest.php tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php` and confirm both the list cases and the two way reference sync pass

**Checkpoint**: The list endpoint works and is documented. US1 is deliverable on its own.

---

## Phase 4: User Story 2 - Read one email, including the copy that left (Priority: P2)

**Goal**: `GET /api/v1/emails-sent/{emailSent}` reads one email with its copy, and refuses anything
outside the account.

**Independent test**: Take an identifier from the list, ask for that one email, and confirm the answer
carries the copy as well as everything the list row carried.

**Note**: The route, the controller method and the reference section were built in Phase 3, for the two
reasons given at the top of this file. What follows is what proves them.

- [X] T016 [US2] Add the single email cases to `tests/Feature/Controllers/Api/EmailSentControllerTest.php`: `it_shows_one_email_that_was_sent` asserting the full structure and the timestamps as integers, and `it_carries_the_copy_of_the_email_when_one_is_asked_for`
- [X] T017 [US2] Add the compartment cases to the same file: `it_returns_not_found_for_an_email_from_another_account` and `it_returns_not_found_for_an_email_that_does_not_exist`, asserting both answer 404 identically
- [X] T018 [US2] Add `it_offers_no_way_to_write_a_sent_email` to the same file, asserting `POST`, `PUT` and `DELETE` on both URLs answer 405, which is FR-011
- [X] T019 [US2] Run `php artisan test --compact tests/Feature/Controllers/Api/EmailSentControllerTest.php` and confirm every case passes

**Checkpoint**: Both endpoints work, and the account boundary is proven on each.

---

## Phase 5: Polish and Cross-Cutting Concerns

- [X] T020 [P] Add `$response->assertSee('Emails sent');` to the sidebar test in `tests/Feature/Controllers/App/Docs/ApiDocsControllerTest.php`, beside the existing assertions for Health, Your profile, API keys and Logs
- [X] T021 [P] Run `vendor/bin/pint --dirty --format agent` from the repository root and commit whatever it reformats
- [X] T022 Run `php artisan monica:localize` and then `php artisan monica:localize --check`, confirming no new key went missing across the nine locales, per the checklist in `.claude/CLAUDE.md`
- [X] T023 Run the whole suite with `php artisan test --compact` and confirm it is green
- [X] T024 Walk [quickstart.md](quickstart.md) steps 2 through 6 by hand against a running application, in particular step 5, confirming the sent emails screen at `/settings/account/logs/emails` renders the three badges exactly as it did before the branch
- [X] T025 Confirm the reference at `/docs/api` shows an "Emails sent" group under RESOURCES with two GET entries, and that `/docs/api.md` carries it too

---

## Dependencies

```text
T001 (baseline)
  └─> Phase 2 Foundational
        T002 (enum) ──> T004 (enum test)
        T002 ────────> T005 (accessor) ──> T006 (accessor test)
        T003 (factory states) ──> T007 (badge guard tests)
        T007 ──> T008 (rewire partial)   [ORDER IS THE POINT]
        T005, T007, T008 ──> T009 (confirm nothing moved)
              └─> Phase 3 US1
                    T010 (resource) ─┐
                    T011 (controller)├─> T012 (routes) ──> T013 (docs) ──> T014 (tests) ──> T015
                                      │  T010 needs the show route name to exist,
                                      │  T013 must land with T012 or the sync test fails
                          └─> Phase 4 US2
                                T016, T017, T018 ──> T019
                                      └─> Phase 5 Polish
                                            T020, T021 parallel; then T022, T023, T024, T025
```

**The one ordering that must not be relaxed**: T007 before T008. The badge mapping in
`_email-sent-row.blade.php` has no test today, so moving it onto the accessor is unguarded until those
assertions exist. Writing them after the rewire would only prove the new code agrees with itself.

**Story order**: US1 before US2. US2's plumbing is dragged into US1's phase by the self link and the
reference sync test, so US2 cannot be built first.

## Parallel execution examples

**Phase 2, at the start**: T002 (the enum) and T003 (the factory states) touch different files and
depend on nothing, so they can be written together. T004 waits on T002.

**Phase 5, at the start**: T020 (the docs assertion) and T021 (Pint) are independent. Everything after
them is a verification run and is sequential by nature.

**Phase 3 and Phase 4 hold no parallel work.** Five of the six files in US1 depend on the one before
it, and every US2 task edits the same test file.

## Implementation strategy

**MVP**: Phase 1 plus Phase 2 plus Phase 3, which is T001 to T015. That delivers the list endpoint,
documented, with the screen provably unchanged. It is the whole of US1 and it answers the question the
feature exists to answer, which is whether an email bounced.

**Increment two**: Phase 4, four tasks, all of them tests over plumbing the MVP already built.

**Then**: Phase 5, which is where Pint, the localize check and the manual walk live. None of it may be
skipped before the pull request; T022 and T023 are checklist items in `.claude/CLAUDE.md` rather than
suggestions.

**Total**: 25 tasks. 9 foundational, 6 for US1, 4 for US2, 6 polish.
