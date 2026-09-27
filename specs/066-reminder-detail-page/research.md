# Research: Reminder detail page redesign

## R1. Schedule sentence

- **Decision**: one translated phrase per cadence, built in `ReminderViewModel::schedule()`:
  `Every week on :weekday at :time · :zone.`, `Every month on day :day at :time · :zone.`,
  `Every year on :date at :time · :zone.`, `Once, on :date at :time · :zone.` An interval above one
  goes through `trans_choice()` (`{1}Every month on day :day…|Every :count months on day :day…`).
- **Rationale**: the design's "on the 28th" needs ordinals no translator can reach. "Day 28" reads in
  every locale. The yearly date is the day and month, ordered as the user's date format orders them.
- **Alternatives**: Carbon `Do` ordinals (English only in practice); reusing `ReminderWords::cadence()`
  (lower case, no day, no time).

## R2. Answering a date

- **Decision**: keep `_answers.blade.php` and `ReminderRow`. Which buttons show is already the
  `remember` and `forget` policy abilities, so FR-005 needs no new rule.
- **Rationale**: the policy owns the rule; the page only asks it.

## R3. History paging

- **Decision**: `history()` returns closed dates (answered or cancelled, date before today or answered)
  through `simplePaginate(4)`, shown in `x-list-panel` with `merge="append"` and its `nextPageUrl`.
- **Rationale**: the account log screen pages the same way (`settings/account/logs/index.blade.php`);
  the `?page=` query string is that precedent.
- **Alternatives**: loading all fifty and revealing four at a time in Alpine (sends everything).

## R4. Menu and deletion

- **Decision**: `x-actions-menu` holding a `x-modal.warning` trigger, the shape of
  `contacts/reminders/_menu.blade.php`. The dialog body counts answers with
  `trans_choice('[0,*]…:count…')` checked per locale.
- **Rationale**: the menu is a contract component; a second hand-built menu is what `components.md` forbids.

## R5. Where the page sits and where deletion lands

- **Decision**: breadcrumb People › contact › Reminders (`contacts.reminders.index`) › name, sidebar
  `current="contacts"`. `ReminderController::destroy` redirects to `contacts.reminders.index` when the
  reminder is about a contact, `reminders.index` otherwise. Route and URL are unchanged.
- **Alternatives**: moving the route under `contacts/{contact}` (breaks links in sent emails).

## R6. Stopped reminders

- **Decision**: `problem()` returns `{title, body}|null`, one pair per `ReminderInactiveReasonEnum`
  case, rendered with `x-notice tone="warning"`. The bodies reuse the wording of `quietBecause()`;
  titles are new. Upcoming rows are left out while stopped; overdue and today rows stay.
- **Rationale**: principle VIII (say why and keep the way back) is met by the notice itself.

## R7. Delivery details

- **Decision**: keep `ReminderHistory` as the source (journal and deliveries, reminder time zone
  already). Render it behind an Alpine disclosure (`x-data="{ open: false }"`, `aria-expanded`,
  `aria-controls`), the pattern of the "Read more" toggle in `contacts/_notes.blade.php`. Two files
  do not reach the component bar.
## R8. Derived reminders

- **Decision**: `sourceLine()` returns the field label and contact name; `editDateUrl()` is today's
  `editUrl()` branch for derived reminders. `editUrl()` returns null for a derived reminder, which
  hides Edit (decided in the spec).
