---
description: "Task list for feature 026, more ways for a notification to reach somebody"
---

# Tasks: More ways for a notification to reach somebody

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md),
[data-model.md](data-model.md), [contracts/http.md](contracts/http.md), [quickstart.md](quickstart.md)

**Tests**: not optional here. Principle III is non-negotiable and CLAUDE.md repeats it, so every task
that adds behaviour is paired with its test. The project does not practise test-first, so a test is
written with its subject rather than before it.

**Format**: `[ID] [P?] [Story?] Description with file path`. `[P]` means a different file with no
incomplete dependency.

## Phase 1: Setup

- [X] T001 Amend `spec/spec.md` §14.2 to drop "a channel we will never provide" and §10.4 to stop listing three channels, in its own pull request per Governance
- [X] T002 Add `laravel/slack-notification-channel ^3.10`, `laravel-notification-channels/telegram ^8.0` and `laravel/vonage-notification-channel ^3.3` to `composer.json`
- [X] T003 [P] Add `notification_retention_months`, `notification_channel_failure_threshold`, `notification_proof_code_minutes`, `notification_proof_maximum_attempts` and `notification_proof_maximum_sends` to `config/monica.php`

---

## Phase 2: Foundational (blocks every story)

- [X] T004 Add `Slack`, `Telegram` and `Sms` cases and the per-channel facts (`label`, `deliveredSentence`, `needsDestination`, `needsCredential`, `provesSilently`, `maximumLength`, `laravelChannel`) to `app/Enums/NotificationChannelEnum.php`, removing `preferenceColumn`
- [X] T005 Cover every enum method in `tests/Unit/Enums/NotificationChannelEnumTest.php`
- [X] T006 Create `database/migrations/2026_09_02_000005_create_notification_channels_table.php` per data-model.md, foreign key declared at the bottom and no `constrained()`
- [X] T007 Drop `in_app_is_enabled`, `email_is_enabled` and `push_is_enabled` from `database/migrations/2026_09_02_000002_create_notification_preferences_table.php`
- [X] T008 Seed `notification_channels` instead of the dropped booleans in `database/migrations/2026_09_02_000004_backfill_notification_preferences.php`
- [X] T009 [P] Create `app/Models/NotificationChannel.php` with the `encrypted:array` credential cast and the account relation
- [X] T010 [P] Create `database/factories/NotificationChannelFactory.php` with `enabled`, `unproven` and `disabled` states
- [X] T011 Cover the model and its states in `tests/Unit/Models/NotificationChannelTest.php`
- [X] T012 Remove `allows`, `enabledChannels` and the three boolean casts from `app/Models/NotificationPreference.php`
- [X] T013 Update `tests/Unit/Models/NotificationPreferenceTest.php` and `database/factories/NotificationPreferenceFactory.php` for T012
- [X] T014 Create the six channel rows for a new account in `app/Actions/CreateNotificationPreference.php`
- [X] T015 Update `tests/Unit/Actions/CreateNotificationPreferenceTest.php` and `tests/Unit/Actions/CreateAccountTest.php` for T014
- [X] T016 Update `tests/Unit/Migrations/BackfillNotificationPreferencesTest.php` for T008

**Checkpoint**: the channel is a row and the enum knows every fact about it.

---

## Phase 3: User Story 1, a channel carries a message whatever it is (P1) 🎯 MVP

**Goal**: delivery, retry and the daily summary stop naming email and work for any channel.

**Independent Test**: two channels on, one deadline due, one notification each, delivered by its own
channel, the failing one retried then abandoned without touching the other.

- [X] T017 [US1] Replace the email branch in `app/Actions/DeliverNotification.php` with a hand-off keyed on the channel, and classify a refusal temporary or permanent per FR-029
- [X] T018 [US1] Create `app/Actions/RecordChannelFailure.php` to count consecutive failures, reset on success and disable past the threshold per FR-013
- [X] T019 [P] [US1] Read the account's enabled channel rows instead of the preference booleans in `app/Actions/NotifyReminderOccurrence.php`, skipping any channel not fully set up per FR-021
- [X] T020 [US1] Produce one summary per channel in `app/Actions/SendNotificationDigest.php`, replacing the hard-coded `NotificationChannelEnum::Email`
- [X] T021 [P] [US1] Take the channel in `app/Notifications/ReminderDue.php` and return it from `via()`
- [X] T022 [P] [US1] Take the channel in `app/Notifications/NotificationDigest.php` and return it from `via()`
- [X] T023 [US1] Cover per-channel hand-off, retry, abandonment and channel isolation in `tests/Unit/Actions/DeliverNotificationTest.php`
- [X] T024 [P] [US1] Cover the failure counter and the disabling threshold in `tests/Unit/Actions/RecordChannelFailureTest.php`
- [X] T025 [P] [US1] Cover one notification per enabled channel in `tests/Unit/Actions/NotifyReminderOccurrenceTest.php`
- [X] T026 [P] [US1] Cover one summary per channel in `tests/Unit/Actions/SendNotificationDigestTest.php`

**Checkpoint**: US1 is shippable on the channels that already exist.

---

## Phase 4: User Story 2, the account says where each channel reaches it (P2)

**Goal**: a destination and a credential per channel, proven where a wrong one fails silently.

**Independent Test**: switching on a channel with no destination is refused; a phone number stays off
until its code is typed back; a Slack room may be switched on at once.

- [X] T027 [US2] Create `app/Actions/UpdateNotificationChannel.php` to set the destination, credential and enabled state, refusing an incomplete or unproven channel and throwing `ModelNotFoundException` across accounts
- [X] T028 [P] [US2] Create `app/Actions/SendChannelProofCode.php` to hash a code, set its expiry and enforce the resend limit
- [X] T029 [P] [US2] Create `app/Actions/ConfirmChannelProofCode.php` to check the code, count wrong attempts and stamp `proven_at`
- [X] T030 [P] [US2] Create `app/Notifications/ChannelProofCode.php`
- [X] T031 [P] [US2] Create `app/Notifications/ChannelDisabled.php`, sent on a channel that still works
- [X] T032 [US2] Clear `proven_at` when the destination changes but not when only the credential changes, in `app/Actions/UpdateNotificationChannel.php`
- [X] T033 [US2] Cover validation, the cross-account refusal and the proof gate in `tests/Unit/Actions/UpdateNotificationChannelTest.php`
- [X] T034 [P] [US2] Cover expiry, wrong attempts and resend limits in `tests/Unit/Actions/SendChannelProofCodeTest.php` and `tests/Unit/Actions/ConfirmChannelProofCodeTest.php`
- [X] T035 [P] [US2] Cover both notifications in `tests/Unit/Notifications/ChannelProofCodeTest.php` and `tests/Unit/Notifications/ChannelDisabledTest.php`

**Checkpoint**: a channel can be set up and proven, with nothing to look at yet.

---

## Phase 5: User Story 3, the notification bar (P3)

**Goal**: the in-app channel becomes visible, and notifications age out under a stated policy.

**Independent Test**: a due deadline raises the count on every signed-in screen, opening one marks it
read and lands on the record, clearing all asks first.

- [X] T036 [P] [US3] Create `app/Actions/MarkNotificationsAsRead.php` handling one notification and all of them
- [X] T037 [US3] Add `Prunable` to `app/Models/Notification.php` keyed on `notification_retention_months`, and correct the "Nothing here is deleted" docblock
- [X] T038 [P] [US3] Create `app/ViewModels/NotificationBarViewModel.php` returning the unread count and a bounded page
- [X] T039 [US3] Create `resources/views/components/notification-bar.blade.php`, keyboard operable and announced to a screen reader per FR-037
- [X] T040 [US3] Render the bar from `resources/views/components/bar-links.blade.php`, which both signed-in layouts already include
- [X] T041 [P] [US3] Create `app/Http/Controllers/App/NotificationController.php` and `NotificationReadController.php`
- [X] T042 [P] [US3] Create `app/Http/Controllers/Api/NotificationController.php` and `NotificationReadController.php` per contracts/http.md
- [X] T043 [US3] Add the web and API notification routes to `routes/web.php` and `routes/api.php`
- [X] T044 [US3] Cover the bar, its empty state and its accessibility in `tests/Feature/Views/Components/NotificationBarTest.php`
- [X] T045 [P] [US3] Cover read, read-all and the cross-account 404 in `tests/Feature/Controllers/App/NotificationControllerTest.php`
- [X] T046 [P] [US3] Cover the API surface in `tests/Feature/Controllers/Api/NotificationControllerTest.php`
- [X] T047 [P] [US3] Cover pruning and the untouched deadline in `tests/Unit/Models/NotificationTest.php`

**Checkpoint**: the ordinary account gets visible value with no outside service configured.

---

## Phase 6: User Story 4, switching a channel on and proving it works (P4)

**Goal**: the preferences screen that reaches everything Phase 4 built.

**Independent Test**: all six channels listed with their setup steps, a test send reports its outcome,
nothing scheduled is touched.

- [X] T048 [P] [US4] Create `app/Actions/SendChannelTestMessage.php`, touching no deadline
- [X] T049 [P] [US4] Create `app/ViewModels/Settings/Account/Notifications/NotificationChannelsViewModel.php`
- [X] T050 [US4] Create `resources/views/app/settings/account/notifications/index.blade.php` with per-channel setup steps per FR-039
- [X] T051 [US4] Warn before saving a destination outside sole control, and confirm before removing a credential or switching off the last working channel, in `resources/views/app/settings/account/notifications/index.blade.php` per FR-014 and FR-018
- [X] T052 [P] [US4] Create the controllers under `app/Http/Controllers/App/Settings/Account/Notifications/`: `NotificationChannelController`, `ChannelProofController`, `ChannelTestController`
- [X] T053 [P] [US4] Create `app/Http/Controllers/Api/NotificationChannelController.php` and `NotificationPreferenceController.php` per contracts/http.md, never returning a credential
- [X] T054 [US4] Add the settings and API routes to `routes/web.php` and `routes/api.php`
- [X] T055 [P] [US4] Add the Notifications entry to `resources/views/components/settings/sidebar.blade.php`
- [X] T056 [US4] Cover the screen and its refusals in `tests/Feature/Controllers/App/Settings/Account/Notifications/NotificationChannelControllerTest.php`
- [X] T057 [P] [US4] Cover proof and test sends in `tests/Feature/Controllers/App/Settings/Account/Notifications/ChannelProofControllerTest.php` and `ChannelTestControllerTest.php`
- [X] T058 [P] [US4] Cover the API surface, including that no credential is ever returned, in `tests/Feature/Controllers/Api/NotificationChannelControllerTest.php`
- [X] T059 [P] [US4] Cover the view model in `tests/Unit/ViewModels/Settings/Account/Notifications/NotificationChannelsViewModelTest.php`

**Checkpoint**: every channel is reachable by a person. Stories 1 to 4 are a coherent release.

---

## Phase 7: User Story 5, Slack, Telegram and text message (P5)

**Goal**: the three outbound destinations, on per-account credentials.

**Independent Test**: with your own credentials saved, a due deadline arrives at each destination in
the account's language.

- [X] T060 [P] [US5] Add `toSlack`, `toTelegram` and `toVonage` to `app/Notifications/ReminderDue.php`
- [X] T061 [P] [US5] Add the same three to `app/Notifications/NotificationDigest.php`
- [X] T062 [US5] Supply the account's own credential per send rather than reading `config/services.php`, per FR-038
- [X] T063 [P] [US5] Create `app/Helpers/MessageShortener.php` cutting at a sentence boundary to a channel's `maximumLength()` per FR-028
- [X] T064 [US5] Map each service's refusals to temporary or permanent in `app/Actions/DeliverNotification.php`
- [X] T065 [P] [US5] Cover the three message builders in `tests/Unit/Notifications/ReminderDueTest.php` and `tests/Unit/Notifications/NotificationDigestTest.php`
- [X] T066 [P] [US5] Cover shortening, including the mid-word case, in `tests/Unit/Helpers/MessageShortenerTest.php`
- [X] T067 [US5] Cover per-account credentials and per-service failure classification in `tests/Unit/Actions/DeliverNotificationTest.php`

**Checkpoint**: all six channels deliver.

---

## Phase 8: User Story 6, the record says which channel carried it (P6)

**Goal**: no screen holds its own list of channels.

**Independent Test**: a notification on a new channel reads as a translated sentence in the reminder
history.

- [X] T068 [P] [US6] Replace the channel `match` with `$channel->deliveredSentence()` in `app/ViewModels/Vaults/ReminderHistory.php`
- [X] T069 [P] [US6] Replace the channel `match` with `$channel->label()` in `app/ViewModels/Vaults/ReminderDelivery.php`
- [X] T070 [P] [US6] Replace the channel `match` with `$channel->label()` in `app/ViewModels/Vaults/ReminderRow.php`
- [X] T071 [US6] Say that per-channel records are not kept beyond the retention period, per FR-051, in `app/ViewModels/Vaults/ReminderHistory.php`
- [X] T072 [US6] Cover all six channels and the pruned-history sentence in `tests/Unit/ViewModels/Vaults/ReminderHistoryTest.php`, `ReminderDeliveryTest.php` and `ReminderRowTest.php`

---

## Phase 9: Polish and cross-cutting

- [X] T073 Run `php artisan monica:localize` and translate every new string in all 31 locales
- [X] T074 Run `php artisan monica:localize --check` and fix every counted-phrase and typography breach
- [X] T075 [P] Audit `resources/views/components/notification-bar.blade.php` and `resources/views/app/settings/account/notifications/index.blade.php` against WCAG 2.2 AA per Principle IX
- [X] T076 [P] Run `vendor/bin/pint --format agent` and `vendor/bin/phpstan analyse --no-progress`
- [ ] T077 Run every scenario in [quickstart.md](quickstart.md) against SQLite, MySQL and PostgreSQL
- [ ] T078 State the Principle XI cap breach and the three new dependencies in the pull request body

---

## Dependencies

- **Phase 1** blocks everything. T001 is a separate pull request that must merge first.
- **Phase 2** blocks every story. T004 blocks T005 and all of Phase 3. T006 blocks T009 to T011. T007 blocks T012 and T013. T008 blocks T016.
- **US1 (Phase 3)** depends on Phase 2 only. T017 blocks T023. T018 blocks T024.
- **US2 (Phase 4)** depends on Phase 2 only, and is independently testable without US1.
- **US3 (Phase 5)** depends on Phase 2 only. T036 to T040 block T044 to T046.
- **US4 (Phase 6)** depends on US2, which supplies the actions its screen calls.
- **US5 (Phase 7)** depends on US1 for the hand-off and US2 for the credentials.
- **US6 (Phase 8)** depends on T004 only, so it can run any time after Phase 2.
- **Phase 9** depends on every story that is being shipped.

## Scope

MVP is Phase 1, Phase 2 and Phase 3. Stories 1 to 4 are the coherent release. Phase 7 is the part to
stage or cut: it carries the three dependencies, the outside services and the §14.2 amendment.
