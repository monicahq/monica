---
description: "Task list for the contact timeline and adding a life event"
---

# Tasks: Contact timeline and adding a life event

**Input**: Design documents from `specs/046-contact-timeline/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/life-event-panel.md, quickstart.md

**Tests**: Required (constitution III). Follow `.claude/rules/testing.md`: strict types, `#[Test]`, snake_case `it_` names, named arguments, The Office data, no comments, no class string or markup assertion. Each rule is tested once, in the layer that owns it.

**Organization**: the foundation is the record and the action. US3 (reading) comes before US1 (the panel) although both are P1, because the panel’s "Just added" entry lands on the timeline. US2 and US4 follow.

## Format: `[ID] [P?] [Story] Description`

## Phase 1: Setup

No setup: no dependency or configuration change (plan.md).

## Phase 2: Foundational

**Purpose**: the life event exists, can be recorded through one action, and the field engine accepts it as an owner (research R1 to R3). Blocks every story.

- [X] T001 [P] Create `app/Enums/DatePrecisionEnum.php` (string backed: `Day = 'day'`, `Month = 'month'`, `Year = 'year'`, `Decade = 'decade'`, `Unknown = 'unknown'`) and `app/Enums/LifeEventSourceEnum.php` (`Day = 'day'`, `Contact = 'contact'`, `Api = 'api'`, `Mcp = 'mcp'`, `Import = 'import'`, `Module = 'module'`), one enum per file
- [X] T002 [P] In `app/Enums/UserActionEnum.php`, add `LifeEventCreation = 'life_event_created'` after the `LifeEventType*` cases, described as `'Recorded the life event :type on the timeline of :name'`
- [X] T003 [P] Create `app/Exceptions/LifeEventTypeIsNotAvailable.php`, an empty `RuntimeException` subclass shaped like `app/Exceptions/RelationshipTypeIsNotAvailable.php`
- [X] T004 Generate with `php artisan make:migration create_life_events_table --no-interaction`, rename to `database/migrations/2026_09_23_000000_create_life_events_table.php`, and write `up()` only with every column of `spec/models/036` in its order, each with `->comment()`: `happened_on` date required; `date_precision` string default `'day'`; `created_from` string default `'contact'`; `title` and `location` "string 255, nullable"; `narrative` text nullable; `duration_in_minutes` unsigned int nullable; `is_shareable` and `narrative_is_shareable` boolean default false; `participant_count` unsigned int default 0; `latitude`/`longitude` decimal(10,7) nullable; `ended_on` date nullable; `happened_at_time` time nullable; `timezone` string 64 nullable; timestamps and soft deletes; index `(vault_id, happened_on)`; foreign keys at the bottom (never `constrained()`) for `account_id`, `vault_id`, `life_event_type_id`, `structure_version_id` and `created_by_user_id` (null on delete)
- [X] T005 Generate and rename `database/migrations/2026_09_23_000001_create_life_event_participants_table.php`: `account_id`, `life_event_id`, `participant_type` string, `participant_id` unsigned big int, `role` and `notes` string 255 nullable, `position` unsigned int default 0, timestamps; unique `(life_event_id, participant_type, participant_id)`; index `(participant_type, participant_id, life_event_id)`; foreign keys at the bottom, `life_event_id` cascading on delete
- [X] T006 Create `app/Models/LifeEvent.php` with a full model docblock (every `@property`), `#[Fillable]`, `HasFactory`, `HasUuids`, `ResolvesModelInVault`, `SoftDeletes`; casts `happened_on` and `ended_on` to `date`, `date_precision` to `DatePrecisionEnum`, `created_from` to `LifeEventSourceEnum`, the two share flags to boolean; relations `account`, `vault`, `lifeEventType` (`withTrashed()`), `structureVersion`, `createdBy`, `participants` (`hasMany`, ordered by `position`), `fieldValues` and `fieldValueRows` (`morphMany`, `'valuable'`); and an `Attribute<string, never>` accessor `name` returning `title`, else the type’s translated name
- [X] T007 [P] Create `app/Models/LifeEventParticipant.php` (docblock, `#[Fillable]`, `HasFactory`, `lifeEvent()` belongsTo, `participant()` morphTo, `account()`) and add to `app/Models/Contact.php` a documented `lifeEvents()` morphToMany through `life_event_participants` (`participant` morph, `withPivot('position')`)
- [X] T008 [P] Create `database/factories/LifeEventFactory.php` (a type of the same account with a published structure, its `structure_version_id`, a vault of that account, `happened_on` in 2026, `date_precision` day, `created_from` contact) and `database/factories/LifeEventParticipantFactory.php` (a contact of the life event’s vault)
- [X] T009 Create `tests/Unit/Models/LifeEventTest.php` proving the `name` accessor: the title when set, the type’s name when the title is null
- [X] T010 Widen `Contact|Entity` to `Contact|Entity|LifeEvent` on the constructor and docblocks of `app/Actions/SetFieldValues.php`, `app/Actions/SetFieldValue.php` and `app/Actions/CreateFieldValueRow.php`, and the `@property-read $valuable` docblocks of `app/Models/FieldValue.php` and `app/Models/FieldValueRow.php`
- [X] T011 In `tests/Unit/Actions/SetFieldValuesTest.php`, add `it_writes_the_values_of_a_life_event` (a text and a number field on a life event type’s published version, saved on a life event, read back from `field_values` with `valuable_type` = `LifeEvent::class`)
- [X] T012 Create `app/Actions/CreateLifeEvent.php` per research R2: class docblock of one sentence; promoted readonly `User $user, Vault $vault, Contact $contact, LifeEventType $lifeEventType, CarbonImmutable $happenedOn, ?string $title = null, ?string $narrative = null, ?string $location = null, ?int $durationInMinutes = null, array $participants = [] (Contact models), array $values = [] (keyed by field id, uploads included, as `SetFieldValues` takes them)`; `execute(): LifeEvent` validates (vault and type in the user’s account, contact and every participant in `$vault` else `ModelNotFoundException`; an inactive type else `LifeEventTypeIsNotAvailable` with `__('That life event type can no longer be chosen.')`), then in `DB::transaction` creates the row on `structure->publishedVersion` with `created_from` contact, `created_by_user_id`, `participant_count`; creates participants (the page’s contact at position 0, the others in the order given, duplicates removed); increments the version’s `record_count`; calls `SetFieldValues` once per row of the version’s `assembly` with that section’s values; increments each participant’s `life_event_count` and sets `last_life_event_at` to the later of the stored value and `happenedOn`; never touches `last_interaction_at`; then dispatches `LogUserAction` with `LifeEventCreation`, parameters `type` and `name`, `loggable` the life event, on the `low` queue
- [X] T013 Create `tests/Unit/Actions/CreateLifeEventTest.php` (`Queue::fake()`) proving: the row and participants written with `assertDatabaseHas()`; the structure version it names and its `record_count`; custom values saved on the life event; counts moved for every participant; an older date leaving a later `last_life_event_at` alone; `last_interaction_at` untouched; the log pushed on `low`; `ModelNotFoundException` for a type of another account and for a participant of another vault; `LifeEventTypeIsNotAvailable` for a deactivated type

**Checkpoint**: `php artisan test --compact tests/Unit/Actions/CreateLifeEventTest.php tests/Unit/Actions/SetFieldValuesTest.php tests/Unit/Models/LifeEventTest.php` passes.

## Phase 3: User Story 3 - Read what happened with somebody (P1)

**Goal**: the Overview’s History becomes the Timeline (research R8).

**Independent Test**: seed life events for Jim over two years with factories; open his Overview; read the years, order and lines.

- [X] T014 [US3] Create `app/ViewModels/Vaults/ContactTimelineBlock.php` (`Vault $vault, Contact $contact, int $page = 1, ?int $justAddedId = null`): `years()` returns `array<int, array{year: int, countLabel: string, showsHeading: bool, entries: array<int, array{id: int, icon: string, title: string, date: string, narrative: string|null, withLine: string|null, isJustAdded: bool}>}>` from the contact’s life events `orderByDesc('happened_on')->orderByDesc('id')->simplePaginate(30, pageName: 'timelinePage', page: $page)` (`contacts.show` already has a `{page?}` segment) with `lifeEventType` and `participants.participant` eager loaded; `countLabel` via `trans_choice(':count entry|:count entries', …)` from one `whereBetween('happened_on', …)` count per year; `showsHeading` false when the entry before the page shares the year; icon through `Section::ICONS` with `Section::DEFAULT_ICON`; `date` as the localized day and month; `withLine` `__('With :names', …)` of the other participants’ first names (name fallback); plus `nextPageUrl(): ?string` and `isEmpty(): bool`
- [X] T015 [US3] Create `tests/Unit/ViewModels/Vaults/ContactTimelineBlockTest.php` proving: years newest first and dates newest first within one; same day ordered by latest recorded; a life event Jim is not in left out; the year count covers entries beyond the page; a second page hides the heading of a year the first page ended in; the title falls back to the type’s name, kept for a deactivated type; the with line names the others and not Jim; `isJustAdded` on the flashed id
- [X] T016 [US3] In `app/ViewModels/Vaults/ContactViewModel.php`, add `timeline(): ContactTimelineBlock` (`once()`, page from `request()->integer('timelinePage', 1)`, just added id from `session('lifeEventJustAdded')`) and delete `history()` and `historyNote()` (no test asserts them today)
- [X] T017 [US3] Create `resources/views/app/vaults/contacts/_timeline.blade.php` (first line comment and `@var` block per code style): `<section id="contact-timeline" x-merge="replace">` with the "Timeline" heading, one heading and `<ol id="contact-timeline-rows" x-merge="append">` per year, each entry a rail line (outlined mark with `x-icon`, title, "· day month", "Just added" badge, narrative and with line underneath), and a footer `id="contact-timeline-pagination"` with the Load more link targeting `contact-timeline-rows contact-timeline-pagination`; in `resources/views/app/vaults/contacts/show.blade.php`, replace the History section with `@include('app.vaults.contacts._timeline', ['timeline' => $viewModel->timeline()])`
- [X] T018 [US3] In `tests/Feature/Controllers/App/Vaults/ContactControllerTest.php`, make the Overview test assert "Timeline" and a recorded life event’s title

**Checkpoint**: Jim’s Overview reads his life events by year.

## Phase 4: User Story 1 - Record a life event from somebody’s page (P1) MVP

**Goal**: the two step panel records through `CreateLifeEvent` (research R4 to R7, contract).

**Independent Test**: record a Move with Pam from Jim’s page; it tops both timelines and both counts move.

- [X] T019 [US1] Create `app/ViewModels/Vaults/LifeEventFormBlock.php` (`User $user, Vault $vault, Contact $contact`): `categories()` returning active categories in `position` order, each `{id, name, typeCountLabel, types: [{id, name, categoryName, icon, fieldCountLabel, fieldsUrl}]}` of active types in position order, the field count from `structure.publishedVersion.assembly.sectionVersion.fields` (top level, `hasAnInput()`) eager loaded, labels `__('No extra fields')` when the count is zero, else `trans_choice(':count field|:count fields', …)` (code style, phrases that show a number); `settingsUrl()` to `settings.lifeEventTypes.index`; `candidatesUrl()`; `opensAtDetails(): bool` and `oldType(): ?LifeEventType` from `old('life_event_type_id')`; `oldParticipants()` re-read in the vault; `fieldsOf(LifeEventType)` returning the published version’s top level fields in assembly then position order with `rawValue` null and `rows` []; `projection()` for the script (no translated string in a script, as `RelationshipFormViewModel::projection()` does)
- [X] T020 [US1] Create `tests/Unit/ViewModels/Vaults/LifeEventFormBlockTest.php` proving: inactive categories and types left out; category and type order; the field counts ("No extra fields", "1 field", "3 fields"); the old type and people restored after an error; a participant of another vault not restored
- [X] T021 [US1] Create `app/Http/Controllers/App/Vaults/ContactLifeEventController.php` with `create()`: `abort_unless($contact->isReachableThrough(...), 404)`; inline validation per the contract’s POST table plus `FieldValueRules::for()` over the chosen type’s published fields under `fields.*`; find the type with `LifeEventType::query()->where('account_id', …)->findOrFail()`, re-scope `participants` with `Contact::where('vault_id')`; call `CreateLifeEvent`; catch `LifeEventTypeIsNotAvailable` to `life_event_type_id` and the engine’s `FieldIsRequired`/`NotEnoughStorageLeft` to `fields`, redirecting back `withInput()->withErrors()`; on success redirect to `contacts.show` with `status` `__(':type, :date, is on the timeline of :names.')`, the first names joined with `__(':names and :last')` when there are two or more and flash `lifeEventJustAdded`
- [X] T022 [P] [US1] Create `app/Http/Controllers/App/Vaults/ContactLifeEventFieldController.php` (`index()`, returns `_life-event-fields` for the type, 404 for an unreachable contact) and `app/Http/Controllers/App/Vaults/ContactLifeEventCandidateController.php` (`index()`, returns `_life-event-candidates` with up to 25 contacts of the vault from `matchingSearch($query, $user->locale)`, excluding the page’s contact and `except[]`, ordered by `sort_name`)
- [X] T023 [US1] In `routes/web.php`, before `contacts.show`, add one line each: `Route::post(…/life-events)` named `contacts.lifeEvents.create`, `Route::get(…/life-events/types/{lifeEventType}/fields)` named `contacts.lifeEventFields.index`, `Route::get(…/life-events/candidates)` named `contacts.lifeEventCandidates.index`
- [X] T024 [US1] Create `resources/js/life-event-panel.js` (`Alpine.data('lifeEventPanel', …)`, registered in `resources/js/app.js`): `open`, `step` ('pick' or 'details'), `category`, `query`, `type`, `changing`, `people`, `openPieces`; `pick(type)` sets the hidden input and `$ajax(fieldsUrl, { target: 'life-event-custom-fields' })`; `search()` debounced in markup, calling the candidates URL with `except[]`; `choose(id, name)` and `remove(id)`; `changeType()` back to pick with `changing` true; `cancel()` resets; finds its markup from the acted element with `closest('[data-life-event-panel]')` (components rule on `$el`)
- [X] T025 [US1] Create `resources/views/app/vaults/contacts/_life-event-panel.blade.php`, `_life-event-fields.blade.php` and `_life-event-candidates.blade.php` per the contract’s markup section: the opener "Add a life event" with `aria-expanded`/`aria-controls`; step 1 caption "Step 1 of 2 · Choose what happened", categories, types, "Search every type", counted result heading in `aria-live`, "No type matches “…”", the settings link; step 2 caption, the type with Change type, "When did it happen" (`type="date"`, today), "Who was there" with the fixed chip and removable ones, "Add someone", "Nobody else matches.", "Details" with "+" chips for Summary, Description, Place and Duration then each custom field through `<x-field-input :reminder="null">`, Remove disabling the input, ":type asks for nothing beyond the date and the people." with the type’s settings link; footer Cancel and "Add to the timeline" (disabled without a date); `<x-form method="post" upload x-target="contact-timeline life-event-panel">`; include it above the timeline in `show.blade.php`
- [X] T026 [US1] Create `tests/Feature/Controllers/App/Vaults/ContactLifeEventControllerTest.php` proving: a post records and redirects with the status naming the type, date and people; `happened_on` required and `duration_in_minutes` 0 refused on their fields; a deactivated type returned on `life_event_type_id`; a participant of another vault not found; a contact reached through the wrong vault answers 404
- [X] T027 [P] [US1] Create `tests/Feature/Controllers/App/Vaults/ContactLifeEventFieldControllerTest.php` (renders a type’s field labels; 404 through the wrong vault) and `ContactLifeEventCandidateControllerTest.php` (finds Pam by "pa", leaves out Jim, `except[]` and another vault’s contact)
- [X] T028 [US1] In `ContactControllerTest.php`, assert the Overview renders "Add a life event" and `x-data="lifeEventPanel(`, and after a failed post renders step 2 with the old type’s name

**Checkpoint**: a life event is recorded from the page and shows on every participant’s timeline.

## Phase 5: User Story 2 - Change the type without losing the rest (P2)

- [X] T029 [US2] In `_life-event-panel.blade.php` and `life-event-panel.js`, when `changing`, mark the current type "Current" and show "The date and the people stay as they are." plus `__('If the new type does not ask for :fields, what you wrote there is dropped.')` built in the script from the `data-label` of the open custom fields holding a value, the wording passed in `projection()`; picking a type replaces `#life-event-custom-fields` and clears `openPieces` of custom keys only
- [X] T030 [US2] In `LifeEventFormBlockTest.php`, prove `projection()` carries the note’s wording, and in `ContactLifeEventControllerTest.php` that a posted `fields` value of a field the chosen type does not ask for is ignored

## Phase 6: User Story 4 - A new contact’s timeline (P3)

- [X] T031 [US4] In `ContactTimelineBlock.php`, add `creationEntry(): ?array` (`{title: __('You added :name'), date}`) returned only when there is no next page, and `isBlank()`; in `_timeline.blade.php`, draw the creation entry after the last year and, when blank, the dashed card "Nothing else yet", its line and "Add the first life event" opening the panel
- [X] T032 [US4] In `ContactTimelineBlockTest.php`, prove the creation entry only on the last page, never counted in a year, and `isBlank()` for a contact with no life event

## Phase 7: Polish

- [X] T033 [P] In `.specify/memory/constitution.md`, change the five mentions of activity (lines 113, 133, 134, 232, 235) to life event, bump to 2.4.1 with today’s Last Amended, and prepend a Sync Impact Report
- [X] T034 Run `php artisan monica:localize`, translate every reported string in every locale per `.claude/rules/translations.md` and the glossary, then `php artisan monica:localize --check`
- [X] T035 Run `vendor/bin/pint --dirty --format agent` and `vendor/bin/phpstan analyse --no-progress`, fixing every finding
- [X] T036 Run the full suite `php artisan test --compact` (shared components and the contact page changed) and fix what fails
- [ ] T037 Walk `quickstart.md` by hand, including keyboard only and VoiceOver, and mark this spec’s status

## Dependencies

- Phase 2 blocks everything; T004 before T005 before T006; T010 before T011 and T012.
- US3 (T014 to T018) before US1 (T019 to T028); US2 and US4 after US1; Polish last.
- Parallel: T001, T002, T003 together; T007 and T008 once T006 exists; T022 and T027 beside the main controller work; T033 any time.
