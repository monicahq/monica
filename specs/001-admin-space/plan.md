# Implementation Plan: Admin Space

**Branch**: `2026-08-20-admin-space` | **Date**: 2026-08-21 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/001-admin-space/spec.md`

## Summary

A separate area of the application, reachable only by people who hold an administrator capacity,
where whoever runs the instance moderates marketplace submissions, looks up an account to answer
a support question, acts on that account in the few ways support requires, decides who else
administers the instance, and reads the state of the instance at a glance. Nothing in it reaches
what a user wrote inside a vault.

Technically: two new tables (`administrators`, `administrator_logs`), two middlewares (one that
answers `404` to everybody without the capacity, one that turns a deactivation into an ended
session), seven new actions, five screens under an `/admin` prefix with a layout of their own,
an Artisan command that designates the first administrator, and two mailables that tell an author
what was decided about their submission. The moderation actions that already exist stop being
dead code: they gain a screen, a row lock and the user who took the decision.

## Technical Context

**Language/Version**: PHP 8.4

**Primary Dependencies**: Laravel 13.8, Blade, Tailwind CSS, Alpine.js and Alpine Ajax, Sanctum
(already present). Nothing new is added.

**Storage**: the application database. Two new tables, no column dropped, no existing table
reshaped. Sessions are in the database by default, but nothing here depends on that.

**Testing**: PHPUnit. `tests/Unit` for models, actions, jobs, mailables, the enum, the command and
the view models. `tests/Feature/Controllers/App/Admin` for every controller.

**Target Platform**: Linux server, rendered in a browser. Hosted and self hosted instances alike.

**Project Type**: server rendered web application.

**Performance Goals**: the first page of every list under 500ms at the 95th percentile, measured
server side, on an instance holding a hundred thousand accounts (SC-009). `simplePaginate` runs no
`COUNT(*)`, which removes the query most likely to miss that. The account search is a table scan
by design (research.md decision 8), so it is the one to measure.

**Constraints**: the suite passes on SQLite, MySQL and PostgreSQL, so no driver specific SQL.
PHPStan level 5 over `app/`, `routes/` and `database/`. Every string translated in all nine
locales. No content held inside a vault is reachable from any admin screen, and no route in the
area takes a vault, a contact or a file as a parameter.

**Scale/Scope**: five screens, seven new actions, two new tables, two middlewares, one command,
two mailables, one layout. Around forty new files plus their tests.

No NEEDS CLARIFICATION remains. The thirteen questions the Technical Context raised are answered
in [research.md](./research.md).

## Constitution Check

*Evaluated against `.specify/memory/constitution.md` v1.1.0, before Phase 0 and again after
Phase 1. Same result both times.*

| Principle | Verdict | How this plan satisfies it |
|---|---|---|
| I. Actions hold the business logic | Pass | Seven new actions, one per operation. Controllers validate and delegate. The Artisan command calls `GrantAdministratorAccess` rather than writing a row. The moderation email is dispatched from inside the action, so any surface that decides sends it. |
| II. Simplicity over abstraction | Pass | No package added for a single capacity with no hierarchy. No repository, no service layer, no base admin controller. Each screen has its own view model, following `LogsViewModel`. |
| III. Tested before merged | Pass | Every new class has a test. Every controller test covers the `404` for a non administrator, the happy path and the named refusals. One test asserts no admin screen exposes vault content. |
| IV. Laravel conventions are the default | Pass | Route names camelCase and prefixed `admin.`, URLs kebab-case, one class per file, typed properties and return types, `up()` only in migrations, early returns, no `else`, no `env()` outside config. |
| V. Localized and safe user experience | Pass | Every string through `__()`, translated in all nine locales via `monica:localize`. Every one of the five account and administrator actions sits behind `<x-modal.warning>` (FR-026). |
| Data access must be scoped | Pass, differently | The usual rule is vault and role scoping. Here the scope is the capacity itself, enforced once in middleware and failing as `404` rather than `403`, which is what FR-002 requires. Tested per controller. |
| Three database matrix | Pass | `lockForUpdate()` and `whereRaw('lower(col) like ?')` are the only unusual constructs, both already used or portable. No driver specific SQL. |
| PHPStan level 5 | Pass | Generic annotations on the new relations and paginators, matching the existing models. |

## Project Structure

### Documentation (this feature)

```text
specs/001-admin-space/
├── plan.md              # This file
├── research.md          # Phase 0 output, thirteen decisions
├── data-model.md        # Phase 1 output, two tables and the read only shapes
├── quickstart.md        # Phase 1 output, how to prove it works
├── contracts/
│   ├── routes.md        # The HTTP surface of the admin space
│   └── console.md       # monica:make-administrator
├── checklists/
│   └── requirements.md  # Written by /speckit-checklist
├── spec.md              # The feature specification
└── tasks.md             # Phase 2 output, written by /speckit-tasks
```

### Source Code (repository root)

```text
app/
├── Actions/
│   ├── ApproveMarketplaceListingVersion.php     # changed: takes a User, locks, emails, logs
│   ├── RejectMarketplaceListingVersion.php      # changed: same
│   ├── ActivateUser.php                         # new
│   ├── DeactivateUser.php                       # new
│   ├── GrantAdministratorAccess.php             # new
│   ├── GrantLifetimeAccess.php                  # new
│   ├── UpdateAccountStorageLimit.php            # new
│   ├── WithdrawAdministratorAccess.php          # new
│   └── WithdrawLifetimeAccess.php               # new
├── Console/Commands/
│   └── MakeAdministratorCommand.php             # new
├── Enums/
│   ├── AdministratorActionEnum.php              # new
│   └── EmailType.php                            # changed: two cases
├── Http/
│   ├── Controllers/App/Admin/
│   │   ├── AccountController.php                # new
│   │   ├── AccountLifetimeAccessController.php  # new
│   │   ├── AccountStorageLimitController.php    # new
│   │   ├── AdministratorController.php          # new
│   │   ├── AdministratorLogController.php       # new
│   │   ├── ModerationApprovalController.php     # new
│   │   ├── ModerationController.php             # new
│   │   ├── ModerationRejectionController.php    # new
│   │   ├── OverviewController.php               # new
│   │   └── UserActivationController.php         # new
│   └── Middleware/
│       ├── EnsureUserIsActive.php               # new
│       └── EnsureUserIsAdministrator.php        # new
├── Jobs/
│   └── LogAdministratorAction.php               # new
├── Mail/
│   ├── MarketplaceListingApproved.php           # new
│   └── MarketplaceListingRejected.php           # new
├── Models/
│   ├── Administrator.php                        # new
│   ├── AdministratorLog.php                     # new
│   └── User.php                                 # changed: administrator(), isAdministrator()
└── ViewModels/Admin/
    ├── AccountsViewModel.php                    # new
    ├── AccountViewModel.php                     # new
    ├── AdministratorLogsViewModel.php           # new
    ├── AdministratorsViewModel.php              # new
    ├── ModerationQueueViewModel.php             # new
    ├── ModerationSubmissionViewModel.php        # new
    └── OverviewViewModel.php                    # new

bootstrap/app.php                                # changed: two middleware aliases
routes/web.php                                   # changed: the admin group

database/
├── factories/
│   ├── AdministratorFactory.php                 # new
│   └── AdministratorLogFactory.php              # new
└── migrations/
    ├── 2026_08_21_000000_create_administrators_table.php        # new
    └── 2026_08_21_000001_create_administrator_logs_table.php    # new

resources/views/
├── layouts/admin.blade.php                      # new
└── app/admin/
    ├── accounts/{index,show}.blade.php          # new
    ├── administrators/index.blade.php           # new
    ├── logs/index.blade.php                     # new
    ├── moderation/{index,show}.blade.php        # new
    └── overview/index.blade.php                 # new

resources/views/mail/                            # two new templates

tests/
├── Feature/Controllers/App/Admin/               # one per controller, plus AdminAccessTest
├── Feature/Views/Layouts/AdminLayoutTest.php    # new
└── Unit/
    ├── Actions/                                 # seven new, two changed
    ├── Console/Commands/MakeAdministratorCommandTest.php
    ├── Enums/AdministratorActionEnumTest.php
    ├── Jobs/LogAdministratorActionTest.php
    ├── Mail/                                    # two new
    ├── Models/                                  # two new, one changed
    └── ViewModels/Admin/                        # seven new

lang/*.json                                      # nine locales, via monica:localize
```

**Structure Decision**: the existing Laravel layout, unchanged. The admin space is a new folder
inside `app/Http/Controllers/App/`, a new folder inside `app/ViewModels/`, and a new folder
inside `resources/views/app/`. No new top level folder under `app/`, so no constitutional
amendment is needed for the structure. The admin space is a fourth layout beside `account`,
`shell`, `guest` and `marketplace`, which is how this codebase already answers "a different kind
of screen".

## Implementation Order

The five user stories ship independently, in priority order. Each is a working slice.

1. **Foundation** (needed by everything): the `administrators` table and model, `isAdministrator()`,
   the two middlewares, the aliases, the route group, the admin layout, the
   `monica:make-administrator` command, and the whole record of administrator actions:
   `administrator_logs`, the model, the enum, the job and the screen that reads it. The record
   belongs here rather than after story 1, because FR-028 admits no exception and story 1's
   decisions have to land in it from the first one. At the end of this step an administrator can
   reach an admin space holding an empty record, and nobody else can tell it exists.
2. **Story 1, P1**: the moderation queue, the submission screen, the two decision controllers, the
   changes to the two existing actions, the two mailables. Turns dead code into a feature.
3. **Story 2, P2**: the account lookup and the account screen. Depends on the foundation only.
4. **Story 3, P2**: the five account actions, each behind a warning.
5. **Story 4, P2**: the administrator list, the grant and the withdrawal.
6. **Story 5, P3**: the overview, which becomes the landing screen of the area.
7. **Close**: `php artisan monica:localize`, translate all nine locales, Pint, PHPStan, the full
   suite on the three databases.

`/speckit-tasks` turns this into the task list.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|---|---|---|
| Two tables rather than one boolean column | The capacity has to carry who granted it and when (FR-009), stay out of every mass assignable path (FR-004), and the operator record has to be append only and separate from the user journal (FR-030, FR-031). | A boolean on `users` loses the provenance and puts the most dangerous flag in the product inside the most mass assigned table. Reusing `logs` risks one forgotten `where` showing a user what an operator did to them. |
