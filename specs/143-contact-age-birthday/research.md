# Research: Contact age and upcoming birthday

## R1. One home for the 7 day window

**Decision**: `ReminderOccurrenceCategoryEnum::UpcomingDays = 7`, a constant on the enum whose
`Upcoming` case already means "a deadline after today". `VaultDashboardAttention` drops its private
`DAYS_AHEAD`, `VaultReminderDates::DefaultDays` becomes `= ReminderOccurrenceCategoryEnum::UpcomingDays`
(the API keeps the public name it validates against), and the contact page reads the enum.

**Rationale**: the window is what "upcoming" means. The enum is already imported by the dashboard and
the reminder dates, so nothing gains a dependency.

**Alternatives**: a config key (an operator could then make the contact page and the dashboard
disagree, which the clarification rules out); a constant on a new birthday class (the dashboard window
is about every deadline, not only birthdays, so it would live in the wrong place).

## R2. One implementation of "next birthday"

**Decision**: a new `App\Helpers\Birthday` built from a birthday `FieldValue` and the reader’s today.
It answers `age()`, `next()` and `daysUntilNext()`, all `null` when they cannot be known. The private
`nextAnniversary()` and `inYear()` of `VaultReminderDates` move into it and `VaultReminderDates` calls
it, so the 29 February rule exists once.

**Rationale**: the contact page, the API and the reminder dates must agree to the day (SC-004). Two
copies of the leap year rule is the defect principle II warns against.

**Alternatives**: methods on `FieldValue` (a field value holds every kind of value; birthday rules
would sit beside address and file logic); leaving `VaultReminderDates` alone (a second copy).

## R3. Which date parts must be known

**Decision**: an age needs the month and the year known; a countdown needs the month known. With the
year unknown there is a countdown but no age; with the month unknown there is neither.
`ContactAges::of()` gains `where('month_is_unknown', false)`, and computes through `Birthday`.

**Rationale**: `month_is_unknown` exists on `field_values`, and `VaultReminderDates` already excludes
it. `ContactAges` does not today, so a birthday recorded as "1978" alone shows an age worked out from a
placeholder day in the relationship and family blocks. That is a latent defect this feature would
otherwise copy onto the contact page.

## R4. Reading the birthday of one contact

**Decision**: a `birthday()` `MorphOne` relationship on `Contact`: attached, `field_key = birthday`,
`field_value_row_id` null. The contact page reads it; the API index eager loads `birthday.field`.

**Rationale**: the API resource is used by the index as well as the show; a query per row in the
resource would add an N+1 query to every contacts list. The conditions (key, top level row, attached)
are the same ones `ContactAges` uses, so the relation is the one place they are written for a single
contact.

**Alternatives**: `ContactAges::of([$id])` (returns an age only, not the next date); querying in
`ContactResource` (N+1).

## R5. Sensitive birthdays

**Decision**: the contact page shows the line whatever the field’s sensitivity, as it already shows
the field. `ContactResource` returns `null` for both values when `birthday.field.is_sensitive` and the
token lacks the `sensitive-fields` ability, through its existing `reachesSensitiveFields()`.

**Rationale**: the same rule `VaultReminderDates::ageOn()` applies; an age leaks the birthday year.

## R6. Wording and the date

**Decision**:

| State | Phrase | Counted by |
| --- | --- | --- |
| Age | `:count year old\|:count years old` (exists) | age |
| In 2 to 7 days, year known | `Turns :age in :count day, on :weekday :date\|Turns :age in :count days, on :weekday :date` | days |
| Tomorrow, year known | `Turns :count tomorrow\|Turns :count tomorrow` | age |
| Today, year known | `Turns :count today\|Turns :count today` | age |
| In 2 to 7 days, year unknown | `Birthday in :count day, on :weekday :date\|Birthday in :count days, on :weekday :date` | days |
| Tomorrow, year unknown | `Birthday tomorrow` | none |
| Today, year unknown | `Birthday today` | none |

The weekday is `isoFormat('dddd')` and the date `isoFormat('D MMMM')`, or `'MMMM D'` when the
reader’s date format starts with the month, in the app locale: the precedent of
`ReminderViewModel::scheduleLine()` for the yearly cadence.

**Rationale**: an age phrase goes through `trans_choice()` counted by the age, so a language that
inflects "turns 1" differently from "turns 37" can. English reads the same at every age, but the
rules forbid `[0,*]` unless the phrase never inflects in any language, which is untrue here. Two
identical English segments keep the choice for translators. The countdown carries two numbers and is
counted by days, the number most languages inflect the noun for; the age is a bare number there.

**Verified in implementation**: `php artisan monica:localize --check` accepts two identical English
segments, so the two phrases stay as written and `[0,*]` is not used.

## R7. Where the line sits and how it refreshes

**Decision**: the region with `id="contact-facts"` becomes a wrapper holding the birthday line, then
the existing list. Saving a section already targets `contact-facts` (`_section.blade.php`), so editing
the birthday redraws the line with no new target.

**Rationale**: design 1b puts the line between the name and the facts; a separate region would need a
new entry in `ContactViewModel::$drawable` and in every section form’s `x-target`.

## R8. API shape

**Decision**: two attributes on `ContactResource`: `age` (integer or null) and `next_birthday_on`
(`Y-m-d` string or null, in the token owner’s time zone), documented in the API docs portal. MCP gains
them through the API with no change of its own (`.claude/rules/mcp.md`).

**Rationale**: principle X. A day without a time is written `Y-m-d`, as `VaultReminderDateResource`
does; the existing unix timestamps describe instants, which a birthday is not.
