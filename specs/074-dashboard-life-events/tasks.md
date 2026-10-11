# Tasks: Life events on the vault dashboard

**Input**: [plan.md](plan.md), [spec.md](spec.md), [research.md](research.md)

**Tests**: required by constitution principle III and listed in the plan. Each story writes its tests first.

## Phase 1: Foundational

- [X] T001 Add the case `Dashboard = 'dashboard'` to app/Enums/LifeEventSourceEnum.php (research R7)
- [X] T002 Add `it_records_where_the_life_event_was_written` to tests/Unit/Actions/CreateLifeEventTest.php: `createdFrom: LifeEventSourceEnum::Dashboard` stores `created_from` `dashboard`, and the existing tests still store `contact` without the argument
- [X] T003 In app/Actions/CreateLifeEvent.php, add the last constructor parameter `private readonly LifeEventSourceEnum $createdFrom = LifeEventSourceEnum::Contact`, write it in `create()`, and say in the class docblock that it is recorded from a contact's page or from the vault dashboard
- [X] T004 Create app/Http/Requests/SaveLifeEventRequest.php (`authorize()` true): move `rules()`, `typeFrom()` as `lifeEventType()`, `valuesFrom()` as `values(?StructureVersion $version)`, and `participantsFrom()` as `participants(Contact $contact)` out of app/Http/Controllers/App/Vaults/ContactLifeEventController.php, reading the vault from `$this->route('vault')`; when the route carries `lifeEvent`, `rules()` adds `kept_files` and `kept_files.*`, and `#[Override] getRedirectUrl()` returns `contacts.lifeEvents.edit` for that contact and life event (research R3)
- [X] T005 Move `sentenceParts()` from app/Http/Controllers/App/Vaults/ContactLifeEventController.php to app/Models/LifeEvent.php as `confirmationParts(User $reader): array` with its `@return array{type: string, date: string, names: string}`, reading `$reader->date_format`; then type the controller's `create()` and `update()` with `SaveLifeEventRequest`, drop the update's `try`/`catch` on `ValidationException` (the request's `getRedirectUrl()` replaces it), call `$lifeEvent->confirmationParts($request->user())` in `create()`, `update()` and `destroy()`, and delete the moved private methods
- [X] T006 Add `it_words_the_confirmation_for_the_reader` to tests/Unit/Models/LifeEventTest.php (type, date in the reader's format, "Michael and Dwight"); run it, tests/Feature/Controllers/App/Vaults/ContactLifeEventControllerTest.php unchanged and tests/Unit/Actions/CreateLifeEventTest.php, and confirm they pass

## Phase 2: User Story 1, write down what happened to me from the dashboard (P1)

Independent test: as Michael, add a life event from the dashboard and find it on the timeline of his Me contact.

- [X] T007 [P] [US1] Create tests/Feature/Controllers/App/Vaults/VaultLifeEventControllerTest.php: `new` answers 200 with "Add a life event" and the panel's `x-data="lifeEventPanel(`; `create` with Michael's Me contact as `contact_id` stores `created_from` `dashboard`, redirects to `vaults.show` with the status ":type, :date, is on the timeline of Michael." and `lifeEventJustAdded`; with Dwight in `participants[]` both take part; a `contact_id` of another vault of the same account answers 404; a missing `contact_id` returns an error on `contact_id`; a deactivated type returns back with an error on `life_event_type_id` and the input
- [X] T008 [P] [US1] Add to tests/Unit/ViewModels/Vaults/LifeEventFormBlockTest.php: with `choosesSubject: true`, `action()` is `vaults.lifeEvents.create`, `projection()` carries `newUrl` `vaults.lifeEvents.new` and `candidatesUrl` `vaults.lifeEventCandidates.index`, and `subject` is the contact, or the contact of `old('contact_id')` in the vault when one is flashed; without it, the URLs stay the contact's and `subject` is the contact
- [X] T009 [P] [US1] Add to tests/Unit/ViewModels/Vaults/VaultDashboardViewModelTest.php: `lifeEventForm()` is built on the vault's Me contact with a choosable subject; a vault without a Me contact gives `null`
- [X] T010 [US1] In app/ViewModels/Vaults/LifeEventFormBlock.php, add `private readonly bool $choosesSubject = false`; when true, `action()`/`createUrl()` give `vaults.lifeEvents.create`, `newUrl()` `vaults.lifeEvents.new`, `candidatesUrl()` `vaults.lifeEventCandidates.index`; add `subject()` (`old('contact_id')` resolved in the vault, else the contact) and put `subject` `{id, name}`, `choosesSubject` and the caption `who` "Choose who it happened to" (the `pick` and `details` captions stay as on the contact page) into `projection()`; `oldParticipants()` excludes the subject rather than the contact
- [X] T011 [US1] In app/ViewModels/Vaults/VaultDashboardViewModel.php, add the constructor parameters `bool $opensLifeEventPanel = false`, `?int $lifeEventJustAddedId = null`, a `meContact()` (`is_me` true in the vault, `once`), and `lifeEventForm(): ?LifeEventFormBlock` built on it with `isAskedFor: $this->opensLifeEventPanel, choosesSubject: true`
- [X] T012 [US1] Create app/Http/Controllers/App/Vaults/VaultLifeEventController.php: `new(Request, Vault)` renders `app.vaults.show` with `opensLifeEventPanel: true`; `create(SaveLifeEventRequest, Vault)` validates `contact_id` `['required', 'integer']`, finds it with `Contact::query()->where('vault_id', $vault->id)->findOrFail()` inside a try/catch that aborts 404, runs `CreateLifeEvent` with `createdFrom: LifeEventSourceEnum::Dashboard` and the request's participants, catches the same exceptions as the contact controller with `back()->withInput()->withErrors()`, and redirects to `vaults.show` with the contact page's status and `lifeEventJustAdded`
- [X] T013 [US1] In routes/web.php, next to `vaults.show`, add `GET vaults/{vault}/life-events/new` named `vaults.lifeEvents.new` and `POST vaults/{vault}/life-events` named `vaults.lifeEvents.create`, both on `VaultLifeEventController`
- [X] T014 [US1] In app/Http/Controllers/App/Vaults/VaultController.php `show()`, pass `lifeEventJustAddedId: $request->session()->get('lifeEventJustAdded')` to the view model
- [X] T015 [US1] In resources/views/app/vaults/contacts/_life-event-panel.blade.php, when `$form->choosesSubject()`: a hidden `contact_id` bound to `subject.id`, a line "For :name" with a "Change" button on the `pick` step (spec story 3 fills the step it opens); the first "Who was there" chip reads `x-text="subject.name"` on both screens
- [X] T016 [US1] In resources/views/app/vaults/show.blade.php, when `lifeEventForm()` is not null, open the main column with a `<section id="contact-timeline" x-merge="replace" x-data="lifeEventPanel(...)" data-life-event-panel>` holding the 1b row ("Something happened?", "A call, a move, a new job. Write it down and it goes on their timeline.", "Add a life event" with `x-ref="addButton"`, `x-on:click="show()"`, `aria-expanded`, `aria-controls`), hidden while `open`, and the panel include when `isRendered()`; reuse `x-panel` and `x-button.secondary`; first line comment and `@var` block untouched
- [X] T017 [US1] In resources/js/life-event-panel.js, add `subject: projection.subject`, reset it to the projection's subject in `cancel()`, and add `subject.id` to `except[]` in `searchPeople()`
- [X] T018 [US1] Run T007 to T009 and tests/Feature/Controllers/App/Vaults/VaultControllerTest.php, and confirm they pass

## Phase 3: User Story 2, my own timeline under the box (P1)

Independent test: give Michael's Me contact three life events and one only Jim takes part in, then read the block.

- [X] T019 [P] [US2] Add to tests/Unit/ViewModels/Vaults/VaultDashboardViewModelTest.php: `timeline()` lists the Me contact's life events and not Jim's alone, ten of twelve at most, marks `lifeEventJustAddedId` as just added, and is `null` without a Me contact; `fullTimelineUrl()` is `contacts.show` of the Me contact
- [X] T020 [P] [US2] Add `it_links_every_entry_to_the_contact_page_when_read_only` to tests/Unit/ViewModels/Vaults/ContactTimelineBlockTest.php: `isReadOnly()` is true and `editUrl` is `contacts.lifeEvents.edit` of the contact; add `it_lists_as_many_entries_as_asked`: `perPage: 10` over twelve life events gives ten entries and a null `nextPageUrl()`
- [X] T021 [P] [US2] Add to tests/Feature/Controllers/App/Vaults/VaultControllerTest.php `it_shows_my_timeline_without_deleting_from_it`: the dashboard shows the Me contact's life event title, a "View full timeline" link to `contacts.show` of the Me contact, and no "Delete life event" nor "Load more"
- [X] T022 [US2] In app/ViewModels/Vaults/ContactTimelineBlock.php, add `private readonly int $perPage = self::PER_PAGE` used by `paginator()` and the year count of `yearThePreviousPageEndedIn()`, then `private readonly bool $isReadOnly = false` and a public `isReadOnly()`; `nextPageUrl()` returns null when read only
- [X] T023 [US2] In app/ViewModels/Vaults/VaultDashboardViewModel.php, add `private const int TIMELINE_SHOWN = 10`, `timeline(): ?ContactTimelineBlock` on the Me contact with `perPage: self::TIMELINE_SHOWN`, `justAddedId`, `isReadOnly: true`, and `fullTimelineUrl(): ?string` giving `contacts.show` of the Me contact
- [X] T024 [US2] In resources/views/app/vaults/contacts/_timeline.blade.php, when `$timeline->isReadOnly()`: no Delete button, no deletion modal, and Edit is `x-button.ghost :href` without `x-target`; `#contact-timeline-pagination` stays empty, since `nextPageUrl()` is null
- [X] T025 [US2] In resources/views/app/vaults/show.blade.php, inside the section of T016 and below the panel, add a heading "Your timeline" and `@include('app.vaults.contacts._timeline', ['timeline' => $viewModel->timeline(), 'form' => $viewModel->lifeEventForm()])`, then, unless the timeline holds nothing, a "View full timeline" `x-link :href="$viewModel->fullTimelineUrl()"` at the bottom
- [X] T026 [US2] In app/ViewModels/Vaults/VaultDashboardViewModel.php or resources/views/app/vaults/show.blade.php wherever it is written, change the empty line of "Recent life events" to "What happens with the people in this vault, once you write it down."
- [X] T027 [US2] Run T019 to T021 and tests/Unit/ViewModels/Vaults/ContactTimelineBlockTest.php, and confirm they pass

## Phase 4: User Story 3, it happened to somebody else (P2)

Independent test: change the subject to Pam, save, and find the life event on Pam's timeline only.

- [X] T028 [P] [US3] Create tests/Feature/Controllers/App/Vaults/VaultLifeEventCandidateControllerTest.php: lists the Me contact and Pam, leaves out `except[]` ids and contacts of another vault, filters by `query`
- [X] T029 [P] [US3] Add to tests/Feature/Controllers/App/Vaults/VaultLifeEventControllerTest.php: with Pam as `contact_id`, Pam is participant at position 0 and the Me contact takes no part; a refused submission flashes `contact_id` back
- [X] T030 [US3] Create app/Http/Controllers/App/Vaults/VaultLifeEventCandidateController.php `index(Request, Vault)` mirroring `ContactLifeEventCandidateController` without excluding a contact, rendering `app.vaults.contacts._life-event-candidates`; add `GET vaults/{vault}/life-events/candidates` named `vaults.lifeEventCandidates.index` in routes/web.php (research R4)
- [X] T031 [US3] In resources/js/life-event-panel.js, `changeSubject()` sets `step = 'who'`, runs `searchPeople()` with an empty query and focuses `life-event-subject-search`; `choose(id, name)` in the `who` step sets `subject`, removes that id from `people`, sets `step = 'pick'` and focuses `life-event-type-search` (research R5)
- [X] T032 [US3] In resources/views/app/vaults/contacts/_life-event-panel.blade.php, add the `who` step: caption "Choose who", search input `life-event-subject-search` labelled "Search the people in this vault", the `#life-event-candidates` container, and a footer "Not in this vault yet? Add somebody first." linking to `contacts.new`; wire "Change" of T015 to `changeSubject()`; the list is `#life-event-subjects`, filled by the candidates endpoint with `list=life-event-subjects` so it never duplicates `#life-event-candidates`; the submit label stays "Add to the timeline"
- [X] T033 [US3] Run T028 and T029 and confirm they pass

## Phase 5: Polish

- [X] T034 Run `php artisan monica:localize`, translate every new string in every configured locale following .claude/rules/translations.md and its glossary, then `php artisan monica:localize --check`
- [X] T035 Run `php artisan test --compact` (full suite) and fix every failure caused by the shared panel, its script or the Form Request
- [X] T036 Run `vendor/bin/pint --dirty --format agent` and `vendor/bin/phpstan analyse --no-progress`, and fix what they report
- [ ] T037 Run `bun run build`, open the dashboard of a seeded vault, and walk the three stories with the keyboard alone: row, type, details, save; Change, who, save; Escape returns focus to "Add a life event"

## Phase 6: Design 2a

- [X] T038 In resources/views/app/vaults/show.blade.php, replace the 1b row and the separate "Needs your attention" with the 2a switch: a `role="tablist"` labelled "What to show", tabs `dashboard-tab-timeline` and `dashboard-tab-attention` with icon, label and count, left and right arrows moving between them, "Add a life event" beside it on the timeline and "All reminders" on the other, two `role="tabpanel"` (research R10)
- [X] T039 Add `timelineCount()` (the Me contact's `life_event_count`) to app/ViewModels/Vaults/VaultDashboardViewModel.php
- [X] T040 Move the attention panel of resources/views/app/vaults/_attention.blade.php into resources/views/app/vaults/_attention-dates.blade.php with a `showsAllReminders` flag; the tab includes it with `false`, `_attention` with `true` inside its section
- [X] T041 Update `it_shows_my_timeline_without_deleting_from_it` in tests/Feature/Controllers/App/Vaults/VaultControllerTest.php to assert the tablist, both `aria-controls` and the empty state "Nothing due this week"; translate "What to show" in every locale

## Dependencies

- T001 before T003. T004 before T005 and T012. Phase 1 before every story.
- US1: T010 and T011 before T012, T015, T016. T013 before T007 passes. US2 needs T016's section. US3 needs T015 and T017.
- Parallel: T007 to T009; T019 to T021; T028 and T029.

## Implementation strategy

MVP is Phase 1 plus US1 and US2, since the request names both. US3 adds the way out of the default subject.
