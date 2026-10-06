# Research: Vault dashboard redesign

## R1. Recording that a contact was opened (FR-005)

**Decision**: a queued job `RecordContactVisit`, dispatched on `low` wherever one contact is consulted
(`ContactController@show`, `Api\ContactController@show`, the MCP tools `GetContact` and `GetMeContact`), writes
`contacts.last_consulted_at` with a plain query, as `RecordVaultVisit` does for `vaults.last_visited_at`.

**Rationale**: the column already exists and nothing writes it. Opening a page is a read, so the job
must not bump `updated_at`, which the API exposes and the list sorts by.

**Rejected**: writing in the view model (a read with a side effect); a visits table (one timestamp is all
the screen needs); counting listings (a sync reading every page of contacts would fill the list).

## R2. Which dates need attention (FR-002)

**Decision**: extract the row source of `VaultRemindersViewModel::rows()` into
`App\ViewModels\Vaults\VaultDeadlines`: the occurrences awaiting an answer up to today, plus each active
rule's next deadline from `NextReminderDeadlines`, as `ReminderRow`s. The reminders screen and the
dashboard both read it; the dashboard keeps the rows due on or before today plus seven days.

**Rationale**: which dates are waiting is a business rule. Two copies of it would drift, and the
dashboard must show what the reminders screen shows.

**Rejected**: a second query in the dashboard (drift); calling `VaultRemindersViewModel` from the
dashboard (one view model holding another screen's view model).

## R3. Bands

**Decision**: `ReminderOccurrenceCategoryEnum::for()` decides the band, as on the reminders screen. The
third band is titled "Next seven days" here, because every row in it is within seven days.

## R4. Answering from the dashboard (FR-003)

**Decision**: include `app.vaults.reminders._answers` unchanged. Its forms post to
`ReminderOccurrenceAnswerController@update`, which redirects `back()` with the flash.

**Rationale**: the contact page answers the same way. The attention list is the first block on the
screen, so the reload keeps the reader where they were, and the flash announces the answer.

**Rejected**: an Alpine Ajax swap of the block (the flash would not be shown, and nothing else on the
site answers that way); renaming the answers to "Remembered" and "Forgot" (two wordings for one action).

## R5. Life events link to a contact (FR-006)

**Decision**: a life event has no page of its own. Its row links to the timeline of its first listed
participant who is not partial, and is plain text when there is none.

**Rationale**: `contacts.lifeEvents.edit` opens an edit form, which is not what reading a list asks for.

## R6. Dates and figures (FR-008)

- Life event dates use `DateFormatEnum::write()` with the reader's format, as `ContactTimelineBlock` does.
- The recently viewed day reuses the wording of `ReminderRow::relativeDay()` ("today", "yesterday") and
  falls back to the date written in the reader's format after that, as the design shows.
- Figures are counted phrases through `trans_choice()`; "life events in the last three months" counts
  `happened_on` from three months before the reader's today up to today.

## R7. Icons, not emoji (FR-011)

Headings use `x-icon`: `bell` (attention), `star` (favourites, as on the contact page), `clock`
(recently viewed), `timeline` (life events). Bands: `clock` overdue, `sun` today, `calendar` next seven
days, each also named in text. Life event tiles use the type's icon and `VaultColourEnum::tintFor()` its colour, exactly as
the timeline does. Reminder tiles use `reminder`.

## R8. API (FR-010)

`ContactResource` gains `last_consulted_at` as a timestamp next to `last_interaction_at`, documented in
`resources/docs/api/06-contacts.php`. It is read only: no endpoint writes it.
