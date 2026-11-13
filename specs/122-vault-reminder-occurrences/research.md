# Research: Vault-wide reminder occurrences

## R1. One source for the dashboard and the API

- **Decision**: Move the body of `VaultDeadlines::rowsUntil()` into
  `App\Helpers\VaultReminderDates::until(Vault $vault, CarbonImmutable $today, CarbonImmutable $lastDay)`,
  returning the `ReminderOccurrence` collection. `rowsUntil()` keeps its signature and maps that
  collection to `ReminderRow`, exactly as now.
- **Rationale**: FR-007 asks for the same rows, not a second query. A view model must not be read
  outside Blade (CLAUDE.md, view models), so the API cannot call `VaultDeadlines` directly. Helpers
  already hold the per record equivalent (`ReminderDates`, `NextReminderDeadlines`).
- **Alternatives**: Calling `VaultDeadlines` from the controller (breaks the view model rule);
  writing a new query (drifts from the dashboard).

## R2. What the dashboard lists

Read from `VaultDeadlines::rowsUntil()`: every active occurrence of an active rule due by today and
unanswered, then the earliest active occurrence of each active rule after today and up to the last
day, generated or computed from the schedule when not generated yet. One upcoming row per rule.
Remindables are contacts, groups and entities alike: groups are included (spec constraint holds).

## R3. Default category and history

- **Decision**: No category returns overdue, today and upcoming (the dashboard set). History is
  `ReminderOccurrence::answeredInVault()`, paginated in the database, most recent answer first, the
  rule loaded with its trashed state so a deleted rule's answers stay listed.
- **Note**: the per contact endpoint returns every occurrence newest first without a category. The
  vault endpoint differs on purpose (FR-003); the docs say so.

## R4. Birthdays

- **Identification**: a birthday is a `field_values` row with `field_key = 'birthday'`
  (`DefaultFieldEnum::Birthday`) on a contact, attached, outside a repeated row, with a `value_date`
  and `month_is_unknown = false`. A birthday reminder is a rule whose `fieldValue` is such a row.
- **A birthday creates no reminder by itself**: `SetFieldValues::wantsAReminderOn()` reads the
  "Remind me about this date" box, false unless ticked.
- **Without a reminder**: the next anniversary from today in the user's timezone: the month and day
  in this year, or next year when already past. 29 February falls on 28 February in a year without
  it, the clamp `ReminderSchedule::onDayOf()` already applies. Kept when it falls on or before the
  last day of the horizon. Excluded when the field value carries an active reminder (FR-019).
  Contacts restricted to `Contact::listed()` (not archived, not partial).
- **Age**: `date year - birth year` when `year_is_unknown` is false, which is the age reached that
  day; the same column `ContactAges` reads.

## R5. Sensitive fields

- **Decision**: When the birthday field is `is_sensitive` and the key lacks the `sensitive-fields`
  ability, a birthday without a reminder is left out and no age is given. An occurrence of a reminder
  stays listed with its date, as the per contact endpoint already lists it.
- **Rationale**: Every API surface hides sensitive values from a key without the ability
  (`ContactSectionController`, `ContactCategoryController`); the MCP trait has
  `reachesSensitiveFields()` for the same decision.

## R6. Representation

- **Decision**: A new `VaultReminderDateResource`. For an occurrence it renders
  `ReminderOccurrenceResource` unchanged and adds `subject`, `age` and an `answer` link. For a birthday
  without a reminder it renders `type: birthday` with the contact, the date, the category and the age.
- **Rationale**: Constitution VI: the existing occurrence shape must not move. A mixed list told apart
  by `type` follows the JSON:API style the API already uses.
- **Alternatives**: Widening `ReminderOccurrenceResource` (changes two shipped endpoints); faking an
  unsaved occurrence for a bare birthday (no rule to hang it on).

## R7. Parameters

`category` (`overdue|today|upcoming|history`), `kind` (`all|birthdays`, default `all`), `days`
(integer 1 to 365, default 7, the dashboard's `DAYS_AHEAD`), `page`, `per_page` (same bounds as every
list). No new API key scope: reminders need none today.
