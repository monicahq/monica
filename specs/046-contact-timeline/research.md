# Research: Contact timeline and adding a life event

## R1. The record follows the product specification as written

- **Decision**: `life_events` and `life_event_participants` carry every column of `spec/models/036`
  and `037`, participants polymorphic as specified. This feature writes only contacts, day precision
  and `created_from = contact`. Two enums: `DatePrecisionEnum`, `LifeEventSourceEnum`.
- **Rationale**: FR-001, and migrations are edited in place until production, so a column added now
  costs nothing and a column skipped would be a second migration pass later.
- **Alternatives**: the subset this screen fills (the API and journal features would reopen the table).

## R2. One action records the whole life event

- **Decision**: `CreateLifeEvent(user, vault, contact, lifeEventType, happenedOn, title, narrative,
  location, durationInMinutes, participants, values)` validates, then in one transaction creates the
  row on `lifeEventType->structure->publishedVersion`, the participants (the page’s contact first),
  increments the version’s `record_count`, calls `SetFieldValues` once per section of the version’s
  `assembly`, and updates each contact’s `life_event_count` and `last_life_event_at` (greater of the
  two). It logs `UserActionEnum::LifeEventCreation` on the low queue.
- **Refusals**: another account or vault → `ModelNotFoundException`; a deactivated type →
  `LifeEventTypeIsNotAvailable` (translated), as `RelationshipTypeIsNotAvailable` does.
- **Not written**: `last_interaction_at`, per the clarification. `reminders` is never passed, so
  `SetFieldValues` creates none.
- **Alternatives**: an `AddLifeEventParticipant` action (one caller; counts move in one transaction).

## R3. The field engine accepts a third owner

- **Decision**: widen `Contact|Entity` to `Contact|Entity|LifeEvent` in `SetFieldValues`,
  `SetFieldValue` and `CreateFieldValueRow` (repeaters). `LifeEvent` gives the duck typed surface they
  read: `vault`, `structure_version_id`, `fieldValues()`, `fieldValueRows()`, timestamps, and a
  `name` accessor (title, else the type’s name) for the `FieldValueSet` log.
- **Consequence**: each value written also journals `FieldValueSet`, as on a contact. FR-005 is read
  as "a creation entry", not "only one entry".
- **Alternatives**: an owner interface (three call sites); a morph map (every morph row migrated).

## R4. The panel is server rendered, the steps are Alpine state

- **Decision**: the Overview renders the whole panel hidden (`x-data="lifeEventPanel(...)"`). Step 1
  lists every active category and type; switching category and searching filter the rendered rows
  with `x-show`, with no request. Picking a type sets the hidden `life_event_type_id` and fetches only
  the custom fields block with `$ajax` into `#life-event-custom-fields`.
- **Why**: date, people, title, narrative, place and duration live outside that block, so Change type
  keeps them for free and drops every custom value (no field is shared between two types).
- **Inputs**: `<x-field-input>` per field, `reminder: null`; names `fields[{id}]` so
  `FieldValueRules::for()` validates them. A closed field is `disabled`, so it is not posted.
- **Alternatives**: one request per step with carried hidden inputs (files cannot be carried).

## R5. People are found the way the relationship form finds them

- **Decision**: `ContactLifeEventCandidateController@index` returns `#life-event-candidates`, from
  `Contact` in the vault, `matchingSearch()`, excluding the ids already listed, limit 25; the script
  calls it with `$ajax` after a 400 ms debounce, as `relationship-form.js` does. Chosen people post
  as `participants[]`; the controller re-scopes each with `Contact::where('vault_id')->findOrFail()`.

## R6. Errors reopen the panel at step 2

- **Decision**: validation or a refusal redirects back `withInput()->withErrors()`. The view model
  reads the old type and renders the panel open at step 2 with that type’s fields and the old people.
  A file chosen before the error must be chosen again, as on the section editor.

## R7. The confirmation is the toaster

- **Decision**: success redirects with `status` ("Move, 3 July 2026, is on the timeline of Jim and
  Pam.") to `<x-toaster>`, which already syncs over Alpine Ajax, and flashes `lifeEventJustAdded` for
  the "Just added" badge. The form targets `contact-timeline life-event-panel`.
- **Alternatives**: the design’s inline note (a second confirmation pattern for one screen).

## R8. The timeline pages without splitting a year

- **Decision**: `ContactTimelineBlock` (beside `ImmediateFamilyBlock`) queries life events joined on
  participants for the contact, `orderByDesc('happened_on')->orderByDesc('id')`, `simplePaginate(30)`,
  eager loading the type and participants. Year headings count with one `whereBetween` per year on
  the page (portable across SQLite, MySQL, PostgreSQL). A later page skips the heading of a year the
  previous page ended in, found by loading the one entry before the page. The creation entry follows
  the last page. It replaces `history()` and `historyNote()`.
- **Alternatives**: `GROUP BY YEAR()` (driver specific); loading every date (FR-011).

## R9. Principle VII is a patch

- **Decision**: the five mentions of activity in the constitution become life event; 2.4.1.
