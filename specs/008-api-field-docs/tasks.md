---

description: "Task list for the response field documentation"
---

# Tasks: Response fields in the API reference

**Input**: Design documents from `specs/008-api-field-docs/`

**Prerequisites**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md),
[data-model.md](data-model.md), [contracts/return-fields.md](contracts/return-fields.md)

**Tests**: Included, and not optional here. Constitution Principle III makes a test a condition of
merge. One of the tests is the feature: the check that compares the reference against what the
endpoints actually answer.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel with the other `[P]` tasks in the same phase (different files, no
  dependency between them)
- **[Story]**: Which user story the task belongs to (US1, US2, US3)
- Every task names the exact file it touches

## One refinement to the plan, made while writing this list

[research.md](research.md) section 7 had the check driven by a `#[DataProvider]` over a written list of
19 endpoint ids, because a provider runs before the application boots and so cannot read the reference.
That works, and it forces an awkward order: the check would be red from the moment it exists until every
one of the seven reference files has been written, so the suite could not be green between phases.

The check is therefore **one test that loops over the reference's own endpoint sections**, in the way
`ApiDocsViewModelTest` already collects and names its drift, rather than 19 provided cases. It skips a
section that has no field rows yet, and a section it has no arm for. That makes it green the day it is
written, and it turns itself on group by group as the reference is filled in, which is what gives the
person writing 230 rows a net from the second group onwards.

**The skip is scaffolding and it has an expiry.** T037 removes it, by asserting that every endpoint
section has both field rows and an arm. Until that task lands, a group nobody documents is a group
nobody checks, and that is exactly the hole FR-014 exists to close. T037 is not optional and it is not
last by accident: it is the task that makes every phase before it honest.

## What actually happened, against this list

Two things went differently, and both are improvements the list did not foresee.

**The scaffolding was never built.** The refinement above described a check that skips a section with
no field rows, kept green by that skip until T037 removed it. Written strictly instead, the check needs
no skip at all: an endpoint answering with no body documents no fields and both sides are empty, while
an endpoint answering with a body and documenting nothing fails on every field it carries. So there was
never a hole to close, and the intermediate state was a red check listing exactly what Phase 3 had to
write, which is a better to-do list than a green one that checks nothing. T037 became an explicit
statement of the same guarantee rather than the removal of an escape hatch.

**The check found a defect in the API on its first run.** Registration answered with the whole token
object rather than the string, putting the readable value at `token.plainTextToken` and carrying the row
it belongs to, its abilities and what it is attached to beside it. Signing in has always answered with
the string, and the reference has always promised one, so the code contradicted a published promise. It
is one word in `RegistrationController`, fixed here with a test of its own, rather than documented as
though it were intended.

## What couples the three stories

- **US1 is the writing, US2 is the proof, US3 is the care.** They are three passes over the same rows,
  not three separate pieces of work. Somebody implementing this end to end will write a full description
  the first time and get US3 for free on the rows they touch; the split exists so that US1 can ship on
  its own, and so that the audit in US3 is a deliberate pass rather than a hope.
- **US2's arms have to exist per endpoint before that endpoint is checked.** An arm arranges the state
  one endpoint needs and calls it. Nineteen arms across six groups, none sharing anything with another,
  which is FR-013.
- **The renderer must land before any reference row.** A row written before the block exists renders
  nowhere and proves nothing.
- **Three files carry everything for the rendering** (one component, the endpoint template, the markdown
  template), so there is no parallel work there. The seven reference files are genuinely independent of
  each other, and they are where the parallelism is.

---

## Phase 1: Setup

**Purpose**: Know the branch was green before anything moved.

- [X] T001 Run `php artisan test --compact` from the repository root and confirm the suite passes before any edit, so a later failure is known to belong to this branch

**No migration, no dependency, no configuration.** Nothing is stored by this feature.

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: The field row on the section object, the builder that writes the envelope once, and the
check, all before a single row of reference copy is written.

**⚠️ CRITICAL**: No user story work begins until this phase is complete and the suite is green.

- [X] T002 Add `returnFields` to `app/ViewModels/Docs/ApiDocsSection.php`: a `readonly array` read from the section definition as `$section['returnFields'] ?? []`, each row being `{name, type, description, opaque?}`, with the array shape in the property docblock the way `params` already carries one, plus a `hasReturnFields()` method beside the existing `hasParams()`
- [X] T003 Create `app/ViewModels/Docs/ApiDocsFields.php` with two static builders returning field rows: `one(string $type, array $attributes)` producing `data`, `data.type`, `data.id`, `data.attributes`, the supplied attribute rows prefixed `data.attributes.`, `data.links` and `data.links.self`; and `page(string $type, array $attributes)` producing `data` as an array, `data[]`, the same record rows prefixed `data[].`, then `meta`, `meta.page`, `meta.per_page` and `meta.total`. The envelope descriptions are written here once and nowhere else, per [research.md](research.md) section 3, and the class docblock says why it exists
- [X] T004 [P] Create `tests/Unit/ViewModels/Docs/ApiDocsFieldsTest.php` asserting the exact path list and order both builders produce, that the kind of record reaches `data.type`'s description, that an attribute row keeps its own type and description, and that the paging rows appear only on `page()`
- [X] T005 [P] Add cases to `tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php` (or a new `ApiDocsSectionTest.php` if the section object has no test file of its own) asserting a section with no `returnFields` reads as an empty list and `hasReturnFields()` is false, and that a section with rows reads them back unchanged
- [X] T006 Create `tests/Feature/Controllers/App/Docs/ApiDocsReturnFieldsTest.php` with one test that loops over every non-guide section of `ApiDocsViewModel`, skips a section with no `returnFields` and a section with no arm, arranges and calls the endpoint through an arm, flattens the decoded answer to a set of paths, compares that set against the documented names in both directions, and collects every disagreement into one failure message naming the endpoint and the field. No arms yet, so the test passes over everything
- [X] T007 Add the flattening rules to the same file, as a private method: an object contributes its own path then its children; a list of records contributes its own path, then a `[]` row, then the paths of its first member; a scalar or a null contributes only itself; and the walk stops at a path the reference marked `opaque`. Per [data-model.md](data-model.md)
- [X] T008 Add the empty page guard to the same file: when a documented name contains `[]` and the answer's list is empty, fail explicitly saying that endpoint needs a record arranged, rather than comparing nothing and passing. This is the silent hole named in [plan.md](plan.md)
- [X] T009 Run `php artisan test --compact tests/Unit/ViewModels/Docs/ tests/Feature/Controllers/App/Docs/` and confirm green, which at this point means the check runs and finds nothing to check

**Checkpoint**: the net exists and will turn itself on group by group. Nothing a reader sees has changed.

---

## Phase 3: User Story 1 - Know what comes back before writing any code (Priority: P1) 🎯 MVP

**Goal**: every endpoint's answer is described field by field, on the reference page and in the plain text
rendering, with each field naming its own parent.

**Independent Test**: open any endpoint in the reference and confirm every field its example response
carries is described above it with a name, a type and a sentence, and that a field on a listed record is
told apart from a field of the answer without relying on the indentation.

- [X] T010 [US1] Create `resources/views/components/api-docs/returns.blade.php` taking the section, rendering the summary sentence then one row per field, each carrying the full path in mono, the type as a word, and the description, with the left indentation computed from the number of `.` and `[]` segments in the path, using the portal's own tokens and following the shape of `resources/views/components/api-docs/params.blade.php`. Nothing in it may be distinguished by colour alone, and the comment at the top says the path rather than the indentation is what carries the parent
- [X] T011 [US1] Wire it into `resources/views/components/api-docs/endpoint.blade.php`, replacing the two lines that print the Returns heading and the summary, so the component owns the whole block and an endpoint with no rows renders exactly what it renders today
- [X] T012 [US1] Add the rows to `resources/views/app/docs/api/markdown.blade.php` under the existing `## Returns` heading, one bullet per field as ``- `path` (type): description``, in the order of the answer, flat rather than indented, since the path is the whole of the nesting in plain text
- [X] T013 [US1] Create `tests/Feature/Views/Components/ApiDocs/ReturnsTest.php` rendering the component with `$this->blade(...)`, asserting the summary shows, that a row shows its path, type and description, that indentation grows with path depth, that a section with no rows renders the summary and no rows, and that the type is text rather than a colour
- [X] T014 [P] [US1] Add field lists to `resources/docs/api/08-vaults.php` for all five vault endpoints, using `ApiDocsFields::page()` for the list and `::one()` for the three single answers with the eight vault attributes (`name`, `description`, `colour`, `position`, `is_default`, `contact_count`, `created_at`, `updated_at`), and no rows at all for the deletion, whose summary already says there is no body
- [X] T015 [P] [US1] Add field lists to `resources/docs/api/04-profile.php` for both profile endpoints, with the twelve user attributes (`first_name`, `last_name`, `email`, `email_verified`, `locale`, `timezone`, `time_format`, `date_format`, `name_order`, `number_format`, `created_at`, `updated_at`)
- [X] T016 [P] [US1] Add field lists to `resources/docs/api/05-api-keys.php` for the four key endpoints, with the five key attributes (`name`, `last_used_at`, `expires_at`, `created_at`, `updated_at`), `token` documented at the top of the creation answer beside `data` rather than inside it, and no rows for the revocation
- [X] T017 [P] [US1] Add field lists to `resources/docs/api/06-logs.php` for both log endpoints, with the seven log attributes (`author`, `action`, `parameters`, `description`, `source`, `ip_address`, `created_at`), and `data.attributes.parameters` marked `opaque` because its keys depend on the action, per [research.md](research.md) section 4
- [X] T018 [P] [US1] Add field lists to `resources/docs/api/07-emails-sent.php` for both endpoints, with eight attributes on the list (`email_type`, `subject`, `email_address`, `delivery_status`, `sent_at`, `delivered_at`, `bounced_at`, `created_at`) and the same nine including `body` when one email is read, since the copy is carried only there
- [X] T019 [P] [US1] Add the three flat rows to `resources/docs/api/02-health.php` (`status`, `version`, `instance`), which answers outside the envelope and so writes its rows directly rather than through the builder
- [X] T020 [P] [US1] Add the two flat rows to `resources/docs/api/03-authentication.php` for registration and for signing in (`token`, `expires_at`), and no rows for signing out
- [X] T021 [US1] Run `php artisan test --compact tests/Feature/Controllers/App/Docs/ tests/Feature/Views/Components/ApiDocs/ tests/Unit/ViewModels/Docs/` and confirm green, updating the pinned section count in `tests/Feature/Controllers/App/Docs/ApiDocsControllerTest.php` only if this feature changed it, which it should not

**Checkpoint**: a reader can learn every field of every endpoint, on the page and as plain text. US1 is
deliverable on its own, and nothing yet proves it stays true.

---

## Phase 4: User Story 2 - Trust the field list, because it cannot go stale (Priority: P2)

**Goal**: the reference is compared against what the endpoints actually answer, for every endpoint, in
both directions.

**Independent Test**: add a field to a resource without touching the reference and confirm the suite
fails and names that field and that endpoint; then remove a row from the reference and confirm it fails
from the other side.

- [X] T022 [US2] Add the arms for the five vault endpoints to `tests/Feature/Controllers/App/Docs/ApiDocsReturnFieldsTest.php`, each arranging its own state with `Sanctum::actingAs` and the factories, the list arm creating at least one vault so the `data[]` rows are actually verified
- [X] T023 [P] [US2] Add the arms for the two profile endpoints to the same file, the change arm sending a valid body so the answer is the changed record
- [X] T024 [P] [US2] Add the arms for the four key endpoints to the same file, the list and read arms minting a key first, the creation arm asserting nothing but returning the answer, and the revocation arm covering the endpoint that answers with no body
- [X] T025 [P] [US2] Add the arms for the two log endpoints to the same file, arranging a log whose `parameters` carries at least one key, so that the `opaque` marker is exercised rather than assumed
- [X] T026 [P] [US2] Add the arms for the two sent email endpoints to the same file, arranging an email that has been delivered, so `delivered_at` is a number rather than null on both, and the copy is present on the one that carries it
- [X] T027 [P] [US2] Add the arms for the health check, registration, signing in and signing out to the same file, the first needing no credential and the middle two creating an account the way `RegistrationControllerTest` already does
- [X] T028 [US2] Watch the check fail, three ways, following step 2 of [quickstart.md](quickstart.md): add a field to `app/Http/Resources/VaultResource.php` and confirm four vault endpoints fail naming it; remove one row from `resources/docs/api/08-vaults.php` and confirm the failure comes from the other direction; invent a row naming a field nothing returns and confirm that fails too. Undo each before the next. **A check nobody has seen go red is a check nobody knows is wired up**
- [X] T029 [US2] Prove the empty page guard, following step 3 of [quickstart.md](quickstart.md): change the vault list arm to arrange no vault and confirm the failure says a record has to be arranged, rather than passing. Undo it
- [X] T030 [US2] Run `php artisan test --compact tests/Feature/Controllers/App/Docs/ApiDocsReturnFieldsTest.php` and confirm all nineteen endpoints are checked and green

**Checkpoint**: no field can be added to, removed from or renamed in any answer without the reference
changing with it.

---

## Phase 5: User Story 3 - Know the limits without discovering them by trial (Priority: P3)

**Goal**: every description says what the field's name and type do not imply.

**Independent Test**: take the fields whose type alone is ambiguous, a bounded string, a value from a
fixed set, a field that is sometimes empty, and a time, and confirm each description states the bound,
the set, the emptiness and the unit.

**Note**: this is an audit of the rows written in Phase 3 against the checklist in
[research.md](research.md) section 6. It is a pass of its own because a description that is plausible and
wrong is the one failure the check in Phase 4 cannot catch.

- [X] T031 [US3] Across all seven files under `resources/docs/api`, state every bound and every fixed set: the maximum in characters for each bounded string, read off the validation rule that enforces it rather than remembered, and for each value drawn from a set either the values themselves or exactly where to read them. `colour` lists its eight; `source`, `time_format`, `name_order`, `number_format` and `delivery_status` each name their enum's values; `data.attributes.action` on a log is a plain string and **must not** be described as a fixed set, because the model deliberately does not cast it
- [X] T032 [US3] Across the same files, state every absence and every unit: for each field that can arrive empty, under what circumstances and what empty looks like, and for every `*_at` field that it is a count of seconds rather than a written date, which is what `->timestamp` returns
- [X] T033 [US3] Across the same files, state what each derived field and each constant is really reporting: `data.type` always holds one value and says which; `email_verified` is not a stored column; `delivery_status` is worked out from three timestamps with bounced winning; `author` is an accessor; `contact_count` counts every contact the vault holds including archived ones; `meta.total` counts the collection and not the page
- [X] T034 [US3] Re-read every row for a rule that was invented rather than found, per FR-009, and cut it. A field whose name and type already say everything gets a plain sentence, and a plausible sentence nobody checked against the code is worse than a short one

**Checkpoint**: a reader can answer, from the reference alone, how long a value can be, which values it
takes, when it is empty and what form it arrives in.

---

## Phase 6: Polish and Cross-Cutting Concerns

- [X] T035 [P] Add a case to `tests/Feature/Controllers/App/Docs/ApiDocsControllerTest.php` asserting the Returns block renders on an endpoint page, showing a path, a type and a description
- [X] T036 [P] Add a case to `tests/Feature/Controllers/App/Docs/ApiDocsMarkdownControllerTest.php` asserting the plain text rendering carries the field bullets, including a nested path, so the rendering a program is handed is covered
- [X] T037 Remove the scaffolding: add a test asserting every non-guide section of the reference has both `returnFields` and an arm in `ApiDocsReturnFieldsTest`, except the three that answer with no body, which must have neither rows nor a body to compare. **This is what makes FR-014 true and what closes the skip introduced in T006**
- [X] T038 Add a test asserting the `opaque` marker appears exactly once across `resources/docs/api`, on `data.attributes.parameters` of a log, so that turning the check off for a second field is a decision somebody has to make on purpose
- [X] T039 [P] Run `vendor/bin/pint --dirty --format agent` from the repository root and commit whatever it reformats
- [X] T040 Run `vendor/bin/phpstan analyse` and confirm it reports nothing new over `app/`, `routes/` and `database/`
- [X] T041 Run `php artisan monica:localize` and then `php artisan monica:localize --check`, confirming nothing went missing across the nine locales. No new key is expected: the reference does not go through `__()`
- [X] T042 Run the whole suite with `php artisan test --compact` and confirm it is green
- [X] T043 Walk steps 4, 5 and 6 of [quickstart.md](quickstart.md) against a running application: the five awkward endpoints render correctly, the block reads in both themes and zoomed to 200%, and the plain text rendering carries the same fields
- [X] T044 Walk step 7 of [quickstart.md](quickstart.md): take ten rows at random and confirm each against the code. This is the only guard on the half of the feature the suite cannot check

---

## Dependencies

```text
T001 (baseline)
  └─> Phase 2 Foundational
        T002 (returnFields on the section) ──> T005 (its test)
        T003 (envelope builder) ──> T004 (its test)
        T002, T003 ──> T006 (the check, skipping everything) ──> T007 (flattening) ──> T008 (empty page guard) ──> T009
              └─> Phase 3 US1
                    T010 (component) ──> T011 (wire it in) ──> T012 (markdown) ──> T013 (component test)
                    T013 ──> T014 … T020 (the seven reference files, parallel) ──> T021
                          └─> Phase 4 US2
                                T022 … T027 (the arms, parallel after T022) ──> T028 (watch it fail) ──> T029 (guard) ──> T030
                                      └─> Phase 5 US3
                                            T031, T032, T033 ──> T034
                                                  └─> Phase 6 Polish
                                                        T035, T036, T039 parallel
                                                        T037, T038 ──> T040 … T044 in order
```

**The one ordering that must not be relaxed**: T028 before this feature is called done. Everything else
in Phase 4 is code that looks correct; T028 is the only step that proves it. The three failures it asks
for are the three ways the reference and the code can come apart.

**The one task that must not be dropped**: T037. Phases 2 to 5 rely on a check that skips what it has no
arm for, which is what keeps the suite green while the reference is being filled in. Until T037 lands,
an endpoint nobody documents is an endpoint nobody checks.

**Story order**: US1 before US2 before US3. The check has nothing to compare until rows exist, and the
audit has nothing to audit until the rows are written.

## Parallel execution examples

**Phase 2**: T004 and T005 are tests of two different classes and can be written together. The rest of
the phase is a chain.

**Phase 3, after T013**: the seven reference files, T014 to T020, touch seven different files and share
nothing. This is the only place in the feature with real parallel width, and it is also the bulk of the
work, so it is where a second pair of hands would help.

**Phase 4, after T022**: the arms for the other five groups, T023 to T027, are independent of each other,
though they all edit the same test file, so they parallelise as thinking rather than as writing.

**Phase 6**: T035, T036 and T039 are independent. Everything after them is a verification run and is
sequential by nature.

## Implementation strategy

**MVP**: Phase 1 plus Phase 2 plus Phase 3, T001 to T021. That delivers what the description asked for:
every field of every endpoint documented, on the page and in plain text. A reader can build a client from
the reference alone. What it does not yet have is any guarantee that it stays true.

**Increment two**: Phase 4, nine tasks, which turns the check on for all nineteen endpoints and proves it
by breaking things on purpose. This is the increment that makes the first one worth having in a year, and
it is the one the author chose over a cheaper alternative.

**Increment three**: Phase 5, four tasks, which is where the reference stops being a list of names and
starts saving a reader every guess.

**Then**: Phase 6. T037 and T038 are not polish in the cosmetic sense: they close the scaffolding this
plan deliberately introduced. T041, T042 and T044 are checklist items in `.claude/CLAUDE.md` and gates in
CI rather than suggestions.

**Total**: 44 tasks. 1 setup, 8 foundational, 12 for US1, 9 for US2, 4 for US3, 10 polish.
