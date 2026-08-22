---

description: "Task list for the Admin Space feature"
---

# Tasks: Admin Space

**Input**: Design documents from `/specs/001-admin-space/`

**Prerequisites**: [plan.md](./plan.md), [spec.md](./spec.md), [research.md](./research.md), [data-model.md](./data-model.md), [contracts/](./contracts/)

**Tests**: included and mandatory. Constitution principle III is non negotiable: every model,
controller, action, helper, job, mailable and view model has a test, and the suite passes before
merge. The test tasks in each phase are part of that phase, not a later cleanup. The constitution
requires the tests to exist and pass before merge, not that they be written before the code, so
either order works inside a phase.

**Organization**: grouped by user story, in the priority order the spec sets. Each story phase is
a working slice that can be validated on its own.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: can run in parallel (different files, no dependency on an unfinished task)
- **[Story]**: which user story the task serves (US1 to US5)
- Every task names the file it touches

## Path Conventions

Existing Laravel application at the repository root. Actions in `app/Actions/`, models in
`app/Models/`, controllers in `app/Http/Controllers/App/Admin/`, view models in
`app/ViewModels/Admin/`, views in `resources/views/app/admin/`, tests in `tests/Unit/` and
`tests/Feature/`, mirroring the application namespace.

---

## Phase 1: Setup

**Purpose**: know the ground is level before laying anything on it

- [X] T001 Confirm the baseline on branch `2026-08-20-admin-space`: `./vendor/bin/phpunit` green, `./vendor/bin/pint --test` clean, `./vendor/bin/phpstan analyse` clean, `php artisan monica:localize --check` reporting nothing missing. Record anything already failing so it is not blamed on this feature.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: the capacity, the door, the layout, the record and the way in. No user story is
reachable or testable until this is done.

**⚠️ CRITICAL**: every story below depends on this phase

### Database

- [X] T002 [P] Create migration `database/migrations/2026_08_21_000000_create_administrators_table.php` per data-model.md: `user_id` unique with a foreign key cascading on delete, `granted_by_user_id` nullable with a foreign key nulling on delete, timestamps, a comment on every column, `up()` only
- [X] T003 [P] Create migration `database/migrations/2026_08_21_000001_create_administrator_logs_table.php` per data-model.md: `user_id` nullable, `user_email`, `action`, `parameters` json, `account_id` nullable and indexed, `subject_type` and `subject_id` nullable with no foreign key, `ip_address`, `created_at` indexed, a comment on every column, `up()` only

### Models, factories and the enum

- [X] T004 [P] Create `app/Models/Administrator.php`: `$table`, `$fillable`, the `user()` and `grantedBy()` belongs to relations with generic annotations, and a class docblock explaining why the capacity is a row rather than a column
- [X] T005 [P] Create `app/Models/AdministratorLog.php`: casts for `parameters`, the `user()`, `account()` and `subject()` relations, the `author` and `description` accessors mirroring `app/Models/Log.php`, and `booted()` throwing `App\Exceptions\LogIsAppendOnly` on update and on delete
- [X] T006 [P] Create `database/factories/AdministratorFactory.php`
- [X] T007 [P] Create `database/factories/AdministratorLogFactory.php`
- [X] T008 [P] Create `app/Enums/AdministratorActionEnum.php`: the nine cases in data-model.md, implementing `App\Contracts\Translatable`, with a `description()` sentence per case and `translationKeys()` returning it
- [X] T009 Add `administrator(): HasOne` and `isAdministrator(): bool` to `app/Models/User.php`, where `isAdministrator()` is true only when the relation exists and `is_active` is true (depends on T004)
- [X] T010 Create `app/Jobs/LogAdministratorAction.php` mirroring `app/Jobs/LogUserAction.php`: reads the IP address and the subject in the constructor, writes an `AdministratorLog` row in `handle()`, falls back to `Log::SYSTEM` for `user_email` when no administrator is given (depends on T005, T008)

### The door

- [X] T011 [P] Create `app/Http/Middleware/EnsureUserIsAdministrator.php`: `abort(404)` for a guest, for a signed in user who holds no administrator row, and for one who is not active. No `403` and no redirect, per FR-002 and SC-005
- [X] T012 [P] Create `app/Http/Middleware/EnsureUserIsActive.php`: sign out, invalidate the session, regenerate the token and redirect to `auth.login.new` when the signed in user has `is_active` false, so FR-023 reaches a session already open
- [X] T013 Register the `administrator` and `active` aliases in `bootstrap/app.php` (depends on T011, T012)
- [X] T014 Add `active` to the existing authenticated route group in `routes/web.php`, and open the admin group `Route::middleware(['administrator', 'set.locale'])->prefix('admin')` with the `admin.logs.index` route in it, plus a root route at `admin` redirecting to `admin.logs.index` so the area has a front door before the overview exists. T095 replaces that redirect with the overview controller (depends on T013)

### The layout

- [X] T015 [P] Create `resources/views/layouts/admin.blade.php`: its own bar marking the area as the admin space, navigation across the five screens, no dock, no link into a vault, and a docblock in the style of `resources/views/layouts/account.blade.php` explaining what separates it from the other three (FR-010)

### The way in

- [X] T016 Create `app/Actions/GrantAdministratorAccess.php`: takes the user to grant and the administrator granting it (nullable, since the console has nobody), refuses when the capacity is already held, creates the row and dispatches `LogAdministratorAction` on the `low` queue (depends on T004, T010)
- [X] T017 Create `app/Console/Commands/MakeAdministratorCommand.php` for `monica:make-administrator {email}` per contracts/console.md: delegates to the action, holds no logic of its own, refuses politely when no user has that address or already holds the capacity, confirms on success (depends on T016)

### The record of administrator actions (FR-028 to FR-031)

- [X] T018 [P] Create `app/ViewModels/Admin/AdministratorLogsViewModel.php`: most recent first, `simplePaginate`, optional filters on administrator and on account read from the query string (depends on T005)
- [X] T019 Create `app/Http/Controllers/App/Admin/AdministratorLogController.php` with `index` (depends on T018)
- [X] T020 Create `resources/views/app/admin/logs/index.blade.php` on the admin layout: who, what, which account, when, and the two filters. Read only, with nothing that edits or deletes an entry (depends on T015, T019)

### Tests for the foundation

- [X] T021 [P] Create `tests/Unit/Models/AdministratorTest.php`: both relations exist
- [X] T022 [P] Create `tests/Unit/Models/AdministratorLogTest.php`: the relations, the `author` fallback chain including the console case, the `description` accessor, and that an update and a delete each throw `LogIsAppendOnly`
- [X] T023 [P] Add tests to `tests/Unit/Models/UserTest.php`: `isAdministrator()` true for an active holder, false for somebody with no row, false for a holder who is not active
- [X] T024 [P] Create `tests/Unit/Enums/AdministratorActionEnumTest.php`: every case has a description, and confirm `tests/Unit/Enums/TranslatableEnumsTest.php` picks the new enum up
- [X] T025 [P] Create `tests/Unit/Jobs/LogAdministratorActionTest.php`: writes the row, keeps the subject type and id of something since deleted, records `Log::SYSTEM` when no administrator is given
- [X] T026 [P] Create `tests/Unit/Actions/GrantAdministratorAccessTest.php`: creates the row, records who granted it, dispatches the log job on the `low` queue, refuses a second grant to the same person
- [X] T027 [P] Create `tests/Unit/Console/Commands/MakeAdministratorCommandTest.php`: grants the capacity, works on an instance with no administrator at all, writes a log entry attributed to the system, fails on an unknown address, fails when the capacity is already held
- [X] T028 [P] Create `tests/Feature/Controllers/App/Admin/AdminAccessTest.php`: every admin URL that exists answers `404` to a guest, to a signed in user with no capacity, and to a holder who is not active, and is reachable for an active holder (a `200`, or the redirect the root carries until T095). This is the SC-005 test and grows a case as each story adds a route
- [X] T029 [P] Create `tests/Feature/Controllers/App/Admin/AdministratorLogControllerTest.php`: lists most recent first, filters by administrator, filters by account, pages
- [X] T106 [P] Create `tests/Unit/ViewModels/Admin/AdministratorLogsViewModelTest.php`: most recent first, the administrator filter, the account filter, both filters together, and paging
- [X] T030 [P] Create `tests/Feature/Views/Layouts/AdminLayoutTest.php`: renders, says it is the admin space, and offers no link into a vault
- [X] T031 [P] Add the way into the admin space to `resources/views/components/bar-links.blade.php`, shown only to a user for whom `isAdministrator()` is true, and test in `tests/Feature/Views/Components/BarLinksTest.php` that a regular user, a signed out visitor and a deactivated holder are shown nothing at all (FR-002, and the assumption that regular users keep no visibility of the area)
- [X] T108 [P] Create `tests/Feature/Controllers/App/Admin/NoSelfServiceCapacityTest.php`: registering a new account creates no `administrators` row, posting administrator-shaped fields to the profile update route creates none, and the newly registered user gets a `404` on every admin URL. This is the FR-004 test

**Checkpoint**: an administrator designated from the console can reach the admin space and read
an empty record of actions. Everybody else gets a `404`. A deactivated user is signed out on
their next request.

---

## Phase 3: User Story 1 - Moderate marketplace submissions (Priority: P1) 🎯 MVP

**Goal**: an administrator sees every submission waiting, reads what the author submitted, and
approves it or rejects it with a reason the author reads. The decision is recorded against them.

**Independent Test**: sign in as an administrator, submit a listing as a regular user, approve it
from the queue, confirm it appears in the catalogue and the author is told. Repeat with a
rejection and confirm the reason reaches the author.

### Tests for User Story 1

- [X] T032 [P] [US1] Update `tests/Unit/Actions/ApproveMarketplaceListingVersionTest.php` for the new signature, and add: the decision is attributed to the administrator's name, the log job is dispatched, the author is emailed, a second decision on the same version throws `ListingIsNotAwaitingModeration`, a decision on a version the author has withdrawn throws the same, and an existing installation of an earlier version is untouched (FR-017, Story 1 scenarios 2, 5, 6, and the withdrawn submission edge case)
- [X] T033 [P] [US1] Update `tests/Unit/Actions/RejectMarketplaceListingVersionTest.php` for the new signature, and add: the reason is required, the reason reaches the listing where the author reads it, the log job is dispatched, the author is emailed, a decision on a version the author has withdrawn throws `ListingIsNotAwaitingModeration`, and a listing already in the catalogue stays published on its earlier version
- [X] T034 [P] [US1] Create `tests/Unit/Mail/MarketplaceListingApprovedTest.php`: the envelope subject, and `render()` containing the listing name
- [X] T035 [P] [US1] Create `tests/Unit/Mail/MarketplaceListingRejectedTest.php`: the envelope subject, and `render()` containing the reason
- [X] T036 [P] [US1] Create `tests/Unit/ViewModels/Admin/ModerationQueueViewModelTest.php`: oldest first, only versions awaiting a decision, the waiting time, paging
- [X] T037 [P] [US1] Create `tests/Unit/ViewModels/Admin/ModerationSubmissionViewModelTest.php`: exposes the description, the change note and the payload of the version
- [X] T038 [P] [US1] Create `tests/Feature/Controllers/App/Admin/ModerationControllerTest.php`: `404` for a non administrator on both `index` and `show`, the queue lists a waiting submission with author, category, submitted date and waiting time, `show` renders what the author submitted, and a listing rejected then resubmitted appears in the queue as a new submission while the earlier rejection reason stays readable on the version it was given about
- [X] T039 [P] [US1] Create `tests/Feature/Controllers/App/Admin/ModerationApprovalControllerTest.php`: `404` for a non administrator, approval publishes and redirects with a status, a version already decided is refused with its current state stated
- [X] T040 [P] [US1] Create `tests/Feature/Controllers/App/Admin/ModerationRejectionControllerTest.php`: `404` for a non administrator, a missing reason fails validation and nothing is rejected, a reason rejects and redirects, a version already decided is refused

### Implementation for User Story 1

- [X] T041 [P] [US1] Add the `MarketplaceListingApproved` and `MarketplaceListingRejected` cases to `app/Enums/EmailType.php`
- [X] T042 [P] [US1] Create `app/Mail/MarketplaceListingApproved.php` implementing `App\Interfaces\HasEnvelope`, with `resources/views/mail/marketplace-listing-approved.blade.php`
- [X] T043 [P] [US1] Create `app/Mail/MarketplaceListingRejected.php` implementing `App\Interfaces\HasEnvelope`, carrying the reason, with `resources/views/mail/marketplace-listing-rejected.blade.php`
- [X] T044 [US1] Change `app/Actions/ApproveMarketplaceListingVersion.php`: take `User $administrator` instead of `string $moderator`, write `moderated_by` from the administrator's name, lock the version row with `lockForUpdate()` inside the transaction before validating the status, dispatch `LogAdministratorAction`, dispatch `SendEmail` with the approval mailable on the `high` queue (depends on T010, T041, T042)
- [X] T045 [US1] Change `app/Actions/RejectMarketplaceListingVersion.php` the same way, carrying the reason into the mailable (depends on T010, T041, T043)
- [X] T046 [P] [US1] Create `app/ViewModels/Admin/ModerationQueueViewModel.php`: versions awaiting a decision, oldest first, listing and `account.user` eager loaded, author, listing name, category, submitted date, waiting time, `simplePaginate` (FR-011, FR-035)
- [X] T047 [P] [US1] Create `app/ViewModels/Admin/ModerationSubmissionViewModel.php`: everything the author submitted for the version, including summary, description, change note and payload (FR-012)
- [X] T048 [US1] Create `app/Http/Controllers/App/Admin/ModerationController.php` with `index` and `show` (depends on T046, T047)
- [X] T049 [P] [US1] Create `app/Http/Controllers/App/Admin/ModerationApprovalController.php` with `create`: delegates to the action, redirects with a status, catches `ListingIsNotAwaitingModeration` and reports the standing state (depends on T044)
- [X] T050 [P] [US1] Create `app/Http/Controllers/App/Admin/ModerationRejectionController.php` with `create`: validates `reason` as required, delegates, handles the same refusal (depends on T045)
- [X] T051 [US1] Add the four moderation routes to the admin group in `routes/web.php` per contracts/routes.md (depends on T048, T049, T050)
- [X] T052 [US1] Create `resources/views/app/admin/moderation/index.blade.php` on the admin layout: the queue, oldest first, with an empty state that reads as nothing waiting rather than as an error (depends on T015, T051)
- [X] T053 [US1] Create `resources/views/app/admin/moderation/show.blade.php`: what the author submitted, the approve control, and the reject form whose reason is required (depends on T015, T051)

**Checkpoint**: the marketplace can be moderated by a human. Dead code is now a feature, and
every decision appears in the record from Phase 2.

---

## Phase 4: User Story 2 - Look up an account to answer a support question (Priority: P2)

**Goal**: an administrator finds an account by email address or name and sees the facts needed to
answer a support email, and nothing a user wrote inside a vault.

**Independent Test**: create an account with a vault and some contacts, search for its email
address, confirm every listed fact is shown and correct, and that no contact name, field value or
uploaded file is reachable from that screen.

### Tests for User Story 2

- [X] T054 [P] [US2] Create `tests/Unit/ViewModels/Admin/AccountsViewModelTest.php`: finds by full email, by partial email, by partial first or last name, is case insensitive, returns nothing for nonsense, pages
- [X] T055 [P] [US2] Create `tests/Unit/ViewModels/Admin/AccountViewModelTest.php`: every fact in FR-019 and FR-020, counts only for vaults and contacts, a null storage limit reading as unlimited rather than zero, a self hosted account reading billing email and purchase as not applicable, and an account whose user is soft deleted still resolving
- [X] T056 [P] [US2] Create `tests/Feature/Controllers/App/Admin/AccountControllerTest.php`: `404` for a non administrator on `index` and `show`, a search that matches, a search that does not and says so, the account screen rendering for an account with no vaults and for one whose user is soft deleted
- [X] T057 [P] [US2] Create `tests/Feature/Controllers/App/Admin/NoVaultContentTest.php`: with a vault holding a named contact, a field value and a file, assert the account screen and the search results contain none of those strings and expose no URL to them. This is the SC-006 and FR-021 test

### Implementation for User Story 2

- [X] T058 [P] [US2] Create `app/ViewModels/Admin/AccountsViewModel.php`: reads `?query=`, searches `users` with `whereRaw('lower(col) like ?')` over email, first name and last name, reads users `withTrashed()`, `simplePaginate`, and falls back to the most recently created accounts on an empty term (research.md decision 8)
- [X] T059 [P] [US2] Create `app/ViewModels/Admin/AccountViewModel.php`: the account summary in data-model.md and nothing else. Storage rendered in a unit a human reads, no limit rendered as unlimited, hosted only facts rendered as not applicable on a self hosted account, a deleted user rendered with the deletion stated
- [X] T060 [US2] Create `app/Http/Controllers/App/Admin/AccountController.php` with `index` and `show` (depends on T058, T059)
- [X] T061 [US2] Add the two account routes to the admin group in `routes/web.php` (depends on T060)
- [X] T062 [US2] Create `resources/views/app/admin/accounts/index.blade.php`: the search box, the results, and a stated empty result rather than a blank screen (depends on T015, T061)
- [X] T063 [US2] Create `resources/views/app/admin/accounts/show.blade.php`: the account facts and the person facts, counts only for vaults and contacts, and no link that leads into a vault (depends on T015, T061)

**Checkpoint**: a support question can be answered from the interface, and no path from it reaches
what a user wrote about anybody.

---

## Phase 5: User Story 3 - Act on an account when support requires it (Priority: P2)

**Goal**: from an account, an administrator can deactivate and reactivate the user, change the
storage limit and grant or withdraw lifetime access. Each warns first and is recorded.

**Independent Test**: deactivate a user, confirm they can no longer sign in and that their
existing session no longer works, then reactivate and confirm they can.

### Tests for User Story 3

- [X] T064 [P] [US3] Create `tests/Unit/Actions/DeactivateUserTest.php`: sets `is_active` false, dispatches the log job, and refuses when the target is the administrator themselves (Story 3 scenario 6)
- [X] T065 [P] [US3] Create `tests/Unit/Actions/ActivateUserTest.php`: sets `is_active` true, dispatches the log job, and leaves everything the user owned in place
- [X] T066 [P] [US3] Create `tests/Unit/Actions/UpdateAccountStorageLimitTest.php`: sets a limit, accepts null as no limit, accepts a limit below current usage without deleting anything, dispatches the log job, and is allowed on the administrator's own account (Story 3 scenario 7)
- [X] T067 [P] [US3] Create `tests/Unit/Actions/GrantLifetimeAccessTest.php`: sets the flag and the purchase date, dispatches the log job, and is allowed on the administrator's own account
- [X] T068 [P] [US3] Create `tests/Unit/Actions/WithdrawLifetimeAccessTest.php`: clears the flag and dispatches the log job
- [X] T069 [P] [US3] Create `tests/Feature/Controllers/App/Admin/UserActivationControllerTest.php`: `404` for a non administrator, deactivation makes the target's next request redirect to sign in (FR-023 end to end), reactivation lets them back, and `404` when the target is the administrator's own user
- [X] T070 [P] [US3] Create `tests/Feature/Controllers/App/Admin/AccountStorageLimitControllerTest.php`: `404` for a non administrator, a limit is set, null means no limit, a limit under current usage deletes no file and the next upload is refused by `CreateFile`, and validation rejects a negative number
- [X] T071 [P] [US3] Create `tests/Feature/Controllers/App/Admin/AccountLifetimeAccessControllerTest.php`: `404` for a non administrator, the grant takes effect immediately and is attributed, the withdrawal takes effect
- [X] T072 [P] [US3] Add a case to `tests/Feature/Controllers/App/Admin/AdministratorLogControllerTest.php`: each of the five actions above appears in the record with who, what, which account and when (Story 3 scenario 5)

### Implementation for User Story 3

- [X] T073 [P] [US3] Create `app/Actions/DeactivateUser.php`: refuses when the target is the acting administrator's own user, sets `is_active` false, dispatches `LogAdministratorAction` (depends on T010)
- [X] T074 [P] [US3] Create `app/Actions/ActivateUser.php` (depends on T010)
- [X] T075 [P] [US3] Create `app/Actions/UpdateAccountStorageLimit.php`: accepts a byte count or null, deletes nothing, dispatches the log job with the old and the new value (depends on T010)
- [X] T076 [P] [US3] Create `app/Actions/GrantLifetimeAccess.php`: sets `has_lifetime_access` and `purchased_at`, dispatches the log job (depends on T010)
- [X] T077 [P] [US3] Create `app/Actions/WithdrawLifetimeAccess.php` (depends on T010)
- [X] T078 [P] [US3] Create `app/Http/Controllers/App/Admin/UserActivationController.php` with `create` and `destroy` (depends on T073, T074)
- [X] T079 [P] [US3] Create `app/Http/Controllers/App/Admin/AccountStorageLimitController.php` with `update`, validating `storage_limit_in_bytes` as nullable integer min 0 (depends on T075)
- [X] T080 [P] [US3] Create `app/Http/Controllers/App/Admin/AccountLifetimeAccessController.php` with `create` and `destroy` (depends on T076, T077)
- [X] T081 [US3] Add the five account action routes to the admin group in `routes/web.php` (depends on T078, T079, T080)
- [X] T082 [US3] Add the five controls to `resources/views/app/admin/accounts/show.blade.php`, each behind `<x-modal.warning>` stating what the user will experience, and the storage form warning explicitly when the new limit is below current usage (FR-026, Story 3 scenario 3) (depends on T063, T081)

**Checkpoint**: support no longer needs an engineer for an abusive user or a purchase that did not
register, and every intervention is on the record.

---

## Phase 6: User Story 4 - Manage who administers the instance (Priority: P2)

**Goal**: an administrator sees who else holds the capacity, grants it by email address and
withdraws it, without server access and without any way to lock the instance out.

**Independent Test**: with two users, grant the capacity to the second, confirm they can reach the
area and that the first cannot withdraw their own, then withdraw the second and confirm the area
is closed to them again.

### Tests for User Story 4

- [X] T083 [P] [US4] Create `tests/Unit/Actions/WithdrawAdministratorAccessTest.php`: deletes the row, dispatches the log job, refuses when the target is the acting administrator (FR-008), refuses when it is the last administrator on the instance (FR-007), and survives two withdrawals arriving together so at least one administrator remains
- [X] T084 [P] [US4] Create `tests/Unit/ViewModels/Admin/AdministratorsViewModelTest.php`: lists every holder with who granted it and when, shows a holder who is not active as unable to administer, and pages
- [X] T085 [P] [US4] Create `tests/Feature/Controllers/App/Admin/AdministratorControllerTest.php`: `404` for a non administrator, the list, a grant by email, validation refusing an address belonging to nobody with a message saying so, a withdrawal closing the area to the other administrator, `404` on withdrawing your own, a stated refusal on withdrawing the last one, and a required confirmation on both the grant and the withdrawal

### Implementation for User Story 4

- [X] T086 [US4] Create `app/Actions/WithdrawAdministratorAccess.php`: inside a transaction, locks the `administrators` rows with `lockForUpdate()`, refuses on self and on the last administrator, deletes the row, dispatches `LogAdministratorAction` (depends on T010, research.md decision 6)
- [X] T087 [P] [US4] Create `app/ViewModels/Admin/AdministratorsViewModel.php`: every holder, who granted it, when, whether they can currently sign in, and `simplePaginate` like every other list in the area (FR-035)
- [X] T088 [US4] Create `app/Http/Controllers/App/Admin/AdministratorController.php` with `index`, `create` validating `email` as required and existing in `users`, and `destroy` (depends on T016, T086, T087)
- [X] T089 [US4] Add the three administrator routes to the admin group in `routes/web.php` (depends on T088)
- [X] T090 [US4] Create `resources/views/app/admin/administrators/index.blade.php`: the list, the grant form behind `<x-modal.warning>` naming who is about to gain the capacity, the withdrawal behind `<x-modal.warning>` naming who is about to lose it, and a deactivated holder marked as unable to administer (FR-009, Story 4 scenarios 2 and 4) (depends on T015, T089)

**Checkpoint**: the operator team changes without server access, and no sequence of grants or
withdrawals can leave the instance with nobody.

---

## Phase 7: User Story 5 - See the state of the instance at a glance (Priority: P3)

**Goal**: the first screen of the admin space says how the instance is doing.

**Independent Test**: seed a known number of accounts, users and pending submissions, open the
overview, and confirm every number matches.

### Tests for User Story 5

- [X] T091 [P] [US5] Create `tests/Unit/ViewModels/Admin/OverviewViewModelTest.php`: all eight figures against seeded data, zeros on an empty instance, and the seven day and thirty day windows counting the right rows
- [X] T092 [P] [US5] Create `tests/Feature/Controllers/App/Admin/OverviewControllerTest.php`: `404` for a non administrator, the figures rendered, an empty instance reading as empty rather than broken, and the waiting count linking to the moderation queue

### Implementation for User Story 5

- [X] T093 [P] [US5] Create `app/ViewModels/Admin/OverviewViewModel.php` with the eight aggregates in data-model.md, each paired with the period it covers, computed when the screen is opened
- [X] T094 [US5] Create `app/Http/Controllers/App/Admin/OverviewController.php` with `index` (depends on T093)
- [X] T095 [US5] Replace the root redirect from T014 with the `admin.overview.index` route in `routes/web.php`, and point the admin layout's first navigation item at it (depends on T094)
- [X] T096 [US5] Create `resources/views/app/admin/overview/index.blade.php`: every figure stating its period, zeros reading as an empty instance, and the waiting count linking to the queue in one step (depends on T015, T095)

**Checkpoint**: all five stories work independently.

---

## Phase 8: Polish and Cross-Cutting Concerns

- [X] T097 Run `php artisan monica:localize`, then translate every string it reports as missing in all nine locale files under `lang/`. Never edit the keys by hand (FR-034, SC-008)
- [X] T098 Run `php artisan monica:localize --check` and confirm it reports nothing missing in any locale
- [X] T099 [P] Run `./vendor/bin/pint` and commit the formatting
- [X] T100 [P] Run `./vendor/bin/phpstan analyse` and fix every new finding rather than ignoring it, adding the generic annotations the new relations and paginators need
- [X] T101 Run the full suite on SQLite, MySQL and PostgreSQL and confirm `lockForUpdate()` and the lowercased `LIKE` behave the same on all three
- [X] T102 [P] Re-read every file under `resources/views/app/admin/` and `app/ViewModels/Admin/` against FR-021 and FR-022: no contact, field value, note or file, no link that leads to one, and nothing that signs an administrator in as a user
- [X] T103 [P] Read the admin group in `routes/web.php` and confirm two things: no route takes a vault, a contact or a file as a parameter, and no route deletes an account or a user (FR-027)
- [X] T107 [P] Verify SC-009: seed one hundred thousand accounts with users, vaults and contacts using a throwaway seeder with chunked inserts, then measure the first page of `admin.accounts.index` (empty term and a term that scans), `admin.logs.index` and `admin.moderation.index`. Record the numbers in the pull request. If the account search misses the threshold, the fallback is research.md decision 8: a prefix-only email match, or a driver-guarded index, either of which needs a spec decision first
- [X] T104 Walk `specs/001-admin-space/quickstart.md` end to end against a running instance, including the two concurrency cases in scenarios 2 and 5
- [X] T105 Open the pull request following `.claude/skills/pull-requests`, with a Conventional Commits title reading as one short sentence, and no mention of the tooling that helped write it

---

## Dependencies and Execution Order

### Phase dependencies

- **Setup (Phase 1)**: no dependencies
- **Foundational (Phase 2)**: depends on Phase 1, blocks every story
- **US1 (Phase 3)**: depends on Phase 2 only
- **US2 (Phase 4)**: depends on Phase 2 only
- **US3 (Phase 5)**: depends on Phase 2, and on T063 from US2 for the screen the controls sit on
- **US4 (Phase 6)**: depends on Phase 2 only (the grant action is already there, built for the console)
- **US5 (Phase 7)**: depends on Phase 2, and reads better once US1 exists since the waiting count links into its queue
- **Polish (Phase 8)**: depends on every story that is being shipped

### Within each story

- Actions before the controllers that delegate to them
- View models before the controllers that build them
- Controllers before the routes that name them, and routes before the views that link to them
- Tests may come first or alongside, and must pass before the story is called done

### The one cross-story dependency

US3 adds its controls to the account screen US2 creates. To ship US3 without US2, the controls
would need a screen of their own, which is more work than doing US2 first. Ship them in order.
Everything else is independent.

### Parallel opportunities

- T002 to T008 and T011, T012, T015 all touch different new files and can run together
- Every test task marked [P] within a phase can run together
- T041 to T043, T046, T047 in US1 are independent of each other
- T073 to T077 in US3 are five independent actions
- With people to spare, US1, US2 and US4 can run at the same time once Phase 2 is done

---

## Parallel Example: Phase 2

```bash
# The two migrations, the two models, the two factories and the enum, all new files:
Task: "Create migration create_administrators_table"
Task: "Create migration create_administrator_logs_table"
Task: "Create app/Models/Administrator.php"
Task: "Create app/Models/AdministratorLog.php"
Task: "Create database/factories/AdministratorFactory.php"
Task: "Create database/factories/AdministratorLogFactory.php"
Task: "Create app/Enums/AdministratorActionEnum.php"
```

## Parallel Example: User Story 3

```bash
# Five independent actions:
Task: "Create app/Actions/DeactivateUser.php"
Task: "Create app/Actions/ActivateUser.php"
Task: "Create app/Actions/UpdateAccountStorageLimit.php"
Task: "Create app/Actions/GrantLifetimeAccess.php"
Task: "Create app/Actions/WithdrawLifetimeAccess.php"
```

---

## Implementation Strategy

### MVP: Phase 1, Phase 2 and User Story 1

Phase 2 is large because the admin space needs a door, a layout and a record before any screen in
it means anything. Once it and US1 are done, the marketplace can be moderated by a human, which
is the whole reason this feature is P1. Stop there and validate.

### Incremental delivery

1. Phases 1 and 2, so an administrator exists and can reach an empty area
2. US1, the moderation queue. Ship it
3. US2, the account lookup. Ship it
4. US3, the account actions, on the screen US2 built. Ship it
5. US4, managing administrators. Ship it
6. US5, the overview. Ship it
7. Phase 8 before each pull request, not only at the end

### Notes

- Every action dispatches `LogAdministratorAction`. FR-028 admits no exception, so an action
  without a log entry is an unfinished action
- Warn before anything a user would experience as a loss, and before a change to who administers
  the instance. Five controls in US3, two in US4
- Nothing in the admin space reads what a user wrote in a vault. T057, T102 and T103 exist to
  keep that true as the area grows
- Commit per task or per logical group, with a Conventional Commits subject reading as one short
  sentence
