# Tasks: Multilingual contact names

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/api.md](contracts/api.md), [contracts/vcard.md](contracts/vcard.md)

**Tests**: required by constitution principle III. Each phase writes its tests before the code they prove.

## Phase 1: Foundational (blocks every story)

- [X] T001 Generate with `php artisan make:migration` and rename to database/migrations/2026_10_06_000000_create_contact_name_representations_table.php: `id`; `uuid` unique; `foreignIdFor(Contact::class)->comment(…)->constrained()->cascadeOnDelete()`; `kind` string; `language_tag` string(35) nullable; `script_code` char(4) nullable; `phonetic_system` string(32) nullable; `formatted_name`, `prefix`, `given_name`, `additional_name`, `family_name`, `suffix` string(255) nullable; `name_order` string nullable; `sort_name` string(511); `search_key` string(511) indexed; `match_key` string(64) indexed; `vcard_alt_id` string(64) nullable; `vcard_property_id` string(255) nullable; `position` unsigned int; timestamps; a `->comment()` on every column; `up()` only
- [X] T002 Create database/migrations/2026_10_06_000001_add_display_name_to_contacts_table.php with `Schema::table('contacts')`: `foreignIdFor(ContactNameRepresentation::class, 'display_name_representation_id')->nullable()->after('suffix')->comment(…)->constrained()->nullOnDelete()` and `formatted_name` string(255) nullable after it; leave every shipped migration untouched
- [X] T003 [P] Create app/Enums/ContactNameKindEnum.php: `Written = 'written'`, `Transliteration = 'transliteration'`, `Phonetic = 'phonetic'`, `label()` returning `__('Written form')`, `__('Transliteration')`, `__('Pronunciation')`
- [X] T004 Create app/Models/ContactNameRepresentation.php: `#[Fillable]` every column but `sort_name`, `search_key`, `match_key`; casts for `kind` and `name_order`; `belongsTo(Contact)` with its generic docblock; a `saving` hook writing the three keys through `LocaleCollation` in the contact's account locale (research R9, data-model); `displayText()` returning `formatted_name` else the parts in `name_order`; `saved` and `deleted` hooks that re-save the contact when it is the displayed row and send it to search with `resendTheRecordsNamingIt()` either way (R3); full model docblocks
- [X] T005 [P] Create database/factories/ContactNameRepresentationFactory.php with a written default and `transliteration()` and `phonetic(string $system)` states
- [X] T006 In app/Models/Contact.php: add `nameRepresentations()` ordered by `position`, `displayNameRepresentation()`, `nameFor(?string $script = null, ?ContactNameKindEnum $kind = null)`; remove `prefix`, `first_name`, `middle_name`, `last_name`, `suffix`, `name_order` from `#[Fillable]`; in `saving`, when `display_name_representation_id` is dirty or a flag set by the representation hook is on, copy `prefix`, `given_name`, `additional_name`, `family_name`, `suffix`, `name_order`, `formatted_name` from the display row (nulls when none)
- [X] T007 In app/Models/Contact.php, make `name`, `initials`, `buildSortName()`, `buildMatchToken()` and `nameForAddress()` fall back on `formatted_name` before nickname and maiden name, and `name` return `formatted_name` first (research R9)
- [X] T008 In database/factories/ContactFactory.php add an `afterCreating` that creates a written display representation from the generated parts when the contact has any and none exists, then points the contact at it
- [X] T009 Write tests/Unit/Models/ContactNameRepresentationTest.php: keys built on save; `displayText()` given first and family first; a formatted name wins over the parts
- [X] T010 Extend tests/Unit/Models/ContactTest.php: the projection follows the display row and ignores a secondary one; changing a secondary row keeps `slug`, `sort_name` and `sort_key`; a contact whose display row holds only 김민지 as `formatted_name` reads, sorts and gets initials from it; `nameFor(script: 'Latn')` and `nameFor(kind: Phonetic)`
- [X] T011 Change app/Actions/CreateContact.php: write the parts as a first written representation and point the contact at it in the same transaction; accept an optional `array $nameRepresentations` and `?int $display` used instead of the parts (research R2); write nothing when every part is empty
- [X] T012 Change app/Actions/UpdateContactName.php: rewrite the display representation's parts (creating a written one and pointing at it when there is none), keep nickname and maiden name on the contact, keep the existing log and duplicate dispatch
- [X] T013 Update tests/Unit/Actions/CreateContactTest.php and tests/Unit/Actions/UpdateContactNameTest.php: the parts land on the display representation; a contact without one gets one; an all empty rename leaves a nameless contact with its row deleted and no pointer
- [X] T014 Create database/migrations/2026_10_06_000002_backfill_contact_name_representations.php: query builder only, chunks of 500 contacts with any name part, one written row each at `position` 1 with keys from `LocaleCollation` in the account's locale, then the pointer; no model save, no job dispatched (research R12)
- [X] T015 Write tests/Unit/Migrations/BackfillContactNameRepresentationsTest.php following BackfillNotificationPreferencesTest.php: named contacts get one displayed written row and an unchanged `name`; nameless contacts get none; no job is pushed
- [X] T016 Run the whole suite once and fix what the projection and the factory break before any story starts

## Phase 2: User Story 1, record several ways to write one name (P1)

Independent test: give Kim Min-ji three representations on the edit screen, select another display name, save, and read the list, the page and the initials.

- [X] T017 [US1] Write tests/Unit/Actions/UpdateContactNameRepresentationsTest.php: creates, updates and deletes in one call; the first row of a contact that had none is displayed; `display` outside the list refused; a row with neither formatted name nor part refused; phonetic without `phonetic_system` refused and non phonetic with one refused; `language` and `script` patterns refused when wrong; an `id` of another contact refused; another account's contact `ModelNotFoundException`; an empty list leaves the contact nameless; nickname and maiden name saved; `ContactNameUpdate` logged on `low`; `DetectDuplicateContacts` pushed
- [X] T018 [US1] Create app/Actions/UpdateContactNameRepresentations.php (user, contact, list, display, nickname, maidenName): `sanitize()` with `TextSanitizer`, `validate()`, then in one `DB::transaction` delete the rows not sent, update or create the rest with `position` from list order, set the pointer, save the contact; `log()` with `ContactNameUpdate` afterCommit; dispatch `DetectDuplicateContacts` afterCommit; class docblock of one sentence
- [X] T019 [US1] Change app/Http/Controllers/App/Vaults/ContactNameController.php `update` to validate `names`, `names.*.*`, `display`, `nickname`, `maiden_name` inline exactly as contracts/api.md, then call the action and redirect with the existing flash
- [X] T020 [US1] Change app/Http/Controllers/App/Vaults/ContactController.php `create` to accept the same `names` shape, requiring at least one row with a formatted name or a part, and pass it to `CreateContact`
- [X] T021 [P] [US1] Extend app/ViewModels/Vaults/ContactEditViewModel.php and app/ViewModels/Vaults/ContactFormViewModel.php with the rows to edit (uuid, every column, `displayText()`, a meta line of ICU language name · ICU script name · kind label), the display index, and the option lists for language, script and pronunciation system from research R8, adding a stored code outside the list as one more option
- [X] T022 [P] [US1] Create resources/js/contact-name-editor.js (registered in resources/js/app.js like life-event-type-fields.js): staged rows, add opening an editor set to transliteration, edit, remove, the replacement choice when the displayed row is removed, Name parts disclosure, the disabled states of Add, Done and Save the name, hidden inputs `names[i][…]` and `display`; find its own markup with `el.closest('[data-name-editor]')`
- [X] T023 [US1] Create resources/views/components/contact-name-editor.blade.php following Edit Contact.dc.html: one row shows the parts grid and Add another way to write this name with its help line; several rows show a `<fieldset>` of native radio inputs with legend Name, each row with Edit and Remove, the Display name badge, and the help line saying searching any of them finds the contact; editor fields Type, Language, Writing system, Pronunciation system, Full name, Name parts; the removal card with Remove and switch and Keep it; errors tied with `aria-describedby`; built from existing components and Tailwind, never `mn-*` classes; opening comment and `@var` block
- [X] T024 [US1] Use the component in resources/views/app/vaults/contacts/edit.blade.php (replacing the name fields, keeping nickname and Name before marriage under it) and in resources/views/app/vaults/contacts/form.blade.php; saving with every part emptied on a contact that had a name asks for confirmation first
- [X] T025 [P] [US1] Write tests/Feature/Views/Components/ContactNameEditorTest.php: one row renders the parts and the add button; several render a radio group whose checked input is the display row; a stored language outside the list is offered
- [X] T026 [P] [US1] Update tests/Feature/Controllers/App/Vaults/ContactNameControllerTest.php and ContactControllerTest.php: the set is saved and redirects with the flash; a phonetic row without system returns the error on `names.0.phonetic_system`; a contact reached through another vault returns 404
- [X] T027 [P] [US1] Update tests/Unit/ViewModels/Vaults/ContactEditViewModelTest.php and ContactFormViewModelTest.php for the rows, meta line and option lists
- [X] T028 [US1] Run the Phase 2 test files

## Phase 3: User Story 2, find the contact by any of its names (P1)

Independent test: search 김민지, Kim Min-ji and 金敏智 in the people list and in vault search.

- [X] T029 [US2] Extend tests/Unit/Models/ContactTest.php: `matchingSearch` finds a contact by each representation and not by an unrecorded transliteration; `searchableName()` lists every representation, display first; `searchableLinkedNames()` of a related contact lists them all
- [X] T030 [US2] In app/Models/Contact.php: `scopeMatchingSearch` adds `orWhereExists` on `contact_name_representations.search_key` with the folded term; `searchableName()` and `searchableLinkedNames()` join every representation with a newline, display first, eager loading `nameRepresentations`
- [X] T031 [P] [US2] In app/Models/LifeEvent.php `searchableLinkedNames()` list every representation of each participant
- [X] T032 [US2] Add a test in tests/Unit/Actions/SearchRecordsTest.php: each of the three names finds the contact once, labelled with the display name; a life event whose participant gains a representation is found by it after reindexing
- [X] T033 [US2] Write the failing cases in tests/Unit/Jobs/DetectDuplicateContactsTest.php: two contacts sharing only a transliteration are paired; sharing only a pronunciation is paired with reasons naming it
- [X] T034 [US2] Change app/Jobs/DetectDuplicateContacts.php: block on representation `match_key` joined to `contacts.vault_id` as well as `contacts.match_key`; compare given names pair by pair between representations sharing a key (research R4)
- [X] T035 [P] [US2] Add a test to tests/Unit/ViewModels/Vaults/ContactsViewModelTest.php counting queries for a page of 50 and of 100 contacts with three representations each: equal counts (SC-005)
- [X] T036 [US2] Run the Phase 3 test files

## Phase 4: User Story 3, import, export and synchronise (P2)

Independent test: import tests/Fixtures/Dav/multilingual-names.vcf over CardDAV, export it, import the export, compare.

- [X] T037 [P] [US3] Create tests/Fixtures/Dav/multilingual-names.vcf (three pairs as contracts/vcard.md, one phonetic `N`, a `SORT-AS` and an unknown `X-` parameter) and tests/Fixtures/Dav/single-name-client.vcf (one plain `N`, no `PROP-ID`)
- [X] T038 [US3] Extend tests/Unit/Http/Dav/VCardReaderTest.php: every `N`/`FN` read and paired by `PROP-ID`, then `LANGUAGE`+`SCRIPT`, then order; kinds from `PHONETIC` and `X-NAME-KIND`; an `FN` alone gives a formatted name only; display from `PREF=1` else first non phonetic
- [X] T039 [US3] Change app/Http/Dav/VCardReader.php to return the list of representations with their kind, codes, parts, `ALTID`, `PROP-ID` and display flag, keeping `nameParts()` callers working until T041
- [X] T040 [US3] Extend tests/Unit/Actions/ImportVCardTest.php: a new card creates one contact with every row; a card with several `N` reconciles (matched updated, unmatched created, missing deleted); single-name-client.vcf updates the display row only and deletes nothing (SC-004); a lone `N` carrying a `PROP-ID` of ours updates that row only; unmanaged properties stay in the kept card
- [X] T041 [US3] Change app/Actions/ImportVCard.php `writeTheName`: new contact through `CreateContact` with the list; several `N` through `UpdateContactNameRepresentations` matched by `PROP-ID` or stored `vcard_property_id`; one `N` through `UpdateContactName` aimed at the named or displayed row (research R6)
- [X] T042 [US3] Extend tests/Unit/Http/Dav/VCardMergerTest.php: export matches contracts/vcard.md line for line; `SORT-AS` and unknown parameters copied back by `PROP-ID`; a nameless contact writes `FN` with Unnamed contact; export, import, export is identical (SC-001)
- [X] T043 [US3] Change app/Http/Dav/VCardMerger.php `writeName` to write one `N` per representation and one `FN` per non phonetic one with `ALTID=1`, `PROP-ID`, `PREF=1`, `LANGUAGE`, `SCRIPT`, `PHONETIC`, `X-NAME-KIND`, carrying over unmanaged parameters from the kept property with the same `PROP-ID` (research R5)
- [X] T044 [US3] Create app/Http/Dav/CardDavPlugin.php extending `Sabre\CardDAV\Plugin`, overriding `convertVCard()` to keep only the `PREF=1` `N` and `FN` without `ALTID` and `PROP-ID` when the target is vCard 3.0; register it in app/Providers/DavServiceProvider.php instead of sabre's
- [X] T045 [US3] Write tests/Unit/Http/Dav/CardDavPluginTest.php: 3.0 output holds one `N` and one `FN` without `ALTID` or `PROP-ID`; 4.0 output is unchanged
- [X] T046 [US3] Extend tests/Feature/Dav/AddressBookTest.php: PUT of multilingual-names.vcf then GET with `Accept: text/vcard; version=4.0` round trips; a following PUT of single-name-client.vcf keeps the other two rows
- [X] T047 [US3] Run the Phase 4 test files

## Phase 5: User Story 4, the API and MCP (P2)

Independent test: list and replace a contact's representations over the API and through MCP.

- [X] T048 [P] [US4] Change app/Http/Resources/ContactResource.php: add `formatted_name` and `name_representations` (shape of contracts/api.md, `is_display` from the pointer) when loaded; eager load `nameRepresentations` in Api/ContactController.php `index` and `show`
- [X] T049 [US4] Create app/Http/Controllers/Api/ContactNameRepresentationController.php (`index`, `update`, validation inline as contracts/api.md, `name_representations` max 20) and add the two routes in routes/api.php named `contacts.nameRepresentations` and `contacts.nameRepresentations.update`
- [X] T050 [US4] Write tests/Feature/Controllers/Api/ContactNameRepresentationControllerTest.php with a `$jsonStructure`: index shape; update saves and returns the contact; 422 on a foreign `id` and on `names` rules; 404 through another vault; another account's contact not returned; update tests/Feature/RouteProtectionTest.php if it lists routes
- [X] T051 [P] [US4] Create app/Mcp/Tools/ListContactNameRepresentations.php and app/Mcp/Tools/UpdateContactNameRepresentations.php with `MirrorsAnApiCapability` and the routes above; register both in app/Mcp/Servers/EricaServer.php
- [X] T052 [P] [US4] Write tests/Unit/Mcp/Tools/ListContactNameRepresentationsTest.php and UpdateContactNameRepresentationsTest.php
- [X] T053 [P] [US4] Document both endpoints and the new attributes in resources/docs/api/06-contacts.php; update the `PUT …/name` description to say it rewrites the displayed name
- [X] T054 [US4] Run the Phase 5 test files and the API docs tests

## Phase 6: User Story 5, merge, snapshot and deletion (P3)

Independent test: merge two contacts with different names, choose the absorbed one's name, undo.

- [X] T055 [US5] Extend tests/Unit/Actions/PreviewContactMergeTest.php, MergeContactsTest.php and UndoContactMergeTest.php: the `display_name` conflict appears when both have a name; choosing `merged` displays the absorbed contact's row; non duplicate rows moved, exact duplicates left behind; undo puts rows and pointer back
- [X] T056 [US5] Change app/Actions/PreviewContactMerge.php (conflict, fingerprint includes representation uuids and `updated_at`), app/Actions/MergeContacts.php (ledger kinds `name_representation_moved` and `display_name_replaced` with `was`) and app/Actions/UndoContactMerge.php (replay both backwards) per research R10
- [X] T057 [P] [US5] Offer the display name choice in app/ViewModels/Vaults/ContactMergeViewModel.php and resources/views/app/vaults/contacts/merge/new.blade.php as a radio pair; accept `display_name` in both ContactMergeControllers and expose it in app/Http/Resources/ContactMergePreviewResource.php
- [X] T058 [US5] Add `name_representations` and `display_name_representation_uuid` to `payload.record` in app/Actions/CreateRecordSnapshot.php and assert them in tests/Unit/Actions/CreateRecordSnapshotTest.php
- [X] T059 [US5] Assert in tests/Unit/Actions/DestroyContactTest.php that a soft deleted contact keeps its rows and pointer
- [X] T060 [US5] Run the Phase 6 test files

## Phase 7: Polish

- [X] T061 [P] Write spec/models/057-contact-name-representation-done.md; point spec/models/019-contact-done.md (name columns are a projection, nameless contacts accepted) and spec/models/034-contact-merge-done.md (display name conflict, two ledger kinds) at it
- [X] T062 Run `php artisan monica:localize`, translate every reported string in every locale using the glossary, and confirm `php artisan monica:localize --check` passes
- [X] T063 Run `vendor/bin/pint --dirty --format agent` and `vendor/bin/phpstan analyse --no-progress`, fixing every finding
- [X] T064 Run the whole suite, and confirm in tests/Feature/Views/Components/ContactNameEditorTest.php that every control of the editor is a native button, input or select with a label, so the keyboard path needs no script of its own

## Dependencies

- Phase 1 blocks everything. T001 before T002 before T004; T004 and T006 before T008; T011 and T012 before T013; T014 after T004.
- US1 (Phase 2) before US3, US4 and US5, which call `UpdateContactNameRepresentations`. US2 needs only Phase 1.
- US3, US4 and US5 are independent of one another once US1 is done.
- Polish last.

## Parallel examples

- Phase 1: T003 and T005 together.
- US1: T021, T022, then T025, T026, T027 together.
- US3, US4 and US5 can run side by side after US1; inside US4, T048, T051, T052 and T053 together.

## Implementation strategy

MVP is Phase 1 and US1: names are stored, chosen and shown, and the API keeps working through the projection. US2 next, since a name that cannot be searched is half useful. Ship US3 before any release to CardDAV users: until it lands, `VCardMerger` writes only the displayed name and a device cannot see the others, though it cannot delete them either.
