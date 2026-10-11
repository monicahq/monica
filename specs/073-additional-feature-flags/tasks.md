# Tasks: Additional feature flags

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [contracts/flags.md](contracts/flags.md), [quickstart.md](quickstart.md)

**Tests**: included, because constitution principle III makes them a merge condition. Follow
`.claude/rules/testing.md`. A test of a gate turns the flag off with `$account->features()->deactivate('<name>')`,
or `Feature::for(null)->deactivate()` for a visitor, then calls `Feature::flushCache()` before asserting (R8).

## Phase 1: Setup

- [X] T001 Create app/Features/SmsNotifications.php, CardDav.php, VCardExport.php and FileUpload.php, each a copy of app/Features/Marketplace.php named `sms-notifications`, `carddav`, `vcard-export`, `file-upload` (R1)
- [X] T002 Create app/Features/McpServer.php named `mcp-server`, with `resolve(?Account $account): bool` returning `true` when `$account` is null and `Feature::for(null)->active(self::class)` otherwise (R1)
- [X] T003 Add `setUp()` to tests/TestCase.php calling `Feature::define()` with `true` for the four names of T001 (R8), then run `php artisan test --compact` and confirm the suite is as green as on main

## Phase 2: Foundational

- [X] T004 [P] Write tests/Unit/Features/ShippedStateTest.php: each of the four classes of T001 resolves off for an account with nothing stored (call `resolve()` directly, past T003's definitions), `McpServer` resolves on for the null scope and for an account, and off for an account once `DeactivateFlagForEveryAccount` ran (FR-002)
- [X] T005 [P] Amend .claude/rules/flags.md: a flag may be a permanent switch that ships on (the MCP server), and a withdrawn capability may say so in one sentence to an account holding data under it (FR-013, FR-014)

## Phase 3: User Story 1, CardDAV and vCard export (P1)

**Goal**: neither reaches an account until switched on. **Independent test**: T006, T009 and T011 pass.

- [X] T006 [US1] In tests/Feature/Dav/AuthenticationTest.php: with `carddav` off for Jim, a PROPFIND on his address book and on his calendar answers 404; without credentials it still answers 401; Dwight switched on gets 207 (FR-003)
- [X] T007 [US1] Append `'flag:carddav'` after `AuthenticateWithApiKeyOnBasicAuth::class` in the `middleware` list of config/laravelsabre.php (R2)
- [X] T008 [US1] Add `davIsOn(): bool` to app/ViewModels/Settings/Account/Security/SecurityViewModel.php and wrap the address block of resources/views/app/settings/account/security/_api-keys.blade.php (lines 27 to 44) in it, with its test in tests/Unit/ViewModels/Settings/Account/Security/SecurityViewModelTest.php
- [X] T009 [P] [US1] In tests/Feature/Controllers/App/Vaults/ContactVCardControllerTest.php and tests/Feature/Controllers/Api/ContactVCardControllerTest.php: with `vcard-export` off, the download answers 404 (FR-004)
- [X] T010 [US1] Put `contacts.vcard.show` in routes/web.php (line 366) and `contacts.vcard.show` in routes/api.php (line 229) behind `flag:vcard-export`
- [X] T011 [US1] Make `vCardUrl()` in app/ViewModels/Vaults/ContactViewModel.php return `?string`, null when `vcard-export` is off, test it in tests/Unit/ViewModels/Vaults/ContactViewModelTest.php, and draw the menu item of resources/views/app/vaults/contacts/show.blade.php (lines 50 to 52) only when it is set

## Phase 4: User Story 2, SMS notifications and file upload (P2)

**Goal**: neither reaches an account until switched on, and nothing is lost. **Independent test**: T012, T014, T016, T019 and T024 pass.

- [X] T012 [US2] In tests/Unit/Models/NotificationChannelTest.php: with `sms-notifications` off, the SMS row is not offered, `mayCarry()` is false although enabled, set up and proven, the email row is unaffected, and binding `sms` as a route key finds nothing (FR-005)
- [X] T013 [US2] Add `isOffered(): bool` to app/Models/NotificationChannel.php (false for `NotificationChannelEnum::Sms` while `FlagState::isOn('sms-notifications', $this->account)` is false), require it in `mayCarry()`, and override `resolveRouteBinding()` to return null for a row not offered while keeping `ResolvesModelInAccount`'s lookup (R4)
- [X] T014 [US2] In tests/Unit/Actions/DeliverNotificationTest.php: a queued SMS notification with the flag off is abandoned, and the channel's `consecutive_failures`, `disabled_reason` and `is_enabled` are unchanged; in tests/Unit/Actions/NotifyReminderOccurrenceTest.php no SMS row is written
- [X] T015 [US2] In app/Actions/DeliverNotification.php, abandon a notification whose channel row is not offered before `hand()`, beside the `isStillWarranted()` check, so `stumble()` never records a failure for it (R4)
- [X] T016 [P] [US2] In tests/Feature/Controllers/Api/NotificationChannelControllerTest.php: the index leaves the SMS row out and `GET notification-channels/sms` answers 404; in tests/Feature/Controllers/App/Settings/Account/Notifications/NotificationChannelControllerTest.php `PUT .../notifications/sms` answers 404
- [X] T017 [US2] Leave the row out of `index()` in app/Http/Controllers/Api/NotificationChannelController.php and of `rows()` in app/ViewModels/Settings/Account/Notifications/NotificationChannelsViewModel.php, and add `smsNotice(): ?string`, set only when the row holds a destination, with its tests in NotificationChannelsViewModelTest.php (FR-014)
- [X] T018 [US2] Draw `smsNotice()` with `x-notice` in resources/views/app/settings/account/notifications/index.blade.php
- [X] T019 [P] [US2] In tests/Feature/Controllers/App/Settings/Account/Profile/PhotoControllerTest.php and both ContactAvatarControllerTest.php: with `file-upload` off, updating answers 404 and deleting still works (FR-007, FR-010)
- [X] T020 [US2] Put `settings.photo.update` (routes/web.php line 592), `contacts.avatar.update` (routes/web.php line 254) and `contacts.avatar.update` (routes/api.php line 241) behind `flag:file-upload`
- [X] T021 [US2] In tests/Unit/Helpers/FieldValueRulesTest.php: with the flag off, a file, image or avatar field, required or not, is `prohibited` with "Uploads are unavailable on this account for now."; then pass the account into app/Helpers/FieldValueRules.php and change `fileRules()` accordingly (R6, FR-008)
- [X] T022 [P] [US2] Prove the refusal reaches the reader once per surface: tests/Feature/Controllers/App/Vaults/ContactSectionControllerTest.php (errors on `fields.{id}`, nothing saved) and tests/Feature/Controllers/Api/EntityControllerTest.php (422)
- [X] T023 [US2] In tests/Unit/Actions/ImportVCardTest.php: with the flag off, a vCard carrying a PHOTO is imported and the contact keeps its avatar; then return early from `writeThePhoto()` in app/Actions/ImportVCard.php (FR-009)
- [X] T024 [US2] Expose `uploadsAreOn` from app/ViewModels/Settings/Account/Profile/ProfileViewModel.php, app/ViewModels/Vaults/ContactEditViewModel.php, ContactViewModel.php (sections and life events) and EntityFormViewModel.php, with a test of each in its existing view model test where one exists
- [X] T025 [US2] Without uploads, draw no `x-file-input` and draw the notice where a file is held or the field is required, in resources/views/app/settings/account/profile/index.blade.php, app/vaults/contacts/edit.blade.php, components/field-input.blade.php and components/field-repetitions.blade.php, asserted in tests/Feature/Views/Components/FieldInputTest.php (FR-014)

## Phase 5: User Story 3, the MCP server (P3)

**Goal**: works for every account until the administrator withdraws it. **Independent test**: T026 passes.

- [X] T026 [US3] In tests/Feature/Mcp/McpAuthenticationTest.php: with nothing stored Pam's call answers; with `mcp-server` off for Pam it answers 404 and Dwight's still answers; in tests/Feature/Mcp/McpOAuthTest.php `GET oauth/authorize` answers 404 to Pam (FR-006)
- [X] T027 [US3] Add `'flag:mcp-server'` after `'auth:sanctum,api'` in routes/ai.php, and `abort_unless(FlagState::isOn('mcp-server', $parameters['user']->account), 404)` at the top of the `Passport::authorizationView()` closure in app/Providers/AppServiceProvider.php (R3)
- [X] T028 [US3] Add `assistantsNotice(): ?string` to SecurityViewModel.php, set only when the flag is off and an assistant is connected, test it in SecurityViewModelTest.php, and draw it in resources/views/app/settings/account/security/index.blade.php; hide the section when the flag is off and none is connected (FR-014)

## Phase 6: User Story 4, the documentation (P4)

**Goal**: a visitor reads only about what every account can use. **Independent test**: T029 passes.

- [X] T029 [US4] In tests/Feature/Controllers/App/Docs/CarddavDocsControllerTest.php and McpDocsControllerTest.php: a visitor gets 404 when the flag is off for every account and 200 once on (FR-011)
- [X] T030 [US4] Put `docs.carddav.index` and `docs.carddav.markdown.index` behind `flag:carddav`, and `docs.mcp.index` and `docs.mcp.markdown.index` behind `flag:mcp-server`, in routes/web.php (lines 139 to 149)
- [X] T031 [US4] Leave a capability that is off out of `areas()` and `llmsText()` in app/ViewModels/Docs/DocsViewModel.php and out of the "Your addresses" box of resources/views/app/docs/index.blade.php (lines 60 to 64), tested in tests/Unit/ViewModels/Docs/DocsViewModelTest.php
- [X] T032 [US4] Add `'flag' => 'vcard-export'` to the `contacts-vcard` entry of resources/docs/api/06-contacts.php, and skip an entry whose flag is off in app/ViewModels/Docs/ApiDocsViewModel.php and app/Actions/BuildOpenApiDocument.php, tested in ApiDocsViewModelTest.php and BuildOpenApiDocumentTest.php

## Phase 7: Polish

- [X] T033 Run `php artisan monica:localize` and translate every new string in every configured locale (`.claude/rules/translations.md`)
- [X] T034 [P] Add the entry to docs/FEATURES.md with the feature-documentator skill
- [ ] T035 Run `php artisan test --compact`, `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent`, then walk through quickstart.md

## Dependencies

- T001 and T002 come first; T003 before any test runs; T004 and T005 after T001 and T002.
- The four story phases depend only on Phases 1 and 2, and touch different files except SecurityViewModel.php (T008, T028) and routes/web.php (T010, T020, T030): do those in order.
- Within a phase, a test task precedes the change it proves.
- Phase 7 after every story.

## Parallel Examples

- After Phase 2: T006, T007 and T009 (US1) run beside T012 to T018 (US2, SMS); US4 waits for T010 and T020, which also edit routes/web.php.
- Inside US2: T016, T019 and T022 are test files no other task edits.

## Strategy

US1 alone is a shippable increment: the two capabilities with the highest risk withdrawn and proven.
Then US2, US3 and US4 in order; each leaves the suite green on its own.
