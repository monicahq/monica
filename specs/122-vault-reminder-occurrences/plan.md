# Implementation Plan: Vault-wide reminder occurrences over the API and the MCP server

**Branch**: `2026-11-13-bug-fixes` | **Date**: 2026-11-13 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/122-vault-reminder-occurrences/spec.md`

## Summary

One read endpoint, `GET /api/v1/vaults/{vault}/reminder-occurrences`, and one MCP tool mirroring it,
`list-vault-reminder-occurrences`. Both list the dates of a whole vault, filtered by category
(overdue, today, upcoming, history), kind (every reminder, or birthdays) and a horizon in days.

The dashboard's query moves out of `App\ViewModels\Vaults\VaultDeadlines::rowsUntil()` into a helper,
`App\Helpers\VaultReminderDates`, which the view model, the controller and the tool all call. That is
what keeps the three answers identical (FR-007). The birthdays kind adds the birthdays nobody asked to
be reminded of (FR-018), worked out from the field values in the same helper. Each row is rendered by a
new resource that reuses `ReminderOccurrenceResource` for an occurrence and adds the subject and the
age; the existing per contact and per group endpoints do not change.

## Technical Context

**Language/Version**: PHP 8.5, Laravel (installed major), Laravel MCP

**Primary Dependencies**: Sanctum (API keys), Laravel MCP (`MirrorsAnApiCapability`)

**Storage**: MySQL/SQLite through Eloquent. No migration: every table needed exists.

**Testing**: PHPUnit. Unit for the helper and the MCP tool, Feature for the controller.

**Target Platform**: Linux server

**Project Type**: Web service (JSON API and MCP server of the existing application)

**Performance Goals**: As fast as the dashboard for the same vault (SC-003): the same queries, plus
one query for birthdays without a reminder when that kind is asked.

**Constraints**: Same rows as the dashboard (FR-007); sensitive fields respected; no change to an
existing response shape (constitution VI).

**Scale/Scope**: One route, one controller, one tool, two helpers, one resource, docs, changelog.

## Constitution Check

| Principle | How the design satisfies it |
| --- | --- |
| I. Actions hold the business logic | Read only, no action. The listing logic lives in one helper shared by the web, the API and MCP, as `ReminderDates` already does for one contact. |
| II. Simplicity | No new table, no enum: the kind is two string constants on the helper, as `ReminderDates::History` already is. |
| III. Tested before merged | Helper, controller and tool tested; dashboard tests (`VaultDeadlinesTest`) rerun unchanged. |
| IV. Laravel conventions | One named route, thin controller, inline validation, API resource. |
| V. Localized | The MCP docs entry goes through the existing translated list; `monica:localize --check` run. |
| VI. Nothing changes under anybody's feet | Additive: new route, new tool, new resource. `ReminderOccurrenceResource` is reused, not changed. |
| VIII. Nothing disappears silently | A sensitive birthday hidden from a key without the scope is left out, as every other sensitive value is. |
| X. Everything goes through the API | Closes the gap: the vault dashboard and the vault reminders screen had no API. The MCP tool mirrors the route, it does not bypass it. |
| Scoping | The vault is found through `$user->account->vaults()->addressedBy()`, as every vault endpoint does; every query is narrowed to the vault and its account. |

Gate: passes. Re-checked after Phase 1: passes, nothing in the design adds a violation.

## Project Structure

### Documentation (this feature)

```text
specs/122-vault-reminder-occurrences/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── contracts/vault-reminder-occurrences.md
└── tasks.md
```

### Source Code (repository root)

```text
app/Helpers/VaultReminderDates.php                  # new: the vault's dates, one source for every surface
app/Helpers/VaultReminderDate.php                   # new: one row, an occurrence or a birthday without a reminder
app/ViewModels/Vaults/VaultDeadlines.php            # rowsUntil() delegates to the helper
app/Http/Resources/VaultReminderDateResource.php    # new
app/Http/Controllers/Api/VaultReminderOccurrenceController.php  # new
routes/api.php                                      # one route: api.v1.reminderOccurrences
app/Mcp/Tools/ListVaultReminderOccurrences.php      # new
app/Mcp/Servers/MonicaServer.php                    # registers the tool
app/ViewModels/Docs/McpDocsViewModel.php            # lists the tool
resources/docs/api/20-reminders.php                 # documents the endpoint
resources/docs/api-changelog/2026-11-13.php         # records the addition

tests/Unit/Helpers/VaultReminderDatesTest.php
tests/Feature/Controllers/Api/VaultReminderOccurrenceControllerTest.php
tests/Unit/Mcp/Tools/ListVaultReminderOccurrencesTest.php
```

**Structure Decision**: The existing Laravel layout. Helpers sit beside `ReminderDates` and
`NextReminderDeadlines`, which they extend to a whole vault.

## Complexity Tracking

No violation to justify.
