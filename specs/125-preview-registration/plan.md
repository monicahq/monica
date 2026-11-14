# Implementation Plan: Preview list and admission while registration is closed

**Branch**: `claude/preview-register-landing-0d2a56` | **Date**: 2026-11-14 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `/specs/125-preview-registration/spec.md`

## Summary

A visitor of an instance that takes no new account leaves an email address on a card at the root
address. An administrator admits entries from a new screen of the admin space, one by one or the
oldest N, and each admitted person receives a link that opens the existing sign up screen while
registration stays closed. One table, one instance setting the deployment supplies, four
actions, and one new parameter on `CreateAccount`. The decisions are in [research.md](./research.md),
the table in [data-model.md](./data-model.md), the API change in [contracts/api.md](./contracts/api.md).

## Technical Context

**Language/Version**: PHP 8.4, Laravel (installed major)  
**Primary Dependencies**: Blade, Alpine.js, Laravel notifications (mail channel), Sanctum (API)  
**Storage**: one new table, `preview_list_entries`; the setting uses the existing instance settings table  
**Testing**: PHPUnit, unit and feature, per `.claude/rules/testing.md`  
**Target Platform**: Linux server, hosted and self hosted alike  
**Project Type**: web application with a JSON API  
**Performance Goals**: none beyond today; the root address adds two configuration reads  
**Constraints**: an instance that sets nothing behaves as before (SC-004); no answer of the card tells whether an address is known (SC-005)  
**Scale/Scope**: about 44 files, 14 new classes, one migration, two new views

## Constitution Check

| Principle | Verdict |
| --- | --- |
| I. Actions hold the business logic | Pass. Joining, admitting, admitting the oldest and removing are four actions; sign up stays in `CreateAccount` |
| II. Simplicity | Pass. State is read from two dates, the batch loops the single action, the setting reuses the catalogue |
| III. Tested before merged | Pass. Every action, the model, the notification, the view models and each route get a test |
| IV. Laravel conventions | Pass. `Notifiable` on the model, middleware alias kept, one named route per line, `up()` only |
| V. Localized and safe | Pass. Strings go through `monica:localize`; the email is sent in the entry's language; removing warns first |
| VI. Nothing changes under anybody's feet | Pass. No existing account or setting is touched; the list ships off |
| VIII. Nothing disappears silently | Pass. Switching the list off keeps entries and links; removal is explicit and logged (research D8) |
| IX. Accessible | Pass. The card and the list are built from `x-auth-page`, `x-list-panel` and `x-delete-action` |
| X. Everything through the API | **Deviation**, see Complexity Tracking. Sign up with an admission is covered |
| XI. Short record | Pass. Every artifact is under its cap |
| Product invariants | Pass. An admission adds an account to the instance (spec, clarification); same product hosted and self hosted |

Re-checked after Phase 1: unchanged.

## Project Structure

### Documentation (this feature)

`plan.md`, `research.md`, `data-model.md`, `quickstart.md`, `contracts/api.md`, then `tasks.md` from `/speckit-tasks`.

### Source Code (repository root)

```text
config/monica.php                                        preview_list_enabled from MONICA_PREVIEW_LIST_ENABLED, false
app/Enums/InstanceSettingEnum.php                        PreviewListEnabled
app/Enums/AdministratorActionEnum.php                    PreviewListEntryAdmitted, PreviewListEntryRemoved
database/migrations/…_create_preview_list_entries_table.php, database/factories/PreviewListEntryFactory.php
app/Models/PreviewListEntry.php                          Notifiable, preferredLocale(), findByAdmissionToken()
app/Actions/{JoinPreviewList,AdmitPreviewListEntry,AdmitOldestPreviewListEntries,RemovePreviewListEntry}.php
app/Actions/CreateAccount.php                            optional previewListEntry: address, verified, registered_at
app/Exceptions/AddressAlreadyHoldsAnAccount.php, app/Notifications/PreviewListEntryAdmitted.php
app/Http/Middleware/EnsureRegistrationIsEnabled.php      a usable `admission` input passes
app/Http/Controllers/App/HomeController.php              the card for a visitor
app/Http/Controllers/App/Auth/{PreviewListEntryController,PreviewAdmissionController}.php  create, show
app/Http/Controllers/{App/Auth/RegistrationController,App/Auth/RegistrationPasskeyController,Api/Auth/RegistrationController}.php
app/Http/Controllers/App/Admin/{PreviewListEntryController,PreviewListAdmissionController,PreviewListOldestAdmissionController}.php
app/ViewModels/Auth/RegisterViewModel.php                the admitted address and the token
app/ViewModels/Admin/PreviewListViewModel.php            entries, state label, whether the list is on
routes/auth.php                                          auth.previewList.create, auth.previewAdmission.show
routes/administrator.php                                 admin.previewList.{index,destroy,admission.create,oldestAdmissions.create}
resources/views/app/auth/preview-list.blade.php          new
resources/views/app/auth/register.blade.php              read only address, hidden admission
resources/views/app/admin/preview-list/index.blade.php   new
resources/views/layouts/admin.blade.php                  the Preview list entry of the navigation
resources/docs/api/02-authentication.php, docs/FEATURES.md, lang/*.json (through monica:localize only)

tests/Unit/Actions/{JoinPreviewList,AdmitPreviewListEntry,AdmitOldestPreviewListEntries,RemovePreviewListEntry,CreateAccount}Test.php
tests/Unit/Models/PreviewListEntryTest.php               the usable link, in each of its refusals
tests/Unit/Notifications/PreviewListEntryAdmittedTest.php
tests/Unit/ViewModels/{Admin/PreviewListViewModelTest,Auth/RegisterViewModelTest}.php
tests/Unit/Enums/InstanceSettingEnumTest.php
tests/Feature/Controllers/App/HomeControllerTest.php
tests/Feature/Controllers/App/Auth/{PreviewListEntryController,PreviewAdmissionController,RegistrationController,RegistrationPasskeyController}Test.php
tests/Feature/Controllers/Api/Auth/RegistrationControllerTest.php
tests/Feature/Controllers/App/Admin/{PreviewListEntryController,PreviewListAdmissionController,PreviewListOldestAdmissionController}Test.php
tests/Feature/Controllers/App/Admin/AdminAccessTest.php  the four new addresses
tests/Feature/Controllers/App/Admin/InstanceSettingControllerTest.php  (dropped: covered by `it_saves_a_change`)
tests/Feature/RouteProtectionTest.php                    PreviewListEntry in modelsScopedByTheirController
```

**Structure Decision**: the existing Laravel layout; nothing new at the top level.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
| --- | --- | --- |
| Principle X: joining the list and managing it have no API endpoint | Decided in the spec on 2026-11-14; the admin space has no API today, support requests excepted | Adding an admin API for one screen. The logic sits in four actions, so the endpoints are transport when they come. The pull request must state this |
