# Implementation Plan: Feature flags

**Branch**: `2026-10-04-laravel-pennant` | **Date**: 2026-10-04 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/068-laravel-pennant-feature-flags/spec.md`

## Summary

Install Laravel Pennant on the database store, scope every flag to the account, and let the instance
administrator switch a flag for one account or for every account from a command or an admin screen,
each switch written to the administrator log. The marketplace is the first flag, off for every account when it ships; a test flag
proves the mechanism. Decisions are in [research.md](research.md), interfaces in
[contracts/flags.md](contracts/flags.md).

## Technical Context

**Language/Version**: PHP 8.4, Laravel 13

**Primary Dependencies**: `laravel/pennant ^1.26` (new, `require`)

**Storage**: Pennant's `features` table, from the migration the package publishes, kept as shipped

**Testing**: PHPUnit, `RefreshDatabase`, database store in tests (R1)

**Target Platform**: hosted and self hosted alike (FR-007)

**Project Type**: server rendered web application (Blade, Alpine)

**Performance Goals**: one row read per flag per account per request, cached by Pennant

**Constraints**: same answer for an account on every surface (R2); nothing deleted by switching off (FR-004)

**Scale/Scope**: one shipped flag (marketplace), five routes, two commands, four actions, one middleware

## Constitution Check

| Principle | Verdict |
| --- | --- |
| I. Actions | Four actions hold the switches; controllers and commands only delegate |
| II. Simplicity | No base class: each flag's `resolve()` is one line (R3) |
| III. Tested | Every action, command, controller, view model and the middleware get a test |
| IV. Laravel way | Pennant as documented; own middleware instead of Pennant's, justified in R5 |
| V. Localized, safe | Admin copy through `__()` and `monica:localize`; "every account" switches warn first |
| VI. Nothing changes under feet | A flag gates reachability; it never edits a version or a record |
| VII. Stable core | No concept added for account holders |
| VIII. Nothing disappears | Switching off hides, never deletes (FR-004) |
| IX. Accessible | Switches are buttons in forms, keyboard reachable, state given in words |
| X. API | The admin area has no API; the storage limit and activation controls set the precedent |
| XI. Short | spec 120 lines, at the cap; plan under 100 |
| Same product everywhere | Flags exist on both deployments and never read the plan (FR-007) |
| Layout | `app/Features` admitted by constitution 2.5.0 (#316) |

Gate passes before research and again after design. No policy: the `administrator` middleware is the
only refusal, and it is stated once.

## Project Structure

### Documentation (this feature)

`spec.md`, `plan.md`, `research.md`, `quickstart.md`, `contracts/flags.md`. `data-model.md` is
omitted: the only table is Pennant's, described in R3.

### Source Code (repository root)

```text
composer.json, composer.lock          laravel/pennant
config/pennant.php                    published, default store database
database/migrations/*_create_features_table.php   published, as shipped
app/Features/.gitkeep                 discover() needs the folder to exist
app/Models/Account.php                uses HasFeatures
app/Providers/AppServiceProvider.php  configureFlags(): Feature::discover()
app/Actions/ActivateFlag.php, DeactivateFlag.php                               one account
app/Actions/ActivateFlagForEveryAccount.php, DeactivateFlagForEveryAccount.php
app/Actions/DestroyAccount.php        forgets the account's decisions (R7)
app/Enums/AdministratorActionEnum.php four cases
app/Http/Middleware/EnsureFlagIsOn.php, bootstrap/app.php (alias flag)
app/Http/Controllers/App/Admin/FlagController.php (index)
app/Http/Controllers/App/Admin/FlagActivationController.php, AccountFlagActivationController.php
app/ViewModels/Admin/FlagsViewModel.php, AccountViewModel.php (flags for one account)
app/Console/Commands/ListFlagsCommand.php, SwitchFlagCommand.php
resources/views/app/admin/flags/index.blade.php, accounts/show.blade.php, layouts/admin.blade.php
routes/administrator.php, lang/*.json
.claude/rules/flags.md, .claude/rules/index.md   how to declare, ask and retire a flag (FR-007, FR-008)
tests/Fixtures/Features/ExampleCapability.php
tests/Unit/{Actions,Console/Commands,Enums,ViewModels/Admin}/...
tests/Feature/EnsureFlagIsOnTest.php  a test route behind flag:example-capability, like TrustedHostsTest
tests/Feature/Controllers/App/Admin/{Flag,FlagActivation,AccountFlagActivation}ControllerTest.php, AdminAccessTest.php
```

**Structure Decision**: existing folders only, plus `app/Features`. Each action takes the flag name, the
account where it has one, and a nullable administrator so a command logs as the console. An unknown
flag name throws `ModelNotFoundException`, which the controllers turn into 404 and the commands into
exit code 1.

## Complexity Tracking

None.
