# Tasks: List every session of an account and remove any of them

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [quickstart.md](quickstart.md)

**Tests**: every new class gets its test, per `.claude/rules/testing.md`; the table in quickstart.md maps each scenario to its test. Tests insert their own rows with `DB::table('sessions')->insert()` and set `session.driver` to `database` where a view model or the screen is read (research R7).

## Phase 1: Setup

- [X] T001 Create branch `2026-10-27-session-list-removal` from an up to date `main` and carry the five files of specs/085-session-list-removal/ onto it (if `.git/config.lock` blocks `checkout -b`, build it with plumbing)

## Phase 2: Foundational

- [X] T002 [P] Create app/Helpers/DeviceDescription.php (research R5) with `public static function fromUserAgent(?string $userAgent): string`: the browser from the first of `Edg/`, `OPR/` or `Opera`, `Chrome/` or `CriOS/`, `Firefox/` or `FxiOS/`, `Safari/` that matches, in that order; the system from the first of `iPhone`, `iPad`, `Android`, `Windows`, `Macintosh` or `Mac OS X`, `Linux`; rendered as `__(':browser on :system')`, the known half alone, or `__('Unknown device')`
- [X] T003 [P] Create tests/Unit/Helpers/DeviceDescriptionTest.php with a `#[DataProvider]`: Chrome on macOS, Safari on iPhone, Firefox on Windows, Edge on Windows, Chrome on Android, Safari on iPad; a browser with no system and a system with no browser; "Unknown device" for null, the empty string and `curl/8.4.0`
- [X] T004 Add `case SessionRemoved = 'session_removed'` after `AssistantDisconnection` in app/Enums/UserActionEnum.php and `self::SessionRemoved => 'Removed the session on :device'` to `description()` (research R8), then run tests/Unit/Enums/UserActionEnumTest.php

Checkpoint: a user agent reads as a device and the journal has a word for a removal; nothing is shown yet.

## Phase 3: User Story 1, see where the account is signed in (P1) MVP

Independent test: quickstart rows US1.1 to US1.4 and FR-009, plus the first by-hand step.

- [X] T005 [US1] Create app/ViewModels/Settings/Account/Security/SessionListViewModel.php (research R1, R2): promoted `User $user` and `string $currentSessionId`; `canList()` is `config('session.driver') === 'database'`; `sessions()` reads `DB::table(config('session.table'))` where `user_id` is the user's and `last_activity` is at or after `now()->subMinutes((int) config('session.lifetime'))->getTimestamp()`, newest first with the row whose `id` is `$currentSessionId` moved to the front, as `list<array{id: string, device: string, address: ?string, lastUsedAt: string, isCurrent: bool}>` with `device` from `DeviceDescription::fromUserAgent()` and `lastUsedAt` from `Carbon::createFromTimestamp()->diffForHumans()`; `header()` is `trans_choice('Signed in on :count device|Signed in on :count devices', $count)`, with no zero branch since the device in hand is always a row on the database driver
- [X] T006 [US1] Create tests/Unit/ViewModels/Settings/Account/Security/SessionListViewModelTest.php: two rows list the one in hand first and marked, with device, address and last use filled; a row with no user agent reads "Unknown device"; a row older than `session.lifetime` is left out; another user's row is left out; `canList()` is false on the `file` driver; the header counts the rows
- [X] T007 [US1] In app/ViewModels/Settings/Account/Security/SecurityViewModel.php add a promoted `private readonly string $currentSessionId = ''` (the default keeps the nine `new SecurityViewModel(user:)` calls of its test) and `sessions(): SessionListViewModel` built as `passkeys()` is; in app/Http/Controllers/App/Settings/Account/Security/SecurityController.php pass `currentSessionId: $request->session()->getId()` (research R6)
- [X] T008 [P] [US1] Create resources/views/app/settings/account/security/_sessions-unavailable.blade.php shaped like `_passkeys-unavailable.blade.php`: one `x-notice tone="quiet"` saying this instance keeps sessions where they cannot be listed, and that signing out still ends this device's session (FR-009)
- [X] T009 [P] [US1] Create resources/views/app/settings/account/security/_session-row.blade.php: an `x-row` with `icon="device"`, the device as title, `__(':address · last used :used')` as description or `__('Last used :used')` when the address is null, and in the `end` slot an `x-badge tone="neutral"` reading `__('This device')` when `isCurrent` (FR-002); no removal yet
- [X] T010 [US1] Create resources/views/app/settings/account/security/_sessions.blade.php: `@php($sessions = $viewModel->sessions())`, the unavailable partial when `! $sessions->canList()`, else a header line showing `$sessions->header()` drawn as `_api-keys.blade.php` draws its own and one `_session-row` include per session; then in resources/views/app/settings/account/security/index.blade.php add, after the Two-factor section, an `x-page-section` titled `__('Where you are signed in')` with a one-line description and an `x-panel :flush="true"` holding the include (research R6)
- [X] T011 [US1] Add to tests/Feature/Controllers/App/Settings/Account/Security/SecurityControllerTest.php: on the `database` driver with two rows the screen shows both devices and "This device" once; on the `file` driver it shows the notice and no device (FR-009); run `php artisan view:clear` before it

Checkpoint: the screen lists every live session of the account and marks the one in hand.

## Phase 4: User Story 2, remove a session (P1)

Independent test: quickstart rows US2.1 to US2.6 and the twice edge, plus by-hand steps 2 to 4. Depends on T002, T004 and the files of Phase 3.

- [X] T012 [P] [US2] Create app/Exceptions/CannotDeleteTheCurrentSession.php as a `RuntimeException` with an empty body, like app/Exceptions/CannotDeleteTheLastPage.php (research R4)
- [X] T013 [US2] Create app/Actions/DestroySession.php (research R3, R4): promoted `User $user`, `string $sessionId`, `string $currentSessionId`; `execute()` throws `CannotDeleteTheCurrentSession` when the two ids match, reads the row `where id and user_id` from `DB::table(config('session.table'))` and throws `ModelNotFoundException` when there is none, deletes it, calls `$this->user->setRememberToken(Str::random(60))` and `save()` as app/Actions/UpdateUserPassword.php does, then dispatches `LogUserAction` on the `low` queue with `UserActionEnum::SessionRemoved` and `['device' => DeviceDescription::fromUserAgent($row->user_agent)]`, as app/Actions/DestroyApiKey.php does
- [X] T014 [US2] Create tests/Unit/Actions/DestroySessionTest.php with `Queue::fake()`: the row is gone, `remember_token` changed and one `LogUserAction` pushed on `low` carrying `device` only; another user's row throws `ModelNotFoundException` with the row and the token untouched; the current id throws `CannotDeleteTheCurrentSession` with the row kept; a second removal of the same id throws `ModelNotFoundException`
- [X] T015 [US2] Add `Route::delete('settings/account/security/sessions/{session}', [SessionController::class, 'destroy'])->whereAlphaNumeric('session')->name('settings.sessions.destroy')` and its import to the `password.confirm:auth.confirmPassword.new` group holding the API keys in routes/web.php (research R6, FR-007)
- [X] T016 [US2] Create app/Http/Controllers/App/Settings/Account/Security/SessionController.php with `destroy(Request $request, string $session): RedirectResponse`: runs `DestroySession` with `currentSessionId: $request->session()->getId()`, catches `CannotDeleteTheCurrentSession` into `redirect()->route('settings.security.index')->with('error', __('This is the device you are on. Sign out to end it.'))` as app/Http/Controllers/App/Settings/Account/RelationshipTypes/RelationshipCategoryController.php does, and otherwise redirects there with `status` `__('The device is signed out.')` and a `status_description` saying it is asked to sign in at its next request
- [X] T017 [US2] In resources/views/app/settings/account/security/_session-row.blade.php add, for every row that is not the device in hand, an `x-delete-action size="small" confirms-password` whose `action` is `route('settings.sessions.destroy', $session['id'])`, whose `label` and `confirm` read `__('Remove')`, whose `title` names the device, and whose body says the device is asked to sign in at its next request and that every device of the account, this one included, will ask to sign in again once its session runs out (FR-004)
- [X] T018 [US2] Create tests/Feature/Controllers/App/Settings/Account/Security/SessionControllerTest.php: with `withConfirmedPassword()` the `DELETE` redirects to `settings.security.index` with the `status` flash and the row is gone; without it, redirects to `auth.confirmPassword.new` with the row kept; the id of the session in hand (`session()->getId()` once `withConfirmedPassword()` started it) redirects with the `error` flash and the row kept; another user's row answers 404
- [X] T019 [US2] Add to tests/Feature/Controllers/App/Settings/Account/Security/SecurityControllerTest.php: the other device's row carries a form pointing at `route('settings.sessions.destroy', ...)` and a warning naming the device, and the device in hand carries no such form (FR-002, FR-004)
- [X] T020 [US2] Create tests/Feature/Auth/RemovedDeviceSignOutTest.php (research R7): a user holding a `remember_token`, reached with a recaller cookie built as `PasswordlessRecallTest::recallerFor()` builds one, gets `vaults.index` before `DestroySession` runs on one of their rows and is redirected to `auth.login.new` after; a live session opened as `OtherDeviceSignOutTest::sessionOpenedWith()` opens one still answers 200 after the removal (FR-005, FR-006, SC-004)

Checkpoint: a session is removed behind the password confirmation and the warning, the removed device is sent to sign in, and the device in hand carries on.

## Phase 5: Polish

- [X] T021 [P] In docs/portal/en/10-your-account-and-security/13-when-something-looks-wrong.md add to "Secure your account" a step removing any device not recognised under **Where you are signed in**, and set `last_updated` to 2026-10-27 (`writer` skill)
- [X] T022 Run `php artisan monica:localize`, translate every phrase it reports in every configured locale following .claude/rules/translations.md and the `translations` skill, then `php artisan monica:localize --check` (FR-010)
- [X] T023 Run `php artisan view:clear`, `php artisan test --compact`, `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent`
- [X] T024 Walk through the by-hand steps of specs/085-session-list-removal/quickstart.md on two browsers with `SESSION_DRIVER=database`, then step 5 with `file`
- [X] T025 Record the feature in docs/FEATURES.md with the `feature-documentator` skill

## Dependencies

- Phase 1, then Phase 2, block everything. T002 and T003 run together; T004 beside them.
- US1 (Phase 3) is the MVP: T005, then T006 and T007 together, then T008 and T009 together, then T010, then T011.
- US2 (Phase 4) needs T002, T004 and Phase 3: T012 beside T013; T014 after T013; T015, then T016; T017 after T009 and T015; T018 after T016; T019 after T017; T020 after T013.
- Phase 5 comes last. T021 can run from Phase 1 on. Tasks touching one file run in order.

## Parallel examples

- Phase 2: T002 and T003 together.
- Phase 3: T008 and T009 together, after T005.
- Phase 4: T012 beside T013; T014 and T015 beside each other; T020 beside T018.

## Implementation strategy

Ship Phases 1 to 3 first: the list, the device in hand marked, the notice on another driver. Add the removal (Phase 4), which is the request. Phase 5 closes the increment with the translations, the gates and the record.

## Phase 6: Convergence

- [X] T026 CRITICAL: Add API coverage for listing the account's sessions, removing one and signing out every other, reusing `DestroySession` and `DestroyOtherSessions` behind Sanctum with tests, or have the maintainer record an explicit exception to Principle X in the spec and the pull request per Constitution X (contradicts)
- [X] T027 Record the decision of 2026-10-29 to offer signing out every other device at once: replace the clarification that forbids it in specs/085-session-list-removal/spec.md, add its requirements (typed password, the device in hand keeps its remembered sign in, unlike FR-006), and list its files in plan.md per spec clarification "end every other session at once" (contradicts)
- [X] T028 Name the devices that were signed out in the journal entry written by app/Actions/DestroyOtherSessions.php, with its test, per FR-008 (partial)
- [X] T029 Let an account with no password sign out every other device after a passkey confirmation, or state the password-only restriction in the spec, per FR-007 (partial)
