# Tasks: Reminders that actually fire

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [data-model.md](data-model.md), [quickstart.md](quickstart.md)

**Tests**: not optional here. Principle III is non-negotiable, so a task that adds a class adds its test in the same task, and both files are named.

**Format**: `[ID] [P?] [Story?] Description with file paths`. `[P]` means a different file and no unfinished dependency.

**Phase order deviates from priority once.** User story 6 is built fourth rather than last, because delivery reads the preferences and cannot be built or tested before they exist. Its P6 reflects how visible it is, not when it can be written.

## Phase 1: Setup

- [X] T001 [P] Add `reminder_generation_window_in_days` (90) and `notification_maximum_attempts` (3) to `config/monica.php`, both through `env()` like their neighbours
- [X] T002 [P] Create `app/Enums/ReminderOccurrenceStatusEnum.php` (pending, notified, postponed, dismissed, cancelled) with `isActionable()` and `isTerminal()`, and `tests/Unit/Enums/ReminderOccurrenceStatusEnumTest.php`
- [X] T003 [P] Create `app/Enums/ReminderOccurrenceCancelledReasonEnum.php` (the seven reasons) with `isReversible()` returning true only for rule_disabled, record_archived, record_deleted and field_removed, and `tests/Unit/Enums/ReminderOccurrenceCancelledReasonEnumTest.php`
- [X] T004 [P] Create `app/Enums/NotificationChannelEnum.php` (in_app, email, push) and `tests/Unit/Enums/NotificationChannelEnumTest.php`
- [X] T005 [P] Create `app/Enums/NotificationTypeEnum.php` (reminder_due, digest) and `tests/Unit/Enums/NotificationTypeEnumTest.php`
- [X] T006 [P] Create `app/Enums/NotificationDeliveryModeEnum.php` (immediate, digest) and `tests/Unit/Enums/NotificationDeliveryModeEnumTest.php`
- [X] T007 [P] Add the `ReminderDue` case to `app/Enums/EmailType.php`
- [X] T008 [P] Add `ReminderOccurrencePostponement` and `ReminderOccurrenceDismissal` cases and their phrases to `app/Enums/UserActionEnum.php`, and cover them in `tests/Unit/Enums/UserActionEnumTest.php`

## Phase 2: Foundational (blocks every story)

- [X] T009 Add `schedule_revision` (unsigned integer, default 1) to the existing `database/migrations/2026_09_02_000000_create_reminders_table.php`, editing it rather than adding a migration, since it is not in production
- [X] T010 [P] Create `database/migrations/2026_09_02_000001_create_reminder_occurrences_table.php` with the columns of model 041, the correction in `data-model.md`, and the three indexes it lists
- [X] T011 [P] Create `database/migrations/2026_09_02_000002_create_notification_preferences_table.php` with the columns of model 043 and its unique index on `account_id`
- [X] T012 [P] Create `database/migrations/2026_09_02_000003_create_notifications_table.php` with the columns of model 042, the `attempts` column, the self referencing `digest_id`, and the two indexes in `data-model.md`
- [X] T013 Create `database/migrations/2026_09_02_000004_backfill_notification_preferences.php` giving every existing account a row with the defaults of FR-028
- [X] T014 [P] Create `app/Models/ReminderOccurrence.php` with `HasUuids`, its casts, its relations and its `due` and `stillAhead` scopes, plus `database/factories/ReminderOccurrenceFactory.php` and `tests/Unit/Models/ReminderOccurrenceTest.php`
- [X] T015 [P] Create `app/Models/Notification.php` with `HasUuids`, its morph and self relations, plus `database/factories/NotificationFactory.php` and `tests/Unit/Models/NotificationTest.php`
- [X] T016 [P] Create `app/Models/NotificationPreference.php`, `database/factories/NotificationPreferenceFactory.php` and `tests/Unit/Models/NotificationPreferenceTest.php`
- [X] T017 Add `occurrences()` and the `schedule_revision` fillable and cast to `app/Models/Reminder.php`, and assert the relation in `tests/Unit/Models/ReminderTest.php`
- [X] T018 Add `notificationPreference()` to `app/Models/Account.php` and assert it in `tests/Unit/Models/AccountTest.php`

## Phase 3: User Story 1, deadlines exist (P1, MVP)

Goal: a saved rule produces the right rows at the right instants, and produces them once. Independently testable with no notification anywhere.

- [X] T019 [US1] Add window enumeration to `app/Helpers/ReminderSchedule.php`: every deadline whose instant falls in a window, scanning cycles from the window start minus the furthest "after" moment to the window end plus the furthest "before" moment, per `research.md`
- [X] T020 [US1] Extend `tests/Unit/Helpers/ReminderScheduleTest.php` for the window: moments reaching into cycles outside it in both directions, 29 February, a monthly rule on the 31st, an unknown year, a local time that does not exist, and one that happens twice
- [X] T021 [US1] Create `app/Actions/GenerateReminderOccurrences.php` taking one rule, building each `generation_key` as `{schedule_revision}:{source_local_date}:{direction}:{amount}:{unit}`, writing with `insertOrIgnore`, skipping instants already past, instants before the rule's schedule anchor, and inactive, removed or value-cleared rules
- [X] T022 [US1] Create `tests/Unit/Actions/GenerateReminderOccurrencesTest.php` covering both moments of a cycle, the exact UTC instants, running twice changing nothing, a second write with an existing key being ignored, a once cadence producing its deadlines once, and a rule in another account not being found
- [X] T023 [US1] Create `app/Jobs/GenerateDueReminderOccurrences.php` chunking active rules and delegating to the action, logging what it did, and `tests/Unit/Jobs/GenerateDueReminderOccurrencesTest.php`
- [X] T024 [US1] Schedule generation daily in `routes/console.php` on the `low` queue, beside the existing entries

## Phase 4: User Story 6, the account knows how it wants to be told (P6, built early)

Goal: every account has preferences, so delivery never reads a missing row.

- [X] T025 [P] [US6] Create `app/Actions/CreateNotificationPreference.php` with the FR-028 defaults in the account user's zone, and `tests/Unit/Actions/CreateNotificationPreferenceTest.php`
- [X] T026 [P] [US6] Create `app/Actions/UpdateNotificationPreference.php` validating the quiet hour bounds, the digest hour and the zone, and `tests/Unit/Actions/UpdateNotificationPreferenceTest.php`
- [X] T027 [US6] Call the create action from `app/Actions/CreateAccount.php` inside the existing transaction, and assert the row in `tests/Unit/Actions/CreateAccountTest.php`
- [X] T028 [US6] Create `tests/Unit/Migrations/BackfillNotificationPreferencesTest.php` instantiating the migration and calling `up()` over an account whose row was removed, proving it ends with exactly one row and that a second run adds none

## Phase 5: User Story 2, the deadline reaches the person (P2)

Goal: a due deadline becomes a notification per enabled channel, held by quiet hours or folded into a digest, and email leaves through the existing chain.

- [X] T029 [P] [US2] Create `app/Helpers/QuietHours.php` answering when a notification may go out, correct across midnight and when the two bounds are equal, and `tests/Unit/Helpers/QuietHoursTest.php`
- [X] T030 [P] [US2] Create `app/Mail/ReminderDue.php` with `resources/views/mail/reminders/due.blade.php` and `tests/Unit/Mail/ReminderDueTest.php` asserting the envelope subject and the rendered contact and date
- [X] T031 [US2] Create `app/Actions/NotifyReminderOccurrence.php` writing one row per enabled channel, copying the occurrence instant, always setting `held_until`, storing the locale, and `tests/Unit/Actions/NotifyReminderOccurrenceTest.php` covering every channel off and the unique index refusing a second row
- [X] T032 [US2] Create `app/Actions/DeliverNotification.php` rechecking the rule is active and the record present and unarchived, dispatching `SendEmail` for the email channel, counting attempts to the configured bound, recording `failed_at` and its reason, and marking the occurrence notified on the first send
- [X] T033 [US2] Create `tests/Unit/Actions/DeliverNotificationTest.php` covering a held notification before and after the window, a contact archived while it waited, a rule disabled while it waited, a failing channel keeping its row, and delivering twice telling nobody twice
- [X] T034 [US2] Create `app/Jobs/DeliverDueNotifications.php` sweeping on `held_until`, and `tests/Unit/Jobs/DeliverDueNotificationsTest.php`
- [X] T035 [US2] Create `app/Actions/SendNotificationDigest.php` folding a period into one delivery pointing the folded rows at it and sending nothing when empty, plus `app/Jobs/SendNotificationDigests.php` and a test for each
- [X] T036 [US2] Schedule delivery every five minutes and digests hourly in `routes/console.php`

## Phase 6: User Story 3, postponing or dismissing (P3)

Goal: each action changes exactly one deadline, keeps it in the record and never lets an obsolete notification escape.

- [X] T037 [P] [US3] Create `app/Actions/PostponeReminderOccurrence.php` moving only that row to the exact future instant supplied by its caller, updating its local day, `postponed_to` and count, keeping `original_scheduled_for`, refusing the past and a terminal state, abandoning unsent notifications for the old instant, logging the user action, and `tests/Unit/Actions/PostponeReminderOccurrenceTest.php` asserting the sibling, source value and sent notification history are untouched
- [X] T038 [P] [US3] Create `app/Actions/DismissReminderOccurrence.php` and `tests/Unit/Actions/DismissReminderOccurrenceTest.php`, recording `dismissed_at`, abandoning unsent notifications, keeping sent notification history, and asserting the next cycle is still generated
- [X] T039 [US3] Make `app/Actions/DeliverNotification.php` reject a dismissed occurrence or a notification whose instant no longer matches it, covered in `tests/Unit/Actions/DeliverNotificationTest.php`
- [X] T040 [US3] Add to `tests/Unit/Actions/GenerateReminderOccurrencesTest.php` that a postponed or dismissed deadline is never regenerated at its original instant

## Phase 7: User Story 4, reconciliation (P4)

Goal: every existing path that changes or silences a rule now acts on the deadlines ahead of it, and never on those behind.

- [X] T041 [P] [US4] Create `app/Actions/CancelReminderOccurrences.php` cancelling only rows still ahead, recording the reason and the previous status, inside a transaction, and `tests/Unit/Actions/CancelReminderOccurrencesTest.php`
- [X] T042 [P] [US4] Create `app/Actions/RestoreReminderOccurrences.php` restoring only rows cancelled for the reason given and still ahead, and `tests/Unit/Actions/RestoreReminderOccurrencesTest.php` proving another reason stays cancelled
- [X] T043 [US4] Generate after create in `app/Actions/CreateReminder.php`, and assert it in `tests/Unit/Actions/CreateReminderTest.php`
- [X] T044 [US4] In `app/Actions/UpdateReminder.php`, bump `schedule_revision`, cancel as rule changed and regenerate when cadence, interval, moments, time or zone moved, and do neither when only the wording changed; cover both in `tests/Unit/Actions/UpdateReminderTest.php`
- [X] T045 [US4] Cancel as rule disabled in `app/Actions/DestroyReminder.php` and restore in whatever restores it, covered in `tests/Unit/Actions/DestroyReminderTest.php`
- [X] T046 [US4] In `app/Actions/SetFieldValues.php`, bump the revision and cancel as source changed when the date is corrected, and cancel as value cleared when it is emptied, covered in `tests/Unit/Actions/SetFieldValuesTest.php`
- [X] T047 [US4] Cancel with the matching reason in `app/Actions/DeactivateReminders.php` and restore it in `app/Actions/ReactivateReminders.php`, covered in both their tests
- [X] T048 [US4] Extend `tests/Unit/Actions/ArchiveContactTest.php`, `UnarchiveContactTest.php`, `DestroyContactTest.php`, `RestoreContactTest.php`, `DetachFieldValuesTest.php` and `ReattachFieldValuesTest.php` to assert what happened to the deadlines, including that past ones never moved

## Phase 8: User Story 5, a bad reminder is refused under the form (P5)

Goal: no input to the reminder form reaches a server error.

- [X] T049 [P] [US5] Add `maximumAmount()` to `app/Enums/ReminderMomentUnitEnum.php` (365, 52, 12), read it in `app/Rules/ReminderMoments.php` instead of `Reminder::MAXIMUM_AMOUNT`, and extend `tests/Unit/Rules/ReminderMomentsTest.php`
- [X] T050 [P] [US5] Create the named exceptions in `app/Exceptions/` replacing the `InvalidArgumentException` cases of the two rule actions, one file each per Principle IV
- [X] T051 [US5] Judge the whole set of moments in `app/Http/Requests/SaveFieldValuesRequest.php`, through the same `ReminderMoments` rule and only for a reminder whose box is ticked
- [X] T052 [US5] Throw the named exceptions and validate `time_of_day` in `app/Actions/CreateReminder.php` and `app/Actions/UpdateReminder.php`, and add them to the catch list in `app/Http/Controllers/App/Vaults/ContactSectionController.php`
- [X] T053 [US5] Hold one live rule per value under concurrency in `app/Actions/CreateReminder.php`, by locking the value row inside the transaction
- [X] T054 [US5] Extend `tests/Feature/Controllers/App/Vaults/ContactSectionControllerTest.php` for a duplicate moment, a moment of 24 months, a moment of 400 days and an empty set, each a refused save with a message and never a 500

## Phase 9: Polish and the written record

- [X] T055 Run `php artisan monica:localize` and translate everything it reports in all nine locales, then `php artisan monica:localize --check`
- [X] T056 [P] Rewrite `spec/models/040-reminder-in-progress.md`: `schedule_revision`, the answered window question, the occurrences relation, and what stays undone
- [X] T057 [P] Rewrite and rename `spec/models/041-reminder-occurrence-done.md` to its new status, correcting `generation_key` per `data-model.md` and keeping retention and the webhook open
- [X] T058 [P] Rewrite and rename `spec/models/042-notification-done.md` and `spec/models/043-notification-preference-done.md`, adding `attempts` and the always-written `held_until`
- [X] T059 [P] Add the notification preferences line to `spec/models/001-account-in-progress.md` and update the file list in `spec/models/README.md`
- [X] T060 Run `vendor/bin/pint --format agent` and `vendor/bin/phpstan analyse`, fixing rather than ignoring
- [X] T061 Walk `quickstart.md` end to end, then run the full suite

## Dependencies

- Phase 1 has no dependency. Phase 2 depends on Phase 1 and blocks every story.
- US1 depends on Phase 2 only. It is the MVP and can ship alone.
- US6 depends on Phase 2 only, and blocks US2.
- US2 depends on US1 (something to notify about) and US6 (preferences to read).
- US3 depends on US1. T040 also depends on T022.
- US4 depends on US1, and on US3 for the states restoration puts back.
- US5 depends on Phase 1 only and can be done at any point after it.
- Phase 9 depends on everything, except that T056 to T059 need only the phase whose model they describe.
- Within a phase, `[P]` tasks touch different files and can run together. Everything else in a phase is sequential.
