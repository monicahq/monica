# Tasks: Vault-wide reminder occurrences over the API and the MCP server

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [data-model.md](data-model.md), [contracts](contracts/vault-reminder-occurrences.md)

Tests are included: `CLAUDE.md` makes a covering test part of "done".

## Phase 1: Foundational

- [X] T001 Create `app/Helpers/VaultReminderDates.php` with `until(Vault $vault, CarbonImmutable $today, CarbonImmutable $lastDay): Collection<int, ReminderOccurrence>` holding the body of `VaultDeadlines::rowsUntil()` unchanged, loading `reminder.fieldValue` on the waiting occurrences too (research R1)
- [X] T002 Make `rowsUntil()` in `app/ViewModels/Vaults/VaultDeadlines.php` delegate to `VaultReminderDates::until()` and map to `ReminderRow`; rerun `tests/Unit/ViewModels/Vaults/VaultDeadlinesTest.php` and `tests/Unit/ViewModels/Vaults/VaultDashboardAttentionTest.php` unchanged
- [X] T003 Create `app/Helpers/VaultReminderDate.php`, the readonly row of data-model.md (`occurrence`, `subject`, `date`, `age`)

## Phase 2: User Story 1, the vault's dates (P1)

Independent test: overdue, today and upcoming occurrences of contacts and a group come back in one response, the same as the dashboard.

- [X] T004 [US1] Add `page(User $user, Vault $vault, ?string $category, string $kind, int $days, bool $reachesSensitiveFields, int $perPage, int $page): LengthAwarePaginator<int, VaultReminderDate>` to `app/Helpers/VaultReminderDates.php`, with constants `All`, `Birthdays`, `DefaultDays = 7`, `MaximumDays = 365`: no category reads `until()` to today plus `$days`; overdue, today and upcoming filter it with `ReminderOccurrenceCategoryEnum::for()`; history pages `ReminderOccurrence::answeredInVault()` with the rule loaded with its trashed state (research R3)
- [X] T005 [US1] Create `app/Http/Resources/VaultReminderDateResource.php`: an occurrence renders `ReminderOccurrenceResource` and adds `attributes.subject` (`type`, `id`, `name`), `attributes.age` and `links.answer`; a row without one renders `type: birthday` (contract)
- [X] T006 [US1] Create `app/Http/Controllers/Api/VaultReminderOccurrenceController.php` `index()`: vault through `$request->user()->account->vaults()->addressedBy()`, inline validation of `category`, `kind`, `days`, the paging of `ContactReminderOccurrenceController`, and the sensitive ability read as `ContactSectionController` reads it
- [X] T007 [US1] Register `GET vaults/{vault}/reminder-occurrences` named `reminderOccurrences` in `routes/api.php` beside `vaults/{vault}/contacts`
- [X] T008 [P] [US1] Unit tests in `tests/Unit/Helpers/VaultReminderDatesTest.php`: the default set equals `VaultDeadlines::rowsUntil()` for the same day; each category; a group's reminder listed; another vault's left out; history newest answer first; `days` widens the window
- [X] T009 [P] [US1] Feature tests in `tests/Feature/Controllers/Api/VaultReminderOccurrenceControllerTest.php`: the list and its `$jsonStructure`, an unknown `category`, `kind` and out of range `days` refused, another account's vault answers 404

## Phase 3: User Story 2, birthdays (P1)

Independent test: `kind=birthdays` returns birthday occurrences with the age, birthdays without a reminder as `type: birthday`, and nothing else.

- [X] T010 [US2] In `app/Helpers/VaultReminderDates.php`, narrow occurrences to rules whose `fieldValue.field_key` is `birthday` on a contact when `$kind` is `Birthdays`, and add the birthdays without an active reminder of `Contact::listed()` contacts falling between today and the last day, on the next anniversary with 29 February clamped (research R4); leave them out of overdue and history
- [X] T011 [US2] Compute `age` in the helper for a birthday with a known year, and drop both the age and the birthday without a reminder when the field `is_sensitive` and `$reachesSensitiveFields` is false (research R5)
- [X] T012 [P] [US2] Unit tests in `tests/Unit/Helpers/VaultReminderDatesTest.php`: a birthday reminder with its age; a year unknown gives no age; a non birthday reminder left out; a birthday without a reminder listed once; one with a reminder not listed twice; one passed yesterday left out; a sensitive one hidden without the ability and shown with it; the default kind leaves birthdays without a reminder out
- [X] T013 [P] [US2] Feature test in `tests/Feature/Controllers/Api/VaultReminderOccurrenceControllerTest.php`: `kind=birthdays` answers both types

## Phase 4: The MCP tool (P1, spec FR-002)

- [X] T014 Create `app/Mcp/Tools/ListVaultReminderOccurrences.php` mirroring `api.v1.reminderOccurrences`, `#[IsReadOnly]`, the same validation, `reachesSensitiveFields()` from the trait
- [X] T015 Register it in `app/Mcp/Servers/MonicaServer.php` beside `ListContactReminderOccurrences`, and list it under "Reminders, reading" in `app/ViewModels/Docs/McpDocsViewModel.php`
- [X] T016 [P] Unit tests in `tests/Unit/Mcp/Tools/ListVaultReminderOccurrencesTest.php`: lists the vault's dates, `kind=birthdays`, an unknown category refused, another account's vault refused

## Phase 5: User Story 4, documentation (P3)

- [X] T017 [US4] Document the endpoint in `resources/docs/api/20-reminders.php` with its parameters, both row types and an example in The Office data
- [X] T018 [US4] Add an `Added` entry to `resources/docs/api-changelog/2026-11-13.php` for `GET /vaults/{vault}/reminder-occurrences`

## Phase 6: Polish

- [X] T019 Run `php artisan monica:localize` and translate what it reports through Sonnet subagents; `php artisan monica:localize --check` passes
- [X] T020 Run `vendor/bin/pint --dirty --format agent`, `vendor/bin/phpstan analyse --no-progress`, then the tests of quickstart.md and the docs and route protection tests

## Dependencies

T001 → T002, T004. T003 → T004, T005. T004 → T006, T008, T010. T005, T006, T007 → T009, T014. T010, T011 → T012, T013, T016. T017, T018 after T007. T019, T020 last.
