---
description: "Task list for readable vault and contact addresses"
---

# Tasks: Readable vault and contact addresses

**Input**: Design documents from `/specs/028-vault-contact-slugs/`

**Tests**: required, not optional. Principle III is non-negotiable and `.claude/rules/testing.md` governs every test file below.

**Organization**: grouped by user story. Two are P1, one P2, one P3.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: different file, no dependency on an unfinished task
- Paths are repository relative

---

## Phase 1: Setup (schema)

- [X] T001 Add `slug` as `string(128)`, commented, with `unique(['account_id', 'slug'])`, to `database/migrations/2026_08_08_000000_create_vaults_table.php`
- [X] T002 [P] Add `slug` as `string(128)`, commented, with `unique(['vault_id', 'slug'])`, to `database/migrations/2026_08_09_000007_create_contacts_table.php`
- [X] T003 [P] Run `php artisan make:migration create_vault_slug_reservations_table --no-interaction` and rename the generated file to `database/migrations/2026_08_08_000005_create_vault_slug_reservations_table.php`, the next free prefix, since 52 of the 53 migrations here are sequenced by hand rather than by the clock. Fill it with `account_id`, `vault_id`, `slug`, timestamps, `unique(['account_id', 'slug'])` and both foreign keys declared at the bottom, never through `constrained()`
- [X] T004 [P] Run `php artisan make:migration create_contact_slug_reservations_table --no-interaction` and rename it to `database/migrations/2026_08_09_000009_create_contact_slug_reservations_table.php`. Fill it with `vault_id`, `contact_id`, `slug`, timestamps, `unique(['vault_id', 'slug'])` and both foreign keys at the bottom
- [X] T005 [P] Create `app/Models/VaultSlugReservation.php` with its `belongsTo` relations and its factory, per the models skill
- [X] T006 [P] Create `app/Models/ContactSlugReservation.php` the same way
- [X] T007 [P] Create `database/factories/VaultSlugReservationFactory.php` and `database/factories/ContactSlugReservationFactory.php`
- [X] T008 Add `slugReservations()` to `app/Models/Vault.php` and `app/Models/Contact.php`, each a `HasMany` with its generic docblock

**Checkpoint**: the columns and the two reservation tables exist and nothing reads them yet.

---

## Phase 2: Foundational (blocks every story)

**⚠️ CRITICAL**: no user story work begins until this phase is done. Nothing here changes an address the product builds, so the application behaves exactly as it does today throughout.

- [X] T009 Add `SlugBuilder::readable()` to `app/Helpers/SlugBuilder.php`, sharing `build()`'s collision loop through a private method: normalise to NFC, drop every control and format character, lowercase with the root locale, reduce every run of anything that is not a letter, mark or number to one separator, trim, limit to 128, fall back to a fixed word when nothing survives
- [X] T010 Extend `tests/Unit/Helpers/SlugBuilderTest.php`: a latin name, a Japanese name that keeps its characters, a Greek name that is not romanised, a name of only emoji, a name carrying a right to left override, a Turkish `I` giving the same name whatever the account's locale, a collision taking `-2` then `-3`, a stem shortened to stay inside 128, and two names looking alike on screen settling to one form: the same name composed and decomposed collides, and a Cyrillic `а` beside a latin `a` does not
- [X] T011 [P] Create `app/Helpers/ReservedAddressWords.php` naming the segments the routes fix: `new` for a vault and a contact, and `favourites` and `archived` for a contact, which are the two values `contacts.index` constrains `filter` to
- [X] T012 [P] Create `tests/Unit/Helpers/ReservedAddressWordsTest.php` walking `Route::getRoutes()` and asserting the list still contains every fixed first segment reachable where a vault or a contact is named, by containment rather than equality so a word reserved on purpose does not fail it, so adding a route that breaks this fails the suite
- [X] T013 Add a `booted()` to `app/Models/Vault.php` whose `saving` hook assigns `slug` when the vault has none, refusing a reserved word and any name live or reserved in the account, and whose `saved` hook writes the reservation row
- [X] T014 Add the same to the `saving` hook already in `app/Models/Contact.php`, built from the whole displayed name, which is `prefix`, `first_name`, `middle_name`, `last_name`, `suffix` and `maiden_name` in the order `name_order` gives, falling back the way `nameInOrder()` already does
- [X] T015 Add `@property string $slug` to the property docblocks of `app/Models/Vault.php` and `app/Models/Contact.php`, and leave both `#[RouteKey]` attributes on `uuid`, so nothing in this phase changes an address the product builds
- [X] T016 Add a `routeKeyColumns()` returning `[$this->getRouteKeyName()]` to `app/Models/Concerns/ResolvesModelInAccount.php` and `app/Models/Concerns/ResolvesModelInVault.php`, and match any of them in the one query each already builds, inside the account and vault clauses they already apply
- [X] T017 Override `routeKeyColumns()` on `app/Models/Vault.php` and `app/Models/Contact.php` with `['slug', 'uuid']`
- [X] T018 Remove the `whereUuid('vault')` and `whereUuid('contact')` constraints from the 37 constraints in `routes/web.php`, leaving every constraint naming another model alone
- [X] T019 [P] Extend `tests/Unit/Models/VaultTest.php`: the slug is assigned on create, a second vault of the same name takes `-2`, a reserved word is refused, the reservation row is written, a vault of another account may hold the same slug, and a vault named "2024" resolves to itself rather than to the vault whose id is 2024
- [X] T020 [P] Extend `tests/Unit/Models/ContactTest.php` the same way, scoped to the vault, and covering a partial contact known by a first name alone, the Me contact, and a contact whose name is all digits meeting the primary key fallback the trait already carries
- [X] T021 [P] Create `tests/Unit/Models/Concerns/ResolvesModelInVaultTest.php` and `ResolvesModelInAccountTest.php` proving a model without an override still resolves exactly as it does today
- [X] T022 Cover FR-009 in `tests/Feature/SeedsReadableAddressesTest.php`: after `migrate:fresh --seed` every vault and every contact carries a name of its own. No command and no backfill migration: the columns are edited into the create migrations, so a database predating this change is rebuilt rather than patched

**Checkpoint**: every vault and contact carries a name, both forms resolve, and the product still builds uuids.

---

## Phase 3: User Story 1 - A person reads the address (P1) 🎯 MVP

**Goal**: the signed in screens address vaults and contacts by name, in whatever script the name is written.

**Independent Test**: create a vault and a contact, open each, read the address, and open it again later to reach the same records.

- [X] T023 [P] [US1] Extend `tests/Feature/Controllers/App/Vaults/VaultControllerTest.php`: a vault opens at its readable address, another account's readable address is not found, and the uuid still opens the same vault
- [X] T024 [P] [US1] Extend `tests/Feature/Controllers/App/Vaults/ContactControllerTest.php`: a contact opens at its readable address, an address naming a different vault of the same account is not found, two contacts of one name each reach the right person, and a contact named `田中太郎` opens at an address carrying those characters, sent encoded and unencoded
- [X] T025 [US1] Change the `#[RouteKey]` attribute to `slug` on `app/Models/Vault.php` and `app/Models/Contact.php`, which is the single point where every address the product builds becomes readable, then run `php artisan test --compact tests/Feature`
- [X] T026 [US1] Fix whatever the suite reports at T025, which is every test asserting a uuid in a path and every `->assertRedirect()` built from one

**Checkpoint**: every signed in address names its vault and its contact, and every uuid address still answers.

---

## Phase 4: User Story 2 - Nothing saved stops working (P1)

**Goal**: every address saved before this change still opens what it opened, on both surfaces, and the API takes either form.

**Independent Test**: record the address of a vault and of a contact before the change, apply it, and confirm both still answer, on a screen and through an API method.

- [X] T027 [US2] Remove the `[0-9a-fA-F-]{36}` pattern from the `vault` and `contact` parameters of the 17 route definitions under `vaults/{vault}` in `routes/api.php`, leaving the patterns naming `merge`, `candidate`, `other` and every other model alone
- [X] T028 [P] [US2] Add `slug` to the attributes of `app/Http/Resources/VaultResource.php`, keeping `id` reporting the uuid, and build `links.self` from `getRouteKey()`
- [X] T029 [P] [US2] Do the same in `app/Http/Resources/ContactResource.php`
- [X] T030 [P] [US2] Extend `tests/Feature/Controllers/Api/VaultControllerTest.php`: either form reaches the vault, `slug` is reported, `id` is still the uuid, and another account's form is not found
- [X] T031 [P] [US2] Extend `tests/Feature/Controllers/Api/ContactControllerTest.php` the same way, including a form in another script sent encoded and unencoded
- [X] T032 [US2] Update `resources/docs/api/05-vaults.php` and `resources/docs/api/06-contacts.php` from `contracts/addressing-a-vault-or-a-contact.md`: both forms accepted, `slug` reported, and the three things a client must not assume
- [X] T033 [US2] Run `php artisan test --compact tests/Feature/Controllers/Api` and fix every assertion that reads a uuid out of `links.self`

**Checkpoint**: both surfaces take both forms, and the reference says so.

---

## Phase 5: User Story 3 - The address follows the name (P2)

**Goal**: renaming a vault or a contact rebuilds its address, the previous one dies, and a contact absorbed by a merge explains itself.

**Independent Test**: rename a contact, confirm the new address answers, the previous readable one does not, and the uuid still reaches the record.

- [X] T034 [P] [US3] Extend `tests/Unit/Models/VaultTest.php` and `tests/Unit/Models/ContactTest.php`: a rename rebuilds the slug, the previous one is reserved and refused to every later record, and a rename touching no name part leaves the slug alone
- [X] T035 [US3] Rebuild the slug in both `saving` hooks when a part it is built from is dirty, keeping the reservation of the name being left behind
- [X] T036 [P] [US3] Extend `tests/Feature/Controllers/App/Vaults/ContactNameControllerTest.php`: the rename lands the reader on the new address, and the previous readable address is then not found
- [X] T037 [US3] Confirm `app/Http/Controllers/App/Vaults/ContactNameController.php` redirects to the rebuilt address, refreshing the model before the route is built rather than after
- [X] T038 [P] [US3] Create `tests/Feature/Controllers/App/Vaults/AbsorbedContactControllerTest.php`: the absorbed contact's address names the contact that absorbed it and offers a way through, a contact absorbed by an undone merge is not found, a name nobody ever held is not found, and the screen is operable with the keyboard alone and announced, which FR-015 now asks of every screen this feature adds
- [X] T039 [US3] Change `app/Http/Controllers/App/Vaults/AbsorbedContactController.php` from a redirect to a view, and find the absorbed contact by slug or uuid rather than by uuid alone
- [X] T040 [US3] Create `resources/views/app/vaults/contacts/absorbed.blade.php` with its one line comment and its `@var` block, naming the contact that absorbed this one and linking to it
- [X] T041 [P] [US3] Create `app/ViewModels/Vaults/AbsorbedContactViewModel.php` carrying the absorbed name and the surviving contact

**Checkpoint**: an address tracks the name it was built from, a released name reaches nobody, and a merge explains itself.

---

## Phase 6: User Story 4 - Keeping names out of the address (P3)

**Goal**: an account may turn readable addresses off and get uuids back, without breaking any readable address already saved.

**Independent Test**: turn the preference off and read unreadable addresses, turn it on and read readable ones, and confirm both forms answer throughout.

- [X] T042 [US4] Add `uses_readable_addresses` as a boolean defaulting to true, commented, to `database/migrations/0001_01_01_000000_create_users_table.php`, and add it to the docblock and the `#[Fillable]` of `app/Models/User.php`
- [X] T043 [P] [US4] Add `usesReadableAddresses()` to `app/Models/Account.php`, mirroring `locale()` and memoised per request by account so a list of fifty contacts does not ask fifty times
- [X] T044 [P] [US4] Extend `tests/Unit/Models/AccountTest.php`: both states, the default for a new account, and a deleted user still answering
- [X] T045 [US4] Branch `getRouteKey()` on both models on `usesReadableAddresses()`, returning the uuid when it is off
- [X] T046 [US4] Add the preference to `app/Actions/UpdatePreferences.php` and to `tests/Unit/Actions/UpdatePreferencesTest.php`
- [X] T047 [US4] Validate it in `app/Http/Controllers/App/Settings/Account/Preferences/PreferenceController.php` and report it from `app/ViewModels/Settings/Account/Preferences/PreferencesViewModel.php`
- [X] T048 [P] [US4] Create `resources/views/app/settings/account/preferences/_readable-addresses.blade.php`, included by `index.blade.php`, with its one line comment, a label saying what turning it off does, and no information carried by colour alone
- [X] T049 [P] [US4] Extend `tests/Feature/Controllers/App/Settings/Account/Preferences/PreferenceControllerTest.php`: the control renders, is operable with the keyboard alone and announced by a screen reader, the preference saves, and an invalid value is refused
- [X] T050 [US4] Create `tests/Feature/Controllers/App/Vaults/ReadableAddressPreferenceTest.php`: with it off every built address carries a uuid, with it on every built address carries a name, and every readable address answers in both states

**Checkpoint**: the preference governs what is built and never what is accepted.

---

## Phase 7: Polish and cross cutting

- [X] T051 Run `php artisan monica:localize` and translate every string it reports missing, in every configured locale, following `.claude/rules/translations.md` for the typography each language declares
- [X] T052 [P] Correct the class docblock of `app/Models/Vault.php`, which says today that the vault is found by its uuid, and the one in `app/Models/Contact.php` beside it
- [X] T053 Run `vendor/bin/pint --dirty --format agent`
- [X] T054 Run `php artisan test --compact` and confirm the whole suite passes, which is SC-002
- [X] T055 Walk `quickstart.md` end to end against a seeded database
- [X] T056 Open the pull request, saying which cap `spec.md` exceeds and why, as principle XI requires

---

## Dependencies and execution order

- **Phase 1 Setup**: no dependency. T001 to T007 are parallel apart from T005 to T007, where each factory needs its model. T008 needs T005 and T006.
- **Phase 2 Foundational**: depends on Phase 1. T009 blocks T013 and T014. T011 blocks T013 and T014. T016 blocks T017. Blocks every story.
- **US1 (Phase 3)**: depends on Phase 2. T023 and T024 are written before T025.
- **US2 (Phase 4)**: depends on Phase 2. Independent of US1 and may run beside it.
- **US3 (Phase 5)**: depends on Phase 2. T034 before T035. T038 before T039, which blocks T040 and T041.
- **US4 (Phase 6)**: depends on Phase 2, and on T025 having flipped the attribute T045 branches on. T042 blocks T043, which blocks T045.
- **Phase 7**: depends on every story you intend to ship. T051 depends on T040 and T048, the two tasks that add copy.

### Parallel opportunities

- T002 to T007 together, then T019 to T022 together.
- US1, US2 and US3 are independent of each other once Phase 2 is done.
- Inside US2, T028 to T031 are four separate files.
