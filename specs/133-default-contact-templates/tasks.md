# Tasks: Four supplied contact templates

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [data-model.md](data-model.md), [quickstart.md](quickstart.md)

**Tests**: included, as the constitution (III) and `.claude/rules/testing.md` require. Keys, slugs and items come from data-model.md; labels and order from the spec tables.

## Phase 1: Setup

- [X] T001 Read `.claude/rules/testing.md`, `.claude/rules/code-style.md` and the `migrations`, `actions` and `translations` skills before writing code

## Phase 2: Foundational (blocks every story)

- [X] T002 [P] Create `HobbyEnum`, `CommunicationChannelEnum`, `IndustryEnum` implementing `SuppliedValueListItem` in `app/Enums/`, items exactly as data-model.md "Value lists"
- [X] T003 Add `Hobbies` (`hobbies`), `CommunicationChannels` (`communication_channels`), `Industries` (`industries`), `AreasOfExpertise` (`areas_of_expertise`, no items) with label, description and items to `app/Enums/ValueListEnum.php`
- [X] T004 [P] Add the new field cases to `app/Enums/DefaultFieldEnum.php` with keys, types, children, value lists, `repetitionOrder()` (every new repeater `NewestLast`), `relationTarget()` (`introduced_by` → contact) and `isKeptOffTheCard()` returning `gift_idea_link`, `outing_idea_link`, per data-model.md "Field keys"
- [X] T005 Add the 28 section cases (slugs from data-model.md "Sections"), labels, icons, colours, `fields()` and `fieldLabel(DefaultFieldEnum)` falling back to `DefaultFieldEnum::label()` in `app/Enums/DefaultSectionEnum.php`; labels from the spec tables, with "Free-form notes", "Job role" and "Current job role" (R10)
- [X] T006 Add the eight page cases with `key()` and `sections()` in `app/Enums/DefaultPageEnum.php`
- [X] T007 Add `Essentials` (`essentials`), `FriendsAndFamily` (`friends-and-family`), `ProfessionalNetwork` (`professional-network`), `FullPortrait` (`full-portrait`) with label, description, `pages()`, `appliesTo()` contact and `isVaultDefault()` (true only for `BasicContact`, `BasicAnimal`) in `app/Enums/DefaultTemplateEnum.php`
- [X] T008 Write the template description and label each field through `fieldLabel()` in `app/Jobs/CreateDefaultTemplate.php`; call `seedTheVaultDefaults()` only when `isVaultDefault()`
- [X] T009 [P] Extend `tests/Unit/Enums/DefaultFieldEnumTest.php`: no key names two concepts (one type per value), every key the spec lists as shared reuses the Basic contact case

**Checkpoint**: any account can be supplied the four templates by calling the job.

## Phase 3: User Story 1, new account starts with the four templates (P1) MVP

**Independent test**: create an account; it holds six templates, every vault defaults to Basic contact, and the user's contact is on Basic contact.

- [X] T010 [P] [US1] In `tests/Unit/Jobs/CreateDefaultTemplateTest.php`, one test per new template asserting page keys, section slugs and field keys in order, and that no `VaultDefaultTemplate` row points at it
- [X] T011 [P] [US1] In `tests/Unit/Actions/CreateVaultTest.php` and `tests/Unit/Actions/DestroyTemplateTest.php`, with the five system contact templates present, the default and the fallback are Basic contact
- [X] T012 [US1] Select the contact default by `slug` = `basic-contact` instead of the first system template in `app/Actions/CreateVault.php::seedTheContactDefault()`
- [X] T013 [US1] Fall back only to a system template whose slug is a vault default slug in `app/Actions/DestroyTemplate.php::fallBackToTheSystemTemplate()`
- [X] T014 [US1] Prefer the `basic-contact` template in `app/ViewModels/Vaults/VaultFormViewModel.php::suppliedStructureVersionId()`
- [X] T015 [US1] Create `app/Jobs/SupplyContactTemplates.php` (queued, class docblock): `CreateDefaultValueLists` with the four lists, then `CreateDefaultTemplate` for each new template in spec order, skipping with `Log::warning` a template whose slug or section slug the account's own template or section already holds
- [X] T016 [US1] Add `new SupplyContactTemplates(user: $this->user)` after the Basic animal job in the chain of `app/Actions/CreateAccount.php::seedTheAccount()`
- [X] T017 [US1] In `tests/Unit/Actions/CreateAccountTest.php`, assert the chain carries `SupplyContactTemplates` and Basic contact stays inline and default

## Phase 4: User Story 2, existing accounts receive the four templates (P1)

**Independent test**: an account holding Basic contact, its own template and contacts gains the four templates and nothing else changes; a second run adds nothing.

- [X] T018 [P] [US2] Create `tests/Unit/Jobs/SupplyContactTemplatesTest.php`: templates and lists added; vault defaults and Basic contact unchanged; second run adds nothing; a deleted supplied template is not restored; a held slug skips only that template
- [X] T019 [US2] Create `database/migrations/2026_11_18_000004_backfill_supplied_contact_templates.php` with `up()` only: `User::query()->chunkById(200, …)` dispatching `SupplyContactTemplates` on the `low` queue, one per account

## Phase 5: User Story 3, values follow the contact between templates (P2)

**Independent test**: moving a contact from Basic contact to Essentials keeps its birthday, emails, phone, address and note; gift ideas never land in outing ideas.

- [X] T020 [P] [US3] In `tests/Unit/Actions/MoveRecordToStructureVersionTest.php`: value follows the same key and type in another section; repeater children follow their group; two candidates set the value aside; a same-section match is unchanged; a birthday reminder follows
- [X] T021 [US3] Add the `key|type` fallback over carried top-level fields, used only when exactly one matches, and move children only under the field their parent followed to, in `app/Actions/MoveRecordToStructureVersion.php::followTheFieldsIntoTheNewVersion()` (R5)
- [X] T022 [P] [US3] Create `tests/Unit/Helpers/OwnedFieldsTest.php`: `gift_idea_link` and `outing_idea_link` map to no vCard property, `website` and `online_profile_link` map to `URL`
- [X] T023 [US3] Return null for a key in `DefaultFieldEnum::isKeptOffTheCard()` in `app/Helpers/OwnedFields.php::propertyFor()`

## Phase 6: User Story 4, the new choice lists (P3)

**Independent test**: a new and a backfilled account list Hobbies, Communication channels, Industries filled and Areas of expertise empty.

- [X] T024 [US4] In `tests/Unit/Actions/CreateDefaultValueListsTest.php`, the four lists are created with their items, `areas_of_expertise` with none, and an account holding them already gets nothing new

## Phase 7: Polish

- [X] T025 Run `php artisan monica:localize` and translate the new strings with sonnet subagents per the `translations` skill; French wording verbatim from the spec, French typography rules applied
- [X] T026 [P] List the six supplied templates in `docs/portal/en/9-templates-and-customisation/1-introduction.md`
- [X] T027 [P] Write the changelog entry per the `changelog` skill
- [X] T028 Run the `definition-of-done` skill (Pint, PHPStan, `monica:localize --check`, `PAO_DISABLE=true php artisan test --parallel --compact`) and the quickstart checks by hand on monica.test

## Dependencies

- Phase 2 blocks everything; T003 needs T002, T005 needs T004, T006 needs T005, T007 needs T006, T008 needs T007.
- US1 (T010 to T017) before US2 (T019 dispatches the job of T015).
- US3 and US4 depend only on Phase 2 and can run beside US1.
- Polish last.

## Parallel examples

- Phase 2: T002, T004 and T009 together.
- US1: T010 and T011 together, then T012, T013, T014 together (three files).
- US3: T020 and T022 together, then T021 and T023 together.

## Strategy

MVP is Phases 2 and 3: new accounts get the templates. US2 ships in the same pull request, since the backfill is the other half of the request. US3 must ship with them, or contacts moved to a new template lose sight of their emails.
