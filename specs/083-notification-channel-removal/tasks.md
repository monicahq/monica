# Tasks: Remove a Telegram or text message setup, and ask for another code

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [contracts/api.md](contracts/api.md), [quickstart.md](quickstart.md)

**Tests**: required by constitution principle III and `CLAUDE.md`. Follow `.claude/rules/testing.md`; data from The Office.

## Phase 1: Setup

- [X] T001 Create branch `2026-10-25-notification-channel-removal` from an up to date `main` in the repository (if `.git/config.lock` blocks `checkout -b`, build it with plumbing)
- [X] T002 [P] Add `'notification_proof_resend_seconds' => env('MONICA_NOTIFICATION_PROOF_RESEND_SECONDS', 60)` after `notification_proof_maximum_sends` in config/monica.php
- [X] T003 [P] Add `NotificationChannelSetupRemoved = 'notification_channel_setup_removed'` with wording `'Removed the setup of :name'` after `NotificationChannelCredentialRemoved` in app/Enums/UserActionEnum.php

## Phase 2: Foundational

- [X] T004 Add `holdsSetup(): bool` (destination filled, or `proof_code_hash` or `verification_token_hash` not null) with a one line docblock in app/Models/NotificationChannel.php
- [X] T005 [P] Test `holdsSetup()` for each of the three fields and for a fresh row in tests/Unit/Models/NotificationChannelTest.php
- [X] T006 `git mv` app/Actions/ResetNotificationChannel.php to app/Actions/RemoveChannelSetup.php per research R1: account check (`ModelNotFoundException`), `ChannelIsNotValid('This channel has nothing to remove.')` when `! needsDestination()`, return unchanged without journal when `! holdsSetup()`, clear every FR-003 field plus `verification_token_hash`, journal `NotificationChannelSetupRemoved` with `name` only
- [X] T007 Test in tests/Unit/Actions/RemoveChannelSetupTest.php: every field cleared and `is_enabled` false; the `LogUserAction` pushed on `low` with only `name`; email refused; a fresh row unchanged with nothing pushed; another account's row refused; a channel switched off after failures loses `disabled_reason`
- [X] T008 Create `ChannelSetupController@destroy(Request, string $channel)` per research R2 and R6 (enum `tryFrom`, the account's row without the `isOffered()` filter, `abort_unless(..., 404)`, flash `__('The setup of :name has been removed.')`, `withErrors(['channel' => ...])`) in app/Http/Controllers/App/Settings/Account/Notifications/ChannelSetupController.php
- [X] T009 Add `Route::delete('settings/account/notifications/{channel}/setup', ...)->name('settings.notifications.setup.destroy')` and delete the `reset` route in routes/web.php
- [X] T010 Delete `reset()` and its import from app/Http/Controllers/App/Settings/Account/Notifications/NotificationChannelController.php, and replace `reset_url` with `setup_url` in app/ViewModels/Settings/Account/Notifications/NotificationChannelsViewModel.php
- [X] T011 Test in tests/Feature/Controllers/App/Settings/Account/Notifications/ChannelSetupControllerTest.php: redirect and flash, email answers `errors.channel`, unknown name 404, another account's row untouched

Checkpoint: the removal works end to end on the web, with no control drawn yet.

## Phase 3: User Story 1, remove a text message number (P1) MVP

Independent test: quickstart rows US1.1 and US1.2.

- [X] T012 [US1] Add `holds_setup` and `removal_warning` (text message: `__('The number :number will be erased from your account.')`; Telegram: `__('Your Telegram conversation will be forgotten.')`, without the product name) to the channel shape in app/ViewModels/Settings/Account/Notifications/NotificationChannelsViewModel.php
- [X] T013 [US1] Replace the unwarned "Cancel this connection" form with `x-delete-action` (title `__('Remove :name', ...)`, label `__('Remove the setup')` (the existing key `Remove :name` is translated for people), action `setup_url`, `removal_warning` in the slot), drawn when `holds_setup` whether or not the channel is ready, in resources/views/app/settings/account/notifications/_channel.blade.php
- [X] T014 [US1] Test `holds_setup` and `removal_warning` for a proven number, a waiting code and a fresh row in tests/Unit/ViewModels/Settings/Account/Notifications/NotificationChannelsViewModelTest.php
- [X] T015 [US1] Test that `ConfirmChannelProofCode` refuses the old code after a removal in tests/Unit/Actions/RemoveChannelSetupTest.php

## Phase 4: User Story 2, disconnect Telegram (P1)

Independent test: quickstart row US2.2. Depends on Phase 3 for the control.

- [X] T016 [US2] Test that `CompleteChannelConnection` with the token of a connection started before the removal returns null and leaves the row unchanged in tests/Unit/Actions/RemoveChannelSetupTest.php
- [X] T017 [US2] Test the Telegram `removal_warning`, and that a removed Telegram channel gets its connect step back, in tests/Unit/ViewModels/Settings/Account/Notifications/NotificationChannelsViewModelTest.php

## Phase 5: User Story 3, ask for another code (P1)

Independent test: quickstart rows US3.1 to US3.4 and the race.

- [X] T018 [P] [US3] Create `NotificationChannelPolicy::sendProofCode(User, NotificationChannel): Response` with the four refusals of research R4, in that order, the wait through `trans_choice()` rounded up to seconds, in app/Policies/NotificationChannelPolicy.php
- [X] T019 [P] [US3] Test permitted, each refusal by name, and `nobody_is_signed_in` in tests/Unit/Policies/NotificationChannelPolicyTest.php
- [X] T020 [US3] In app/Actions/SendChannelProofCode.php: `$this->user->authorize('sendProofCode', $this->channel)` after the account check, drop the moved checks, reword `isSetUp()` to "Type a number before asking for a code.", write with the conditional update of research R5 and throw `AuthorizationException` with the wait wording when it changes no row
- [X] T021 [US3] Test in tests/Unit/Actions/SendChannelProofCodeTest.php: a second send after the wait (new hash, `proof_attempts` 0, full expiry, count plus one), refused inside the wait and at the limit with `Notification::assertNothingSent()`, the race (row's `proof_last_sent_at` moved to now after the model is loaded) sends once; update the existing tests that expected `ChannelIsNotValid` for the moved refusals
- [X] T022 [US3] Test that the previous code is refused once another is sent in tests/Unit/Actions/ConfirmChannelProofCodeTest.php (covered instead by T021's replaced hash, so not repeated: testing.md tests a rule once)
- [X] T023 [US3] Catch `AuthorizationException` beside `ChannelIsNotValid` in one `catch` into `errors.proof` in app/Http/Controllers/App/Settings/Account/Notifications/ChannelProofController.php
- [X] T024 [US3] Add `can_send_proof_code` and `proof_code_refusal` from `$this->user->inspect('sendProofCode', $row)` to the channel shape in app/ViewModels/Settings/Account/Notifications/NotificationChannelsViewModel.php
- [X] T025 [US3] Add a "Send another code" form posting to `proof_url` beside the code field in the `awaits_proof` branch; when refused, `aria-disabled="true"`, not activable, `aria-describedby` on the printed reason, in resources/views/app/settings/account/notifications/_setup.blade.php
- [X] T026 [US3] Test the two new keys for allowed, waiting and exhausted in tests/Unit/ViewModels/Settings/Account/Notifications/NotificationChannelsViewModelTest.php
- [X] T027 [US3] Test one refusal reaching `errors.proof`, and the index rendering the control with `aria-disabled` and `aria-describedby`, in tests/Feature/Controllers/App/Settings/Account/Notifications/ChannelProofControllerTest.php

## Phase 6: User Story 4, both through the API (P2)

Independent test: [contracts/api.md](contracts/api.md). Depends on Phases 2 and 5.

- [X] T028 [P] [US4] Create `Api\ChannelSetupController@destroy` with the query of T008, `ChannelIsNotValid` as `ValidationException` on `channel`, the resource with 200, in app/Http/Controllers/Api/ChannelSetupController.php
- [X] T029 [US4] Add `Route::delete('notification-channels/{channel}/setup', ...)->name('notificationChannels.setup.destroy')` next to the proof routes in routes/api.php
- [X] T030 [P] [US4] Turn `AuthorizationException` into a 422 on `channel` in app/Http/Controllers/Api/ChannelProofController.php
- [X] T031 [P] [US4] Add `can_send_proof_code` and `proof_code_refusal` through `$request->user()->inspect()` in app/Http/Resources/NotificationChannelResource.php
- [X] T032 [US4] Test 200 with `$jsonStructure`, 422 for email, 404 for an unknown name, another account's row untouched in tests/Feature/Controllers/Api/ChannelSetupControllerTest.php
- [X] T033 [US4] Test one 422 refusal inside the wait in tests/Feature/Controllers/Api/NotificationChannelControllerTest.php, where the API proof routes are already tested
- [X] T034 [US4] Document the endpoint, the two attributes and the new refusals with the `api-docs-writer` skill in resources/docs/api/15-notification-channels.php, and adjust tests/Feature/Controllers/App/Docs/ApiDocsControllerTest.php if it counts endpoints

## Phase 7: User Story 5, number kept while text messages are withdrawn (P3)

Independent test: quickstart row US5. Depends on Phases 2 and 3.

- [X] T035 [US5] Add `keptNumberRemovalUrl(): ?string`, set exactly when `smsNotice()` is, in app/ViewModels/Settings/Account/Notifications/NotificationChannelsViewModel.php
- [X] T036 [US5] Put an `x-delete-action` naming the number inside the kept number notice in resources/views/app/settings/account/notifications/index.blade.php
- [X] T037 [US5] With `sms-notifications` off for the account and `Feature::flushCache()`, test `keptNumberRemovalUrl()` in NotificationChannelsViewModelTest.php, and the web and API removals erasing the number while `GET api/v1/notification-channels/sms` stays 404, in the two ChannelSetupControllerTest.php files

## Phase 8: Polish

- [X] T038 Add "Remove a channel" and "Ask for another code" to docs/portal/en/5-reminders-and-notifications/5-notification-channels.md, update `last_updated`, no dashes as punctuation
- [X] T039 Run `php artisan monica:localize` and translate every reported phrase in every locale under lang/, following .claude/rules/translations.md
- [X] T040 Run `vendor/bin/pint --dirty --format agent` and `vendor/bin/phpstan analyse --no-progress` over app/ and tests/
- [X] T041 Run both quickstart commands, then the whole suite, since `_channel` and `_setup` partials changed (.claude/rules/components.md)
- [X] T042 Record the feature with the `feature-documentator` skill in docs/FEATURES.md

## Dependencies

- Phase 1, then Phase 2, block everything.
- US1 (Phase 3) is the MVP. US2 needs its control; US5 needs its control and Phase 2.
- US3 (Phase 5) needs only Phase 2 and can run beside Phases 3 and 4.
- US4 (Phase 6) needs Phases 2 and 5. Phase 8 comes last.
- Within a phase, tasks touching the same file run in order; `[P]` tasks touch files nobody else in the phase touches.

## Parallel examples

- Phase 1: T002 and T003.
- Phase 5 start: T018 and T019 beside T012 to T015.
- Phase 6: T028, T030 and T031 together, then T029, T032 and T033.

## Implementation strategy

Ship Phases 1 to 3 first: a warned removal of a text message number, through the web. Add Phases 4 and 5,
then the API (Phase 6), then the withdrawn case (Phase 7). Phase 8 closes every increment that is merged.
