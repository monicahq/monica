# Tasks: Account export and import

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [contracts/](contracts/), [quickstart.md](quickstart.md), models [058](../../spec/models/058-account-export-done.md), [059](../../spec/models/059-account-import-done.md), [060](../../spec/models/060-account-import-item-done.md)

**Tests**: required by constitution III; each test task precedes the code it proves. No test uses the real example file; fixtures are small hand-written files.

## Phase 1: Setup

- [X] T001 Read `.claude/rules/testing.md`, `.claude/rules/policies.md`, `.claude/rules/flags.md`, `.claude/rules/components.md`, the `actions`, `controllers`, `models`, `migrations`, `routes`, `views`, `view-models`, `api-writer`, `api-docs-writer` and `translations` skills, then app/Actions/ImportVCard.php, app/Http/Dav/OwnedFields.php, app/Actions/MergeContacts.php and app/Helpers/StructureWriter.php as the references
- [X] T002 Run `composer require halaxa/json-machine` (approved 2026-10-18, research R3) and leave composer.json and composer.lock for the owner to commit on their own
- [X] T003 [P] Create tests/Fixtures/Imports/monica-4-small.json in the `1.0-preview.1` shape of contracts/monica-4-mapping.md: Michael (Me), Dwight with a birthday of unknown year and an age based one on Jim, emails, phones, an address, two notes, a yearly and a past one time reminder, a gift, a debt, a task, two tags, a `parent`/`child` pair and a `godfather`/`godson` pair, a life event `first_kiss`, an activity shared by two contacts, a call, a conversation, a pet, a 1x1 JPEG photo as avatar, a journal entry and a day rating, a partial contact, an inactive one, a `google2fa_secret`, an `addressbook_subscription` with a username and a URI
- [X] T004 [P] Create tests/Fixtures/Imports/monica-4-invalid.json (truncated JSON) and tests/Fixtures/Imports/monica-4-future.json (`version` `9.0`)

## Phase 2: Foundational

- [X] T005 Create app/Features/AccountTransfer.php with `#[Name('account-transfer')]` resolving through `Feature::for(null)`, and add it to the flags turned on in tests/TestCase.php
- [X] T006 [P] Create app/Enums/AccountExportStatusEnum.php (`preparing`, `ready`, `failed`, `expired`), AccountImportStatusEnum.php (`reading`, `refused`, `read`, `writing`, `rolling_back`, `rolled_back`, `completed`), AccountImportSourceEnum.php (`monica_4`, `monica`), AccountImportOutcomeEnum.php (`imported`, `merged_into_me`, `left_out`)
- [X] T007 [P] Add `AccountExportRequested`, `AccountExportDownloaded`, `AccountImportStarted`, `AccountImportEnded` with their `description()` arms to app/Enums/UserActionEnum.php, and `AccountExportReady`, `AccountImportEnded` to app/Enums/EmailType.php
- [X] T008 Create the migration, model, factory and `RouteKey` uuid for AccountExport exactly as the Fields of spec/models/058 say, in database/migrations, app/Models/AccountExport.php, database/factories/AccountExportFactory.php
- [X] T009 Create the same for AccountImport (spec/models/059) and AccountImportItem (spec/models/060, `kind` string (32), `reason` string (255), `record` morph), with `items()` ordered by `position` on AccountImport
- [X] T010 [P] Write tests/Unit/Policies/AccountExportPolicyTest.php and AccountImportPolicyTest.php: `create` permitted, refused while one of the account's is unfinished, and refused with nobody signed in (`nobody_is_signed_in`)
- [X] T011 Create app/Policies/AccountExportPolicy.php and AccountImportPolicy.php with `create` only (ownership stays with scoping, `.claude/rules/policies.md`), wording "An export is already being prepared." and "An import is already running."
- [X] T012 Run the policy tests and `tests/Unit/Policies/PolicyCoverageTest.php`

**Checkpoint**: flag, models and policies exist; nothing reachable yet.

## Phase 3: User Story 1, move from Monica 4 to Monica (P1, MVP)

**Independent test**: import tests/Fixtures/Imports/monica-4-small.json into a new vault and check every row of the mapping contract on the resulting records and the report.

- [X] T013 [P] [US1] Write tests/Unit/Actions/ReadMonicaFourFileTest.php: items per kind and positions from the small fixture, the reverse relationship row skipped, `me_contact` stored as the Me candidate, summary counts, no row or payload holding `google2fa_secret`, the subscription username or its URI, refusal for the invalid and the future fixtures
- [X] T014 [US1] Create app/Actions/ReadMonicaFourFile.php iterating `/account/data` and `/account/properties` with `JsonMachine\Items`, writing AccountImportItem rows in the order structures, types, files, contacts, contact contents, relationships, life events, reminders, entities, Me, journal; files decoded from `dataUrl` to the disk under `accounts/{id}/transfers/{import uuid}`
- [X] T015 [P] [US1] Write tests/Unit/Actions/CreateAccountImportTest.php: stores the upload under the account prefix, records `source_checksum`, dispatches `ReadAccountImport`, refused by the policy while another runs, logs `AccountImportStarted`
- [X] T016 [US1] Create app/Actions/CreateAccountImport.php and app/Jobs/ReadAccountImport.php (detects the format from `version` or `format`, calls the reader, sets `read` or `refused` with the reason)
- [X] T017 [P] [US1] Write tests/Unit/Actions/StartAccountImportTest.php: new vault created, existing vault of another account not found, refused over the contact, vault and storage caps naming the numbers, `already_imported` when the checksum was imported into that vault, first batch dispatched
- [X] T018 [US1] Create app/Actions/StartAccountImport.php (research R7) dispatching `WriteAccountImportBatch`
- [X] T019 [P] [US1] Write tests/Unit/Actions/WriteAccountImportItemTest.php, one test per rule-carrying row of contracts/monica-4-mapping.md: name parts, gender items, unknown year, age based note, website or note per contact field kind, address parts and label, notes, photo and avatar, reminder at next occurrence, past one time left out, inactive deactivated, archived last, life event natures and the account's own nature, activity with both participants, call, conversation, pet with caregiver, gift, debt, task and tags as notes, journal on Me, `created_at` kept, logs carry `LogSourceEnum::Import` and the import uuid, `DetectDuplicateContacts` queued on `low` for each imported contact (`Queue::assertPushedOn`)
- [X] T020 [US1] Create app/Actions/WriteAccountImportItem.php writing one item through `CreateContact`, `SetFieldValue` (field found by `DefaultFieldEnum` key in the contact's version, a missing field becoming a note naming it), `CreateRelationship`, `CreateRelationshipType`, `CreateLifeEvent` (`LifeEventSourceEnum::Import`), `CreateLifeEventType`, `CreateContactReminder`, `CreateEntity`, `UpdateContactAvatar`, `ArchiveContact`; returns the outcome, reason and record
- [X] T021 [US1] Add the Me item to app/Actions/WriteAccountImportItem.php: `MergeContacts` of the imported contact into the vault's Me with every conflict and collision `kept`; test in T019 that Me keeps its birthday and gains the imported email and relationships
- [X] T022 [P] [US1] Write tests/Unit/Jobs/WriteAccountImportBatchTest.php: 50 items per run, items with an outcome skipped on retry, `items_done` advanced, next batch dispatched, `completed` and `AccountImportEnded` sent at the end, a thrown error dispatches the rollback
- [X] T023 [US1] Create app/Jobs/WriteAccountImportBatch.php with `failed()` dispatching the rollback
- [X] T024 [P] [US1] Write tests/Unit/Actions/RollBackAccountImportTest.php and CancelAccountImportTest.php: every created record hard deleted, the storage total and `contact_count` back, the Me merge undone, a created vault destroyed, `rolled_back` at the end
- [X] T025 [US1] Create app/Actions/RollBackAccountImport.php, app/Jobs/RollBackAccountImportBatch.php and app/Actions/CancelAccountImport.php (research R2)
- [X] T026 [P] [US1] Write tests/Unit/Notifications/AccountImportEndedTest.php (subject, panel, action URL to the report, `emailType()`) and create app/Notifications/AccountImportEnded.php on queue `high`
- [X] T027 [P] [US1] Write tests/Unit/ViewModels/Settings/AccountImportViewModelTest.php: summary per kind, the left out reasons translated, the Me candidates searchable, progress, report counts
- [X] T028 [US1] Create app/ViewModels/Settings/AccountImportViewModel.php
- [X] T029 [P] [US1] Write tests/Feature/Controllers/App/Settings/Account/Transfer/ImportControllerTest.php: upload refused over 512 MB and without a file, start validated, cancel warned and redirecting, another account's import not found, flag off answers not found
- [X] T030 [US1] Create the Import, ImportContact and ImportReport controllers under app/Http/Controllers/App/Settings/Account/Transfer, routes one per line in routes/web.php behind `flag:account-transfer`, named `settings.accountImports.*`
- [X] T031 [US1] Create resources/views/app/settings/account/transfer/import.blade.php and import-report.blade.php with `x-settings.page`, `x-page-section`, `x-list-panel`, a live region for progress, `x-delete-action` for cancel, and the entry in resources/views/components/settings/sidebar.blade.php behind the flag
- [X] T032 [P] [US1] Write tests/Unit/Console/ImportAccountCommandTest.php and create app/Console/Commands/ImportAccountCommand.php (`monica:import-account`, contracts/api.md), running the batches synchronously and printing each kind and a summary
- [X] T033 [US1] Update spec/models/059 and 060 to `-in-progress` or `-done` with what is built, and the README rows

## Phase 4: User Story 2, take my data out of Monica (P2)

**Independent test**: export a vault holding a contact of each value type, an entity, a relationship, a life event, a reminder and a file, and find each in the JSON.

- [X] T034 [P] [US2] Write tests/Unit/Actions/WriteAccountExportTest.php: the envelope of contracts/export-format.md, one structure document per version in use, values by section and field key, files base64, nothing of FR-013's left out list present, soft deleted and detached left out
- [X] T035 [US2] Create app/Actions/WriteAccountExport.php streaming with `fwrite` and a `convert.base64-encode` filter to the configured disk under `accounts/{id}/transfers`
- [X] T036 [P] [US2] Write tests/Unit/Actions/CreateAccountExportTest.php and tests/Unit/Jobs/BuildAccountExportTest.php: one at a time, `ready` with `expires_at` seven days on, `failed` on an exception, `AccountExportReady` sent, `AccountExportRequested` logged
- [X] T037 [US2] Create app/Actions/CreateAccountExport.php, app/Jobs/BuildAccountExport.php, app/Notifications/AccountExportReady.php (with its test in tests/Unit/Notifications)
- [X] T038 [P] [US2] Write tests/Unit/Jobs/PurgeExpiredAccountExportsTest.php and create app/Jobs/PurgeExpiredAccountExports.php, scheduled daily in routes/console.php
- [X] T039 [P] [US2] Write tests/Unit/ViewModels/Settings/Account/Transfer/AccountTransferViewModelTest.php and extend app/ViewModels/Settings/Account/Transfer/AccountTransferViewModel.php with the exports and the vaults to choose from
- [X] T040 [P] [US2] Write tests/Feature/Controllers/App/Settings/Account/Transfer/AccountExportControllerTest.php and AccountExportDownloadControllerTest.php: create redirects with the warning confirmed, download streams for the holder, logs `AccountExportDownloaded`, 404 for another account, before ready and after expiry
- [X] T041 [US2] Create the AccountExport and AccountExportDownload controllers, routes `settings.accountExports.create` and `settings.accountExportDownloads.show`, and an Export section in resources/views/app/settings/account/transfer/index.blade.php with the FR-014 warning before the button
- [X] T042 [US2] Update spec/models/058 and its README row

## Phase 5: User Story 3, bring an Monica export into another instance (P3)

**Independent test**: export a vault with `WriteAccountExport`, read it with `ReadMonicaFile` into an empty vault of a second account, and compare every contact's values.

- [X] T043 [P] [US3] Write tests/Unit/Actions/ReadMonicaFileTest.php: items per kind, a file of two vaults yields items for two new vaults each with its Me, refusal of a `version` other than `2.0-preview.1`, unknown keys ignored
- [X] T044 [US3] Create app/Actions/ReadMonicaFile.php
- [X] T045 [P] [US3] Write a test in tests/Unit/Actions/WriteAccountImportItemTest.php that a structure item creates a template copy with every version in use published in order, and records land on the copy of their version (research R5), with `DetectDuplicateContacts` queued for each contact written
- [X] T046 [US3] Add structure, life event type and relationship type items to app/Actions/WriteAccountImportItem.php through `StructureWriter`, publishing each version
- [X] T047 [US3] Add a round trip test in tests/Unit/Actions/AccountTransferRoundTripTest.php: export then import into an empty vault gives the same contacts, values, relationships, life events, reminders and files (SC-002)

## Phase 6: API, docs and polish

- [X] T048 [P] Write tests/Feature/Controllers/Api/AccountExportControllerTest.php and AccountImportControllerTest.php per contracts/api.md: 202 shapes with `$jsonStructure`, 403 without `sensitive-fields`, 422 for a cap and a busy account, 404 for another account, download stream
- [X] T049 Create the five API controllers, app/Http/Resources/AccountExportResource.php and AccountImportResource.php, routes in routes/api.php behind `flag:account-transfer`
- [X] T050 Create resources/docs/api/NN-account-transfer.php documenting every route, so tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php passes
- [X] T051 Run `php artisan monica:localize` and translate every reported string in every locale; `php artisan monica:localize --check`
- [X] T052 Run `vendor/bin/phpstan analyse --no-progress`, `vendor/bin/pint --dirty --format agent` and the whole suite
- [X] T053 Walk quickstart.md by hand, keyboard only and with a screen reader on both screens, at 200% zoom and on a phone width, and time through a queue worker a file of 2,000 contacts with 20 MB of photos, generated by a throwaway script outside the repository, against SC-001's 10 minutes
- [X] T054 In the pull request that ships story 3, delete app/Features/AccountTransfer.php and every check of it, and note `php artisan pennant:purge --except-registered` in its deployment notes

## Dependencies

- Phase 1 and 2 block everything. T002 blocks T014 and T044.
- US1 (Phase 3) is the MVP and blocks nothing else except T033.
- US2 (Phase 4) needs Phase 2 only. US3 (Phase 5) needs US1 (item writing, T020) and US2 (T035) for its round trip.
- Phase 6 needs the stories whose endpoints it exposes; T054 comes last.

## Parallel work

- In Phase 3, the tests T013, T015, T017, T019, T022, T024, T026, T027, T029 touch separate files and can be written together; their implementations follow one by one.
- US2 can run beside US1 after Phase 2, in its own files.
