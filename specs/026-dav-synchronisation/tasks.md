---

description: "Task list for address book and calendar synchronisation"
---

# Tasks: Address book and calendar synchronisation

**Input**: Design documents from `/specs/026-dav-synchronisation/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/dav.md](contracts/dav.md), [quickstart.md](quickstart.md)

**Tests**: not optional here. Constitution Principle III requires a test for every model, action, controller, helper and view model, so every producing task is paired with its test task.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: can run in parallel, different files, no dependency on an unfinished task
- **[Story]**: the user story from [spec.md](spec.md) the task belongs to

---

## Phase 1: Setup (shared infrastructure)

**Purpose**: the dependency is installed, the endpoint is mounted, and the record carries what a card needs.

- [X] T001 Require `monicahq/laravel-sabre ^2.0` in `composer.json` and install it, checking `sabre/dav` resolves to 4.7
- [X] T002 Publish `config/laravelsabre.php` with tag `laravelsabre-config`, then set `path` to `dav`, `middleware` to `[AuthenticateWithApiKeyOnBasicAuth::class, SetApiLocale::class, 'throttle:api']`, `guard` to `sanctum` and `realm` to the application name, leaving `methods` as published
- [X] T003 Add `dav_uri` and `vcard` per [data-model.md](data-model.md) to `database/migrations/2026_08_09_000007_create_contacts_table.php`, with the unique index over `vault_id` and `dav_uri`, since the migrations skill requires altering an existing migration until production
- [X] T004 Add `dav_uri` and `vcard` to the `#[Fillable]` attribute and the property docblock of `app/Models/Contact.php`
- [X] T005 [P] Add `$touches = ['valuable']` to `app/Models/FieldValue.php`
- [X] T006 [P] Add `$touches = ['valuable']` to `app/Models/FieldValueRow.php`
- [X] T007 [P] Add the `Dav` case, backed by `dav`, to `app/Enums/LogSourceEnum.php`
- [X] T008 [P] Create `app/Enums/ApiKeyScopeEnum.php` with `SensitiveFields = 'sensitive-fields'`, implementing `Translatable`
- [X] T009 [P] Extend `tests/Unit/Models/FieldValueTest.php` and `tests/Unit/Models/FieldValueRowTest.php` so saving, moving and deleting a value each move the contact's `updated_at`
- [X] T010 [P] Write `tests/Unit/Enums/ApiKeyScopeEnumTest.php`
- [X] T011 [P] Extend `tests/Unit/Models/ContactTest.php` for the two new columns

---

## Phase 2: Foundational (blocking prerequisites)

**Purpose**: a device authenticates with a key and finds its principal. No story can be reached before this.

**CRITICAL**: no user story work starts until this phase is done.

- [X] T012 Create `app/Http/Middleware/AuthenticateWithApiKeyOnBasicAuth.php`: point `Sanctum::getAccessTokenFromRequestUsing()` at the bearer token and then the Basic password, resolve the user on the `sanctum` guard, require the Basic username to equal that user's email, and answer 401 with `WWW-Authenticate: Basic` naming the application as its realm otherwise
- [X] T013 Set `log_source` to `LogSourceEnum::Dav` and the key's name as the source detail on the request attributes, in the same middleware
- [X] T014 Read `source` and `source_detail` in `app/Jobs/LogUserAction.php` from `request()->attributes` when the caller names neither, defaulting to `LogSourceEnum::Web`
- [X] T015 Create `app/Http/Dav/PrincipalBackend.php` serving one principal per user, with `{DAV:}displayname` and the email address property, per [contracts/dav.md](contracts/dav.md)
- [X] T016 Create `app/Providers/DavServiceProvider.php` registering `LaravelSabre::principal(fn ($user) => (string) $user->id)`, the `PrincipalCollection` node and the Auth and ACL plugins, plus the Browser plugin outside production, per [research.md](research.md) sections 1 and 2
- [X] T017 Register `DavServiceProvider` in `bootstrap/providers.php`
- [X] T018 [P] Write `tests/Feature/Dav/AuthenticationTest.php`: `PROPFIND /dav/` answers 207 with a key and 401 without one, with a wrong key, with a revoked key, with the account password and with another person's email, never asking for a second factor, and `last_used_at` moves on a good request
- [X] T019 [P] Write `tests/Unit/Http/Dav/PrincipalBackendTest.php`
- [X] T020 [P] Extend `tests/Unit/Jobs/LogUserActionTest.php` for the source and the source detail read from the request

---

## Phase 3: User Story 1, see the people of a vault on a device (Priority: P1)

**Goal**: every vault of the account is an address book whose cards match the records.

**Independent test**: mint a key on the security screen, connect a stock contacts application with the address that screen shows, and confirm each vault appears as an address book whose cards match the records field for field.

- [X] T021 [US1] Create `app/Http/Dav/CollectionChanges.php` computing a collection's token and its added, modified and deleted lists by the formulas in [data-model.md](data-model.md)
- [X] T022 [US1] Create `app/Http/Dav/VCardMerger.php` read path: start from the kept card or an empty vCard 4.0, rewrite the owned properties by the ownership table in [data-model.md](data-model.md), keep the device's parameters, label an appended property with the field's translated label, and set `UID` and `REV`
- [X] T023 [US1] Emit a recurring date whose year is unknown as `BDAY:--MMDD` for the `birthday` field and as `X-ABDATE` for every other, in `VCardMerger`
- [X] T024 [US1] Skip a value of a field marked sensitive in `VCardMerger` unless the key's abilities contain `sensitive-fields`, checked by membership and never by `tokenCan()`
- [X] T025 [US1] Create `app/Http/Dav/AddressBookBackend.php` read side: one address book per vault, `getCards` returning ETags without building a card, `getCard`, `getMultipleCards` batching the values and avatars of one multiget, and `getChangesForAddressBook` through `CollectionChanges`
- [X] T026 [US1] Register the address book home and the CardDAV and Sync plugins in `DavServiceProvider`, restricting the members to `Contact::inGrouping(ContactGroupingEnum::Everyone)`
- [X] T027 [US1] Add `array $scopes = []` to `app/Actions/CreateApiKey.php`, minting `abilities` as exactly that list
- [X] T028 [US1] Pass `['*']` explicitly from `app/Http/Controllers/Api/Auth/LoginController.php` and `app/Http/Controllers/Api/Auth/RegistrationController.php`, so the tokens they mint keep the reach they have today
- [X] T029 [US1] Accept `sensitive_fields`, default false, in `app/Http/Controllers/Api/ApiKeyController.php` and in `app/Http/Controllers/App/Settings/Account/Security/ApiKeyController.php`, passing the chosen scope through
- [X] T030 [US1] Add the `sensitive_fields` attribute to `app/Http/Resources/ApiKeyResource.php`
- [X] T031 [US1] Expose the DAV address and which keys reach sensitive fields from `app/ViewModels/Settings/Account/Security/SecurityViewModel.php`
- [X] T032 [US1] Add the copyable DAV address, the sensitive fields checkbox on the mint form and the badge on each key that carries the scope to `resources/views/app/settings/account/security/_api-keys.blade.php`
- [X] T033 [P] [US1] Capture one card from each verified client into `tests/Fixtures/Dav/*.vcf`
- [X] T034 [P] [US1] Write `tests/Feature/Dav/AddressBookTest.php` covering the seven acceptance scenarios of this story, including the vault of another account answering not found
- [X] T035 [P] [US1] Write `tests/Unit/Http/Dav/CollectionChangesTest.php`, covering the same second boundary and an archived contact reading as deleted
- [X] T036 [P] [US1] Write `tests/Unit/Http/Dav/VCardMergerTest.php` for the read path, the yearless date and the sensitive field gate
- [X] T037 [P] [US1] Write `tests/Unit/Http/Dav/AddressBookBackendTest.php`
- [X] T038 [P] [US1] Extend `tests/Unit/Actions/CreateApiKeyTest.php` for the scopes, and assert an existing `['*']` key does not reach sensitive fields
- [X] T039 [P] [US1] Extend `tests/Feature/Controllers/Api/ApiKeyControllerTest.php`, `tests/Feature/Controllers/App/Settings/Account/Security/ApiKeyControllerTest.php` and `tests/Unit/ViewModels/Settings/Account/Security/SecurityViewModelTest.php` for the scope and the address

---

## Phase 4: User Story 2, change somebody from the device (Priority: P2)

**Goal**: a correction, a creation and a removal made on the device land in the record, and the card comes back whole.

**Independent test**: on the device, correct a name, add a phone number, create a contact and delete one, then confirm the browser shows each change and the journal names the device's key as its source.

- [X] T040 [US2] Create `app/Http/Dav/VCardReader.php`, reading the name parts, the nickname, the photo and the owned properties of a card by the same ownership rule
- [X] T041 [US2] Create `app/Actions/ImportVCard.php` per [research.md](research.md) section 5, calling `CreateContact` or `UpdateContactName`, `SetFieldValue` for each owned value with one repeater row per value, and refusing a card with no name part
- [X] T042 [US2] Clear the field of an owned property absent from the card, and leave a sensitive field untouched when the key does not reach it, in `ImportVCard`
- [X] T043 [US2] Route the photo through `UpdateContactAvatar` only when its bytes differ from what we emit, and through `DestroyContactAvatar` when the card carries none, in `ImportVCard`
- [X] T044 [US2] Keep the card on `contacts.vcard`, normalised to vCard 4.0 and minus its `PHOTO`, at the end of `ImportVCard`
- [X] T045 [US2] Write side of `AddressBookBackend`: `createCard` answering 201, `updateCard` 204 with the new ETag, `deleteCard` through `DestroyContact`, the stale `If-Match` refused as 412, the Me contact's card refused as 403, a body that is not a vCard as 400, and a `MOVE` into another address book left to the standard's fallback of a create in the target and a removal at the source
- [X] T046 [US2] Format an `address` value in `app/Helpers/FieldValueFormatter.php`, joining the non empty components with a comma, per [data-model.md](data-model.md)
- [X] T047 [P] [US2] Write `tests/Unit/Actions/ImportVCardTest.php`, covering the creation, the update, the cleared field, the refused nameless card and the untouched sensitive field
- [X] T048 [P] [US2] Write `tests/Unit/Http/Dav/VCardReaderTest.php`
- [X] T049 [P] [US2] Extend `tests/Feature/Dav/AddressBookTest.php` for the seven acceptance scenarios of this story, including the journal naming the key, the Me contact's deletion refused, and a card moved into another vault arriving as a new record on that vault's default structure with both halves journalled
- [X] T050 [P] [US2] Extend `tests/Unit/Http/Dav/VCardMergerTest.php` with the fidelity round trip: every fixture card saved and read back differs only in the properties the record owns and in `REV`
- [X] T051 [P] [US2] Extend `tests/Unit/Helpers/FieldValueFormatterTest.php` and `tests/Unit/Actions/SetFieldValueTest.php` for the address shape

---

## Phase 5: User Story 3, the dates calendar and the task list (Priority: P3)

**Goal**: each vault offers its recurring dates as a read only calendar and its upcoming deadlines as a read only task list.

**Independent test**: subscribe a stock calendar application to a vault and find each recurring date as a yearly all day entry and each deadline inside the horizon as a task, with every write refused.

- [X] T052 [US3] Create `app/Http/Dav/ICalendarWriter.php` writing a `VEVENT` per recurring date and a `VTODO` per reminder occurrence, per [research.md](research.md) section 6
- [X] T053 [US3] Create `app/Http/Dav/CalendarBackend.php` serving two calendars per vault, their tokens and their changes, and throwing `Forbidden` from every write
- [X] T054 [US3] Create `app/Http/Dav/ReadOnlyCalendar.php` granting read alone from `getACL()`
- [X] T055 [US3] Register the calendar home and the CalDAV plugin in `DavServiceProvider`
- [X] T056 [P] [US3] Write `tests/Feature/Dav/CalendarTest.php` covering the three acceptance scenarios, the postponed occurrence at its new time, the dismissed and cancelled ones absent, and `MKCALENDAR`, `ACL` and every write refused
- [X] T057 [P] [US3] Write `tests/Unit/Http/Dav/ICalendarWriterTest.php`, covering the unknown year and the date inside a repetition
- [X] T058 [P] [US3] Write `tests/Unit/Http/Dav/CalendarBackendTest.php` and `tests/Unit/Http/Dav/ReadOnlyCalendarTest.php`

---

## Phase 6: Polish and cross-cutting concerns

- [X] T059 Write `resources/docs/api/12-devices.php` as a guide page, saying how to connect each verified client, the address, the credential, what a card carries, how a saved card is kept and merged, and what the calendar and the task list hold
- [X] T060 Add the scope field and the mint parameter to `resources/docs/api/04-api-keys.php`
- [X] T061 Run `tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php`, `ApiDocsFieldsTest.php` and `ApiDocsSectionTest.php`, and confirm the guide page joins no route while the new field is documented
- [X] T062 Run `php artisan monica:localize` and translate everything it reports, in every configured locale, then `php artisan monica:localize --check`
- [ ] T063 Walk the security screen with the keyboard alone and with a screen reader, zoomed to 200%, in light and dark, and confirm no meaning is carried by colour alone. The markup this rests on is covered by `tests/Feature/Dav/SecurityScreenAccessibilityTest.php`; the walk itself needs a person
- [X] T064 Record `dav_uri` and `vcard` in `spec/models/019-contact-done.md`, the `address` value shape in `spec/models/022-field-value-done.md`, and update `spec/models/README.md`
- [X] T065 Run `vendor/bin/pint --dirty --format agent` and `vendor/bin/phpstan analyse --memory-limit=2G` and fix what they report
- [ ] T066 Run the whole run in [quickstart.md](quickstart.md) by hand, then connect Apple Contacts and DAVx5 against it, noting the vCard version each asked for. Needs a person with the two clients; the five fixture cards under `tests/Fixtures/Dav` are written from documented client output rather than captured from devices, so this run is also where real captures replace them

---

## Dependencies

- Phase 1 blocks everything. Phase 2 blocks every story.
- T002 depends on T001, and T012 must exist before T002 can name it.
- T005 and T006 block T021, because the token is only honest once a value moves its record.
- US1 blocks US2: the write side extends the backend and the merger US1 introduces.
- US3 is independent of US2 and may land before it.
- T027 blocks T028, T029 and T038. T024 depends on T027, since the scope must be mintable before it can be read.
- T044 blocks T050, and T040 blocks T041.
- Phase 6 comes last. T062 must follow every task that adds copy, and T059 every task that changes what a card carries.

## Parallel opportunities

- T005 to T011 run together once T003 and T004 are applied.
- T018, T019 and T020 run together.
- T033 to T039 are separate files and run together once T021 to T032 are done.
- T047 to T051 run together, as do T056 to T058.
- US3 runs beside US2 entirely, since they share no file but `DavServiceProvider`.

## Implementation strategy

The MVP is Phase 1, Phase 2 and US1: every vault readable on a stock contacts application, with the key minted from the security screen. It is worth shipping alone, because a name and a number where the call is made is most of the value. US2 turns the device into a way in and is the natural second release, and it is the one that touches data, so the fidelity round trip in T050 gates it. US3 is additive and can land either side of US2.
