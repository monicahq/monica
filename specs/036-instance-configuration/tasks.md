# Tasks: Instance Configuration

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md),
[data-model.md](data-model.md), [quickstart.md](quickstart.md)

**Format**: `- [ ] [ID] [P?] [Story?] description with the file path`. `[P]` means a different file with
nothing incomplete in front of it.

**Tests are not optional here.** Principle III makes a test part of the change rather than a decision,
so every phase carries its own.

**Shape of this file**: Principle XI asks for one line per task, phase headings and the dependency list,
so the template's parallel examples, strategy and notes sections are left out.

**No amendment is owed.** Every folder this feature touches is already enumerated in the constitution,
unlike feature 035. Nothing here blocks on a governance change.

## Phase 1: Setup

**Purpose**: the table and the catalogue every other phase reads.

- [X] T001 Create the migration `database/migrations/*_create_instance_settings_table.php` with `id`, `name` (string, unique), `value` (text, nullable) and timestamps, `up()` only and no `constrained()`, per [data-model.md](data-model.md)
- [X] T002 [P] Create `app/Models/InstanceSetting.php` with `#[Fillable(['name', 'value'])]`, the `encrypted` cast on `value`, and a `booted()` that forgets the settings cache entry on save and on delete so no caller can forget it
- [X] T003 [P] Create `database/factories/InstanceSettingFactory.php`
- [X] T004 [P] Create `tests/Unit/Models/InstanceSettingTest.php` asserting the `encrypted` cast survives a round trip, that a saved row and a deleted row each forget the settings cache entry, and that `name` is unique
- [X] T005 [P] Create `app/Enums/InstanceSettingKindEnum.php` with `Integer`, `Boolean`, `Url`, `Text` and `Secret`, each returning its validation rules per [data-model.md](data-model.md)
- [X] T006 [P] Create `app/Enums/InstanceSettingGroupEnum.php` with the eight groups of [data-model.md](data-model.md), implementing `Translatable`
- [X] T007 Create `app/Enums/InstanceSettingEnum.php`, one case per setting, each returning `group()`, `kind()`, `configKey()` and a one sentence `description()`, and a `fixedAtDeployment()` true for what the instance is, which release it runs and whether it is self hosted
- [X] T008 [P] Create `tests/Unit/Enums/InstanceSettingEnumTest.php` asserting every case names a configuration key that resolves, that no two cases share one, and that every case carries a description

## Phase 2: Foundational (blocking prerequisites)

**Purpose**: stored values reaching the 91 call sites that read configuration, and reaching them fast enough. Every story rides on this and none can start before it.

- [X] T009 Add `applyInstanceSettings()` to `app/Providers/AppServiceProvider.php`, delegating to `app/Helpers/InstanceSettings.php`, which reads the table through `Cache::remember` with a sixty second lifetime, stashes what configuration already held under a parallel runtime key, then calls `config()->set()` for each stored value, per [research.md](research.md)
- [X] T010 Create `app/Listeners/ReapplyInstanceSettings.php` on `Illuminate\Queue\Events\JobProcessing` calling the same routine, so a long-lived `queue:work` process is at most sixty seconds stale, and register it
- [X] T011 Add `InstanceSettingUpdated` and `InstanceSettingReverted` to `app/Enums/AdministratorActionEnum.php` with their descriptions, following the nine cases already there
- [X] T012 Create `tests/Unit/Helpers/InstanceSettingsTest.php` (the logic lives in `app/Helpers/InstanceSettings.php`, which the provider and the listener both call) asserting a stored value overrides the deployment one (FR-014), that the deployment value stays readable after the override, and that a setting with no row leaves configuration untouched
- [X] T013 [P] Create `tests/Unit/Listeners/ReapplyInstanceSettingsTest.php` asserting a change made after the listener's process booted is in force on the next job once the cache lifetime has passed, and that a write forgets the entry so a web request sees it at once (FR-003)

**Checkpoint**: a row written by hand changes how the application behaves, on the web and on the queue. Nothing can write one yet.

## Phase 3: User Story 1 (P1) - Change a setting from the screen 🎯 MVP

**Goal**: an administrator changes any ordinary setting and it governs the next request.

**Independent test**: lower the free contact limit from the screen, then try to exceed it as an ordinary user and confirm the new limit is the one enforced.

- [X] T014 [US1] Create `app/Actions/UpdateInstanceSetting.php` taking the promoted readonly administrator, the enum case and the value, validating by kind, enforcing the two cross-setting rules of [data-model.md](data-model.md), writing the row and recording the administrator record entry
- [X] T015 [US1] Create `tests/Unit/Actions/UpdateInstanceSettingTest.php` covering a value accepted, each kind's refusal, both cross-setting refusals, a setting fixed at deployment refused (FR-007), and the record entry written
- [X] T016 [P] [US1] Create `tests/Unit/Actions/UpdateInstanceSettingRetroactivityTest.php` asserting a lowered free storage limit leaves an existing account's snapshotted ceiling untouched and governs only accounts created afterwards (FR-008, first edge case)
- [X] T017 [US1] Create `app/ViewModels/Admin/ConfigurationViewModel.php` returning the settings by group, each with its value, its deployment value, its description, whether it is changed and whether it is changeable, following `app/ViewModels/Admin/OverviewViewModel.php`
- [X] T018 [P] [US1] Create `tests/Unit/ViewModels/Admin/ConfigurationViewModelTest.php` asserting the grouping, the changed flag and that a fixed setting reports itself unchangeable
- [X] T019 [US1] Create `app/Http/Controllers/App/Admin/InstanceSettingController.php` with `index` and `update`, thin, delegating to the action and answering `back()->withErrors()` on refusal
- [X] T020 [US1] Declare `admin/configuration` and `admin/configuration/{setting}` in `routes/web.php` inside the existing `administrator` group, the write wrapped in `password.confirm:auth.confirmPassword.new` exactly as the security routes are (FR-012)
- [X] T021 [US1] Create `resources/views/app/admin/configuration/index.blade.php`, its first line the `{{-- --}}` comment and its `@var` block, drawing each group with the components listed in `.claude/rules/components.md` rather than fresh markup
- [X] T022 [US1] Add the Configuration entry to the nav array in `resources/views/layouts/admin.blade.php`, beside Overview, Moderation, Accounts, Administrators and Record
- [X] T023 [US1] Create `tests/Feature/Controllers/App/Admin/InstanceSettingControllerTest.php` covering the screen rendering, a change saved, a refusal leaving the stored value untouched, and the record entry
- [X] T024 [P] [US1] Add the two configuration URLs to the `#[DataProvider]` in `tests/Feature/Controllers/App/Admin/AdminAccessTest.php` so a visitor, a regular user and a deactivated administrator all get not found
- [X] T025 [P] [US1] Create `tests/Feature/Views/App/Admin/ConfigurationTest.php` asserting the copy, the form actions, the field names and the accessibility contract, and never a class string, per `.claude/rules/testing.md`
- [X] T026 [US1] Warn before saving a change that switches a capability off while something it issued is outstanding, or that shortens how long data is kept, naming what it affects, as Principle V requires of a destructive action, and cover both in `tests/Feature/Views/App/Admin/ConfigurationTest.php` (FR-012)

**Checkpoint**: the MVP. Every ordinary setting is changeable from the screen, proved, recorded and refused correctly.

## Phase 4: User Story 1 (P1, second increment) - The outbound services

**Goal**: the mail transport, the text message provider and the payment provider are configurable, and a wrong one cannot be saved.

**Independent test**: point the mail transport at a host that does not answer, confirm the save is refused with what the transport said, and confirm a password reset link still arrives.

- [X] T027 [US1] Create `app/Contracts/TestsAnOutboundService.php` (renamed from `OutboundServiceTest`, which read as a PHPUnit class) declaring the one method, taking the submitted values and answering whether the service accepted them and what it said
- [X] T028 [P] [US1] Create `app/Actions/TestMailTransport.php` building a transport from the submitted values rather than the live configuration and sending to the administrator's own address, per [research.md](research.md)
- [X] T029 [P] [US1] Create `app/Actions/TestTextMessageProvider.php` exercising the provider with the submitted values
- [X] T030 [P] [US1] Create `app/Actions/TestPaymentProvider.php` exercising the provider with the submitted values
- [X] T031 [US1] Extend `app/Actions/UpdateInstanceSetting.php` to run the matching test before writing an outbound service's value, and to refuse with what the service answered (FR-015)
- [X] T032 [US1] Teach `app/ViewModels/Admin/ConfigurationViewModel.php` and the screen that a secret is write only: reported as set or unset, never shown back, an empty submission leaving it alone, with its own control to clear it (FR-011)
- [X] T033 [US1] Make a provider whose package is absent report itself unavailable, refuse to be switched on and never be named as a delivery driver, reading availability through configuration and not a bare `class_exists` (FR-016)
- [X] T034 [US1] Amend `app/Actions/UpdateInstanceSetting.php` so a secret's record entry holds that it changed and neither value (FR-006), and extend `tests/Unit/Actions/UpdateInstanceSettingTest.php` to prove it
- [X] T035 [P] [US1] Create `tests/Unit/Actions/TestMailTransportTest.php`, `TestTextMessageProviderTest.php`, `TestPaymentProviderTest.php` and `TestChatProviderTest.php` (a fourth service, Telegram, which FR-015 covers too), each proving the service is exercised with the submitted values and not the live ones, and each proving a refusal carries what the service said

**Checkpoint**: the terminal is no longer needed for mail, and the screen cannot lock an operator out of their own instance.

## Phase 5: User Story 2 (P2) - See where a value comes from and put it back

**Goal**: a changed setting is distinguishable from a default and can be reverted.

**Independent test**: change a setting, confirm the screen marks it as changed and names the deployment value, revert it, and confirm the mark and the behaviour both return.

- [X] T036 [US2] Create `app/Actions/RevertInstanceSetting.php` deleting the row and recording the reversion, treating a setting nobody changed as already reverted rather than an error
- [X] T037 [P] [US2] Create `tests/Unit/Actions/RevertInstanceSettingTest.php` covering the delete, the record entry, and the reversion of a setting with no row
- [X] T038 [US2] Add `destroy` to `app/Http/Controllers/App/Admin/InstanceSettingController.php` and its route to `routes/web.php`, inside the same proof-gated group
- [X] T039 [US2] Show on the screen, per setting, the deployment value and whether the stored one differs, saying plainly where a disagreeing environment value is no longer obeyed (FR-014)
- [X] T040 [US2] Create `app/Actions/RemoveOrphanedInstanceSetting.php` and show a stored row whose name no setting carries in a group of its own, kept and marked as no longer applying, with removal its only control (FR-013)
- [X] T041 [P] [US2] Create `tests/Unit/Actions/RemoveOrphanedInstanceSettingTest.php` covering the row deleted, the record entry written, and a name the catalogue still carries refused so a live setting cannot be removed this way
- [X] T042 [P] [US2] Extend `tests/Feature/Controllers/App/Admin/InstanceSettingControllerTest.php` and `tests/Feature/Views/App/Admin/ConfigurationTest.php` with the revert, the disagreement notice and the orphaned group

**Checkpoint**: nothing on the screen is a mystery, and every change has a way back.

## Phase 7: Polish

- [X] T049 Run `php artisan monica:localize` and translate everything it reports, in every configured locale, following `.claude/rules/translations.md` for the locales that declare typography
- [ ] T050 Walk the screen with the keyboard alone, with a screen reader and zoomed to 200%. **Partly done**: `tests/Feature/Views/App/Admin/ConfigurationTest.php` proves a refused control keeps `aria-disabled` and an `aria-describedby` naming its reason, and never carries the `disabled` attribute that would take it out of the tab order. The screen reader, the zoom and the look still need a person on a running instance
- [X] T051 Run `vendor/bin/pint --dirty --format agent`, `vendor/bin/phpstan analyse` and the full suite, the last on SQLite, MySQL and PostgreSQL
- [ ] T052 Work through [quickstart.md](quickstart.md) end to end on a running instance, including the queue worker scenario, which no test in the suite proves against a real long-lived process. **Not done**: this session could not reach a local server, so nothing here was exercised by hand

## Dependencies

- Phase 1 blocks everything. T007 needs T005 and T006.
- Phase 2 blocks every story. T009 blocks T010, T012 and T013.
- Phase 3 needs Phase 2. T014 blocks T019; T017 blocks T021; T019 and T020 block T023; T025 blocks T026, which writes into the file it creates.
- Phase 4 needs Phase 3. T027 blocks T028, T029 and T030. T028 to T030 block T031.
- Phase 5 needs Phase 3, not Phase 4.
- Phase 7 needs everything.

## Removed after implementation, on the author's instruction

- The JSON API (`Api/Admin/InstanceSettingController`, `InstanceSettingResource`, three routes, the reference page, two test files and `contracts/instance-settings.md`). Principle X is unmet while it is gone; see Complexity Tracking in [plan.md](plan.md).
- The pre-save service tests (`TestMailTransport`, `TestTextMessageProvider`, `TestChatProvider`, the `TestsAnOutboundService` contract, their tests and fixtures). A mail transport that cannot be reached can now be saved, and that is what carries the password reset and sign in links.
