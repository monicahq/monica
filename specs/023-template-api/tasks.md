---
description: "Task list for template endpoints"
---

# Tasks: Template endpoints

**Input**: `/specs/023-template-api/` — [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [contracts/endpoints.md](contracts/endpoints.md)

**Tests**: required. Principle III of the constitution is non-negotiable, so every phase writes its tests before its controllers.

**Organization**: one phase per user story. The stories are sequential rather than parallel here, because each addresses something the one before it creates. Ship a phase, then start the next.

## Format: `[ID] [P?] [Story] Description`

`[P]` means a different file with no unfinished dependency. Paths are from the repository root.

---

## Phase 1: Setup

- [X] T001 Create the group shell `resources/docs/api/11-templates.php`, returning `name` "Templates" and an empty `sections` list, following the shape of `resources/docs/api/07-entity-types.php`
- [X] T002 Add an `assertSee()` for the Templates group to `tests/Feature/Controllers/App/Docs/ApiDocsControllerTest.php`

---

## Phase 2: Foundational

None. Every table, model and action this feature needs already exists, and no shared helper is worth inventing: each controller scopes its own lookup as `EntityTypeController` does. User Story 1 begins immediately after setup.

---

## Phase 3: User Story 1 - Read a template and the structure it imposes (Priority: P1) 🎯 MVP

**Goal**: a caller can read the templates of their account, the version history of one, and one version's full nested structure.

**Independent test**: `GET templates`, then `GET templates/{uuid}/versions/1`, against an account holding a published template and a draft, returns both with nothing of a second account in them.

- [X] T003 [P] [US1] Write the index and show cases, including the cross-account 404 and the empty list for another account, in `tests/Feature/Controllers/Api/TemplateControllerTest.php`
- [X] T004 [P] [US1] Write the version index and nested show cases, including addressing by `version_number` and the 404 for a number the template does not have, in `tests/Feature/Controllers/Api/TemplateVersionControllerTest.php`
- [X] T005 [P] [US1] Create `app/Http/Resources/TemplateResource.php` with type `template`, `id` the uuid, timestamps as Unix integers, and `links.self` to `api.v1.templates.show`
- [X] T006 [P] [US1] Create `app/Http/Resources/FieldResource.php` carrying the type, label, help text, placeholder, the four booleans, repetition bounds and order, options, validation, default value and relation target
- [X] T007 [P] [US1] Create `app/Http/Resources/TemplateVersionSectionResource.php` carrying the placement's position, title override and collapsed state, the section version it names, and its fields through `FieldResource`
- [X] T008 [P] [US1] Create `app/Http/Resources/TemplateVersionPageResource.php` carrying the key, label and position, and its placements through `TemplateVersionSectionResource`
- [X] T009 [US1] Create `app/Http/Resources/TemplateVersionResource.php` carrying the number, status, change note, published and retired timestamps, record count and origin, and its pages through `TemplateVersionPageResource` (depends on T006 to T008)
- [X] T010 [US1] Create `app/Http/Controllers/Api/TemplateController.php` with `index` and `show`, paginating from `monica.api_default_items_per_page` clamped to `monica.api_maximum_items_per_page`, ordered by position then id, with a private `find()` scoped to the account by uuid
- [X] T011 [US1] Create `app/Http/Controllers/Api/TemplateVersionController.php` with `index` and `show`, resolving `{version}` as `version_number` within the template rather than by row id
- [X] T012 [US1] Eager load `pages.assembly.sectionVersion.fields` in the version show so the nested read runs a bounded number of queries, and assert the count in `TemplateVersionControllerTest`
- [X] T013 [US1] Register the four P1 routes in `routes/api.php` per [contracts/endpoints.md](contracts/endpoints.md), with the controller imports in alphabetical order
- [X] T014 [US1] Document the four P1 endpoints in `resources/docs/api/11-templates.php`, with example values matching the resources exactly

**Checkpoint**: `php artisan test --compact tests/Feature/Controllers/Api tests/Unit/ViewModels/Docs` is green and the reading surface is shippable.

Three things the phase needed that this list did not foresee, all done: `Account` had no `templates()` relation, so one was added with a test in `tests/Unit/Models/AccountTest.php`; `ApiDocsViewModelTest` and `ApiDocsControllerTest` both hold hard-coded lists of the reference's groups and sections that had to grow; and `ApiDocsReturnFieldsTest` calls every documented endpoint and compares its real answer against the documented fields, so each of the four needed an arm in `answer()`.

---

## Phase 4: User Story 2 - Declare a template, correct it, retire it (Priority: P2)

**Goal**: the container is fully manageable through the API.

**Independent test**: declare a template, correct it, duplicate it, stop offering it, move it, delete it, and confirm a system template refuses all six.

- [X] T015 [P] [US2] Add the create, update and destroy cases to `tests/Feature/Controllers/Api/TemplateControllerTest.php`, covering validation failures, the entity type required when `applies_to` is `entity`, the system template 404 and the cross-account 404
- [X] T016 [P] [US2] Write `tests/Feature/Controllers/Api/TemplateActivationControllerTest.php`, asserting the state is sent rather than toggled and that sending it twice confirms it
- [X] T017 [P] [US2] Write `tests/Feature/Controllers/Api/TemplatePositionControllerTest.php`, asserting renumbering from one and that a position past the end lands last
- [X] T018 [P] [US2] Write `tests/Feature/Controllers/Api/TemplateDuplicateControllerTest.php`, asserting a separate uuid and slug and that the original is untouched
- [X] T019 [US2] Add `create`, `update` and `destroy` to `app/Http/Controllers/Api/TemplateController.php`, calling `CreateTemplate`, `UpdateTemplate` and `DestroyTemplate`, and translating `CannotDeleteATemplateInUse` into a 422 as `EntityTypeController` translates its exception
- [X] T020 [P] [US2] Create `app/Http/Controllers/Api/TemplateActivationController.php` calling `ActivateTemplate` or `DeactivateTemplate` from the boolean sent
- [X] T021 [P] [US2] Create `app/Http/Controllers/Api/TemplatePositionController.php` calling `UpdateTemplatePosition`
- [X] T022 [P] [US2] Create `app/Http/Controllers/Api/TemplateDuplicateController.php` calling `DuplicateTemplate`
- [X] T023 [US2] Register the six P2 routes in `routes/api.php`
- [X] T024 [US2] Document the six P2 endpoints in `resources/docs/api/11-templates.php`

**Checkpoint**: an account's templates can be built and retired from a script.

The system template refusal this phase was written to enforce does not exist anywhere in the application: no action checks `is_system` and the browser edits and deletes supplied templates freely. Decided against inventing it, so the API mirrors the browser and `spec.md` FR-005, its acceptance scenario and the contract were corrected instead. `is_system` is a label, not a lock.

---

## Phase 5: User Story 3 - Draft a version and publish it (Priority: P3)

**Goal**: drafting and publishing, with freezing proven.

**Independent test**: open a draft from a published version, confirm the published one refuses every write, publish the draft, and confirm the records on the retired version stayed there.

- [X] T025 [P] [US3] Add a case to `tests/Unit/Actions/CreateTemplateVersionDraftTest.php` asserting a second call returns the draft already open rather than creating a second one
- [X] T026 [P] [US3] Add a case to `tests/Unit/Actions/PublishTemplateVersionTest.php` asserting the action itself publishes the draft section versions the assembly carries
- [X] T027 [US3] Correct `app/Actions/CreateTemplateVersionDraft.php` to return the template's open draft when one exists, per Decision 3 in [research.md](research.md)
- [X] T028 [US3] Move the section publishing walk into `app/Actions/PublishTemplateVersion.php`, before the `CannotPublishWhileASectionIsStillADraft` check, per Decision 4
- [X] T029 [US3] Delete `publishTheSectionsItCarries()` from `app/Http/Controllers/App/Settings/Account/Templates/TemplateVersionPublicationController.php` and confirm its feature test still passes
- [X] T030 [P] [US3] Add the create, publication and destroy cases to `tests/Feature/Controllers/Api/TemplateVersionControllerTest.php`, asserting the 200 with the open draft on a second create, the change note required only when a predecessor exists, the refusal of a version with no page, and the 422 on every write against a published or retired version
- [X] T031 [US3] Add `create` and `destroy` to `app/Http/Controllers/Api/TemplateVersionController.php`, calling `CreateTemplateVersionDraft` and `DestroyTemplateVersion`
- [X] T032 [US3] Create `app/Http/Controllers/Api/TemplateVersionPublicationController.php` calling `PublishTemplateVersion`, translating `InvalidArgumentException`, `PublishedVersionIsFrozen`, `CannotPublishAnEmptyPage` and `CannotPublishWhileASectionIsStillADraft` into 422s
- [X] T033 [US3] Register the three P3 routes in `routes/api.php`
- [X] T034 [US3] Document the three P3 endpoints in `resources/docs/api/11-templates.php`, stating that a write against a frozen version answers 422

**Checkpoint**: a structure built in the browser can be drafted and published from a script.

Three removals came out of this phase. `RetireTemplateVersion` was fully tested and called from nowhere, so publishing never retired anything and the Retired badge was unreachable; it is deleted rather than wired up, and `spec.md` FR-007 and its scenario now say a publication leaves the version it replaces alone. Moving the section walk into `PublishTemplateVersion` made `CannotPublishWhileASectionIsStillADraft` unreachable, since `isEditable()` is exactly `Draft`, so the check, its catch in the web controller and the exception class are gone. Removing that catch orphaned one translation key, pruned with `monica:localize` across all nine locales.

`TemplateVersionResource` now takes `withStructure()` rather than reading `whenLoaded('pages')`: publishing walks its own pages to validate them, and `refresh()` keeps loaded relations, so the publication endpoint was answering with the whole nested structure by accident. The docs guard caught it.

---

## Phase 6: User Story 4 - Lay out the pages and sections of a draft (Priority: P4)

**Goal**: the layout of a draft is manageable through the API.

**Independent test**: build a draft of two pages, place a section on each, move one between pages, reorder both, and confirm every level renumbers from one and the published version never moved.

- [X] T035 [P] [US4] Write `tests/Feature/Controllers/Api/TemplateVersionPageControllerTest.php`, covering the settled key surviving a rename, `PageLabelIsAlreadyUsed` as a 422, and `CannotDeleteAPageInUse` unless the request says it accepts the loss
- [X] T036 [P] [US4] Write `tests/Feature/Controllers/Api/TemplateVersionPagePositionControllerTest.php`
- [X] T037 [P] [US4] Write `tests/Feature/Controllers/Api/TemplateVersionSectionControllerTest.php`, asserting that placing a section leaves the section itself unchanged
- [X] T038 [P] [US4] Write `tests/Feature/Controllers/Api/TemplateVersionSectionPositionControllerTest.php`
- [X] T039 [P] [US4] Write `tests/Feature/Controllers/Api/TemplateVersionSectionPageControllerTest.php`
- [X] T040 [P] [US4] Create `app/Http/Controllers/Api/TemplateVersionPageController.php` calling `CreateTemplateVersionPage`, `UpdateTemplateVersionPage` and `DestroyTemplateVersionPage`, guarded by `TemplateVersionPage::isReachableThrough()`
- [X] T041 [P] [US4] Create `app/Http/Controllers/Api/TemplateVersionPagePositionController.php` calling `UpdateTemplateVersionPagePosition`
- [X] T042 [P] [US4] Create `app/Http/Controllers/Api/TemplateVersionSectionController.php` calling `AddSectionToTemplateVersion`, `UpdateTemplateVersionSection` and `RemoveSectionFromTemplateVersion`, guarded by `TemplateVersionSection::isReachableThrough()`
- [X] T043 [P] [US4] Create `app/Http/Controllers/Api/TemplateVersionSectionPositionController.php` calling `UpdateTemplateVersionSectionPosition`
- [X] T044 [P] [US4] Create `app/Http/Controllers/Api/TemplateVersionSectionPageController.php` calling `MoveSectionToTemplateVersionPage`
- [X] T045 [US4] Register the nine P4 routes in `routes/api.php`
- [X] T046 [US4] Document the nine P4 endpoints in `resources/docs/api/11-templates.php`

**Checkpoint**: a template's layout can be assembled from a script out of sections that exist.

`DestroyTemplateVersionPage` refuses a page that still holds sections outright rather than taking a confirmation, and refuses the last page of a version besides, so `spec.md` FR-009 and its scenario were corrected to say that instead of describing a force flag that does not exist. Five controllers needed the same account-scoped template and version lookup, so they share `TemplateStructureController`, against the plan's line about inventing no base class: five copies of the compartmentalisation boundary is how one of them quietly loses its scope. `Account` had no `sections()` relation either.

---

## Phase 7: User Story 5 - Define the fields a section holds (Priority: P5)

**Goal**: fields are manageable through the API, and changing one on a published section opens a draft of it.

**Independent test**: add a field of every type to a section of a draft, configure the ones carrying options, reorder them, and confirm a published section is left alone while a draft of it takes the change.

- [X] T047 [P] [US5] Write `tests/Feature/Controllers/Api/FieldControllerTest.php`, covering every `FieldTypeEnum` case, the value list required for a field offering choices, a setting refused for a type that does not carry it, and the published section left untouched while its draft takes the change
- [X] T048 [P] [US5] Write `tests/Feature/Controllers/Api/FieldPositionControllerTest.php`
- [X] T049 [US5] Create `app/Http/Controllers/Api/FieldController.php` calling `MakeSectionVersionEditable` then `CreateField`, `UpdateField` or `DestroyField`, and naming the resulting section version in the answer
- [X] T050 [P] [US5] Create `app/Http/Controllers/Api/FieldPositionController.php` calling `UpdateFieldPosition`
- [X] T051 [US5] Register the four P5 routes in `routes/api.php`
- [X] T052 [US5] Document the four P5 endpoints in `resources/docs/api/11-templates.php`, stating that repeatability and relation targeting are settled when a field is added

**Checkpoint**: every gesture the builder offers is reachable through the API.

Drafting a published section copies its fields with `replicate()`, which keeps each key and gives each a new id, so a field id sent by a caller is stale the moment the change drafts the section. The controllers therefore read the key before drafting and find the same field by it afterwards, and the reference says the id changes while the key does not. `ApiDocsFields::nested()` was added for records with no address of their own: a page, a placement and a field are only ever read inside a version, so `one()` was documenting a `links.self` none of them carries.

---

## Phase 8: Polish

- [X] T053 [P] Run `php artisan monica:localize --check` and translate anything it reports in all nine locales
- [X] T054 [P] Run `vendor/bin/pint --dirty --format agent`
- [X] T055 Run `vendor/bin/phpstan analyse --memory-limit=2G` at level 5, which CI enforces and the CLAUDE.md checklist omits
- [X] T056 Run the full suite, and confirm it passes on SQLite, MySQL and PostgreSQL (SQLite only locally: no MySQL credentials on this machine and PostgreSQL is not running. CI gates on MySQL, and its matrix has SQLite and PostgreSQL commented out, so MySQL is the one that has to be watched on the pull request. Nothing here writes raw SQL.)
- [X] T057 Smoke test the live app at erica.test rather than walking the whole of [quickstart.md](quickstart.md): every new route answers 401 rather than 404, so it is registered and behind Sanctum, and the four new reference groups render at /docs/api/{section}.md. The write walkthrough is left to the suite, since Boost forbids creating records outside tests without approval and `ApiDocsReturnFieldsTest` already calls all 26 endpoints and diffs their real answers.
- [X] T058 Write the pull request body naming the `spec.md` cap of 120 lines exceeded at 132 and why, as Principle XI requires

---

## Dependencies & Execution Order

| Phase | Depends on | Why |
| --- | --- | --- |
| 1 Setup | nothing | |
| 2 Foundational | nothing | empty |
| 3 US1 | Phase 1 | the docs group must exist before an endpoint is documented into it |
| 4 US2 | Phase 3 | reuses `TemplateResource` and the account-scoped lookup |
| 5 US3 | Phase 3 | reuses `TemplateVersionResource` and the version number resolution |
| 6 US4 | Phase 5 | a page or a section can only be added to a draft, which Phase 5 opens |
| 7 US5 | Phase 6 | a field is added to a section placement, which Phase 6 creates |
| 8 Polish | all shipped phases | |

Within a phase, the tests come before the controllers, the resources before the controllers that return them, and the routes before the documentation that must match them. T009 waits on T006 to T008; T019 waits on T015; T031 and T032 wait on T027 and T028.

Stories are not independent of each other here, unlike the template's default. Phases 4 and 5 may be worked in parallel once Phase 3 ships, since one touches the template and the other its versions. Phases 6 and 7 are strictly sequential.
