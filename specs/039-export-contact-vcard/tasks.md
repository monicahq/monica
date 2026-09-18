---

description: "Task list for exporting a contact as a vCard"
---

# Tasks: Export a contact as a vCard

**Input**: Design documents from `/specs/039-export-contact-vcard/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md), [contracts/contact-vcard.md](contracts/contact-vcard.md), [quickstart.md](quickstart.md)

**Tests**: not optional here. Constitution III makes a test a delivery condition, so every phase carries its own and the story is not done until they pass.

**Format**: `[ID] [P?] [Story] Description`. `[P]` means a different file with no incomplete dependency.

## Phase 1: Setup

- [ ] T001 Cut the working branch `2026-09-17-export-contact-vcard` from an up to date `main`, as the constitution's workflow requires

---

## Phase 2: Foundational (blocking prerequisites)

**Purpose**: the action and the journal entry both stories depend on.

**CRITICAL**: no user story work begins until this phase is complete.

- [X] T002 Add the `ContactExported` case and its sentence `Exported the contact :name` to `app/Enums/UserActionEnum.php`, following `TemplateExported`
- [X] T003 Write `tests/Unit/Actions/ExportContactVCardTest.php`: it returns the card, it carries a sensitive value when reaching them and omits it when not, it journals on the low queue, and it throws `ModelNotFoundException` for a contact in another account
- [X] T004 Create `app/Actions/ExportContactVCard.php`, a promoted readonly constructor taking `User $user`, `Contact $contact` and `bool $reachesSensitiveFields`, one public `execute()` returning `new VCardMerger($this->reachesSensitiveFields)->merge($this->contact)`, refusing a contact outside the account and dispatching `LogUserAction` on the `low` queue

**Checkpoint**: the card can be built and is journalled. Neither surface exists yet.

---

## Phase 3: User Story 1 (P1) 🎯 MVP

**Goal**: the account holder downloads a contact as a file from the contact screen.

**Independent test**: open a contact, use the control, and confirm the downloaded file opens in an address book application carrying the name, photo and details the screen showed, sensitive values included.

- [X] T005 [P] [US1] Write `tests/Feature/Controllers/App/Vaults/ContactVCardControllerTest.php`: the download succeeds with `text/vcard` and a `Content-Disposition` naming the slug, the body carries a sensitive value, a contact in another account answers 404, and one reached through the wrong vault answers 404
- [X] T006 [P] [US1] Extend `tests/Feature/Controllers/App/Vaults/ContactControllerTest.php` to assert the contact screen offers the export route
- [X] T007 [US1] Add `vCardUrl()` to `app/ViewModels/Vaults/ContactViewModel.php`, beside `mergeUrl()`
- [X] T008 [US1] Declare `contacts.vcard.show` in `routes/web.php` **above** the `vaults/{vault}/contacts/{contact}/{page?}` catch-all at line 255, which would otherwise match `vcard` as a page name
- [X] T009 [US1] Create `app/Http/Controllers/App/Vaults/ContactVCardController.php` with `show()`, calling the action at full reach and returning a streamed download named from the contact slug
- [X] T010 [US1] Add the export button to the header row in `resources/views/app/vaults/contacts/show.blade.php`, matching the `x-button.secondary` pattern of Share links, Edit and Merge

**Checkpoint**: User Story 1 is complete and shippable on its own.

---

## Phase 4: User Story 2 (P2)

**Goal**: a program holding an API key fetches the same card.

**Independent test**: call the endpoint with a key and compare the body with the interface download for the same contact, byte for byte.

- [X] T011 [P] [US2] Write `tests/Feature/Controllers/Api/ContactVCardControllerTest.php`: a key carrying `sensitive-fields` receives the sensitive value, a key without it receives a card where the property is absent rather than empty, another account answers 404, no token answers 401, and the body matches the interface download
- [X] T012 [P] [US2] Extend `tests/Unit/Actions/BuildOpenApiDocumentTest.php` to cover a section declaring a response media type, and to prove the default is still `application/json`
- [X] T013 [US2] Add a `responseMediaType` key to `app/Actions/BuildOpenApiDocument.php`, read in `answer()`, defaulting to the present behaviour
- [X] T014 [US2] Declare the API route in `routes/api.php`, beside the other contact routes
- [X] T015 [US2] Create `app/Http/Controllers/Api/ContactVCardController.php` with `show()`, reading `ApiKeyScopeEnum::SensitiveFields` from `currentAccessToken()` the way `DavServiceProvider::reachesSensitiveFields()` does
- [X] T016 [US2] Document the endpoint in `resources/docs/api/06-contacts.php`, declaring the media type and saying what a missing scope removes

**Checkpoint**: both surfaces hand over the same card.

---

## Phase 5: User Story 3 (P3)

**Goal**: a card returning from a device updates its contact instead of duplicating it.

**Independent test**: present a card carrying a known identifier under a filing address the product has never seen, and confirm the contact count in the vault is unchanged.

- [X] T017 [P] [US3] Extend `tests/Feature/Dav/AddressBookTest.php`: a card whose identifier matches a contact in the vault updates it and creates nothing, a card whose identifier belongs to a contact in another vault creates a new contact and leaves that other record untouched, and a card with no identifier still creates one
- [X] T018 [US3] In `createCard()` in `app/Http/Dav/AddressBookBackend.php`, read `UID` through `VCardReader`, look it up with `Contact::query()->where('vault_id', $vault->id)->where('uuid', $uid)->first()`, and pass any match to `ImportVCard` as `contact:`. Filter in the query, never load and then compare

**Checkpoint**: the export no longer hands anybody a duplicate.

---

## Phase 6: Polish and cross-cutting concerns

- [X] T019 Run `php artisan monica:localize` and translate every reported string in every configured locale, never editing `lang/*.json` by hand
- [X] T020 [P] Run `vendor/bin/pint --dirty --format agent`
- [X] T021 [P] Run `vendor/bin/phpstan analyse` and fix what is new
- [X] T022 Run the full suite, not only this feature's files, because `ContactViewModel` and the contact screen are reached by many tests
- [X] T023 Cover [quickstart.md](quickstart.md) Check 3, including step 5, the identifier belonging to another vault. Done as three tests in `tests/Feature/Dav/AddressBookTest.php` rather than by hand, one of which feeds the real exported bytes back through synchronisation, so the crossing of the two subsystems is checked on every run instead of once
- [X] T024 Confirm the new control is operable with the keyboard alone, announced by a screen reader, and readable zoomed to 200 percent on desktop, tablet and phone

---

## Dependencies

- **Phase 1** blocks nothing but should come first.
- **Phase 2** blocks Phases 3 and 4. Both surfaces call the same action.
- **Phase 3** and **Phase 4** are independent of each other once Phase 2 is done.
- **Phase 5** depends on nothing in Phases 2 to 4. It touches the import path only and can be built first, last or in parallel.
- **Phase 6** depends on every story that is being shipped.
- Within a phase: T002 before T004, T003 before T004, T007 before T010, T008 before T009, T013 before T016.

## Implementation strategy

Phase 1, Phase 2, Phase 3, then stop and validate. That is the MVP: a working download, journalled, tested, on one surface. Phase 4 completes the constitution's API obligation. Phase 5 is the correction that keeps the feature from handing people duplicates, and it is the phase least safe to drop, because its absence is invisible until somebody's address book has two of everybody.
