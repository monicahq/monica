# Implementation Plan: Instance Configuration

**Branch**: `036-instance-configuration` | **Date**: 2026-09-17 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/036-instance-configuration/spec.md`

## Summary

A configuration section in the admin space, holding the operator decisions the product already names plus its outbound service and sign in provider credentials. Values live in one encrypted table, a service provider overrides `config()` with them at boot so no reading code changes, and a sixty second cache bounds how stale a queue worker can be. Writes sit behind the credential proof the security screen already uses and are recorded in the administrator record. The screen is the only surface: the JSON API this plan once described was removed on the author's instruction.

## Technical Context

**Language/Version**: PHP 8.4, Laravel (current major)

**Primary Dependencies**: none added. Whether an optional package is present is read through configuration per the constitution, never from a bare `class_exists`.

**Storage**: one table, `instance_settings`. Values encrypted at rest with the framework's `encrypted` cast.

**Testing**: PHPUnit. Unit for the actions, the enums, the helper and the view model; Feature for the web controllers and the screen's contract.

**Target Platform**: the responsive web application

**Project Type**: server rendered Blade with an Alpine layer, as the constitution fixes

**Performance Goals**: one cached read per boot, no query per setting per request

**Constraints**: a web request obeys a change immediately; a queue worker within sixty seconds (FR-003). The suite passes on SQLite, MySQL and PostgreSQL, so no driver specific SQL.

**Scale/Scope**: 41 settings across 8 groups, 1 screen, 4 web routes

## Constitution Check

*GATE: passed before Phase 0, re-checked after Phase 1.*

| Principle | How this satisfies it |
| --- | --- |
| I. Actions hold the logic | `UpdateInstanceSetting`, `RevertInstanceSetting` and `RemoveOrphanedInstanceSetting`. The controller delegates and holds no rule. |
| II. Simplicity | The provider override means 91 existing call sites change by zero lines. No repository, no helper, no second config store. |
| III. Tested before merged | A test per action, per enum, per model, per helper, per listener, per view model and per controller, plus the propagation bound. |
| IV. Laravel conventions | `up()` only, no `constrained()`, inline validation, camelCase route names, kebab-case URLs, one class per file. |
| V. Localized and safe | Every string through `__()`, `monica:localize` run for all locales. FR-012's warning is the destructive-action warning `CLAUDE.md` requires. |
| VI. Nothing changes underfoot | FR-008: a change governs what happens next and never rewrites what is fixed. The account storage ceiling stays snapshotted, as it already is. |
| VII. Stable core | Adds no personalisation mechanism. Operator settings are a fixed catalogue in an enum, not user invented data. |
| VIII. Nothing disappears | FR-013: a stored value for a dropped setting is kept and shown as no longer applying. A secret is replaced, never read back, and its record entry omits the value. |
| IX. Accessible | One form of ordinary controls. The read only settings stay rendered and `aria-disabled` with `aria-describedby` naming the reason, as `.claude/rules/policies.md` requires of a refused control. |
| X. Everything through the API | **Not satisfied.** The API was removed on the author's instruction after implementation, so the screen can do what the API cannot. Reversing this means restoring the controller, the resource, the routes and the reference page. |
| XI. Written record is short | spec 102, plan under 100, research 65, data model under 60. The contracts file went with the API. |

**Gate result**: principle X is knowingly unmet, recorded under Complexity Tracking below. Everything else passes.

## Project Structure

### Documentation (this feature)

```text
specs/036-instance-configuration/
├── plan.md
├── research.md
├── data-model.md
├── quickstart.md
├── checklists/requirements.md
└── spec.md
```

### Source Code (repository root)

```text
app/
├── Actions/                     UpdateInstanceSetting, RevertInstanceSetting,
│                                RemoveOrphanedInstanceSetting
├── Enums/                       InstanceSettingEnum, InstanceSettingKindEnum,
│                                InstanceSettingGroupEnum, + 2 AdministratorActionEnum cases
├── Http/Controllers/App/Admin/  InstanceSettingController
├── Listeners/                   ReapplyInstanceSettings (on JobProcessing)
├── Models/                      InstanceSetting
├── Providers/                   AppServiceProvider: applyInstanceSettings()
└── ViewModels/Admin/            ConfigurationViewModel

database/migrations/             create_instance_settings_table
database/factories/              InstanceSettingFactory
resources/views/app/admin/configuration/index.blade.php
resources/views/layouts/admin.blade.php   (one nav entry)
routes/web.php
tests/Unit/{Actions,Enums,Models,ViewModels,Listeners,Providers}/
tests/Feature/Controllers/App/Admin/
tests/Feature/Views/App/Admin/
```

**Structure Decision**: every file lands in a folder the constitution already enumerates. No new top-level folder, so no amendment is owed. The admin web controller joins the existing `App/Admin` namespace.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| Principle X: a capability exists on the screen and not through the API | The author asked for the API to be removed after it had been built and tested | None was rejected on technical grounds. The endpoints existed, passed their tests and were deleted on instruction. Restoring them is transport only, since every rule already lives in the actions |
