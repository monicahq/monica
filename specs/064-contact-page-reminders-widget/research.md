# Research: Reminders widget on the contact page

## R1. Where the rows come from

- **Decision**: a new block, `ContactDeadlinesBlock`, beside `ImmediateFamilyBlock`, built on
  `ContactRemindersViewModel` with the All filter. It reads that view model's `groups()` for the
  rows and `filters()` for the counts, and drops History.
- **Rationale**: SC-002 asks the panel and the screen to agree. One query path makes that true by
  construction, and the categories stay 063's.
- **Alternatives**: a second query in the block (two copies of the rules to keep in step).
- **Name**: `ContactReminderBlock` already means the panel under a date field, so this block is named
  after what it lists, deadlines.

## R2. Empty wordings per filter

- **Decision**: `ContactRemindersViewModel::emptyTitle()` and `emptyLine()` take an optional filter,
  defaulting to the current one. The block asks for each of the four.
- **Rationale**: the panel shows the screen's wording (spec Assumptions) without copying the strings.

## R3. Switching filters

- **Decision**: in the browser, with Alpine. Every row is rendered once with its category, and
  `x-show` keeps the ones the selected filter shows. The counts are the server's.
- **Rationale**: FR-002 renders every row anyway, so the browser already holds what each filter
  needs; no query string on the contact page, no controller change, no round trip.
- **Alternatives**: links with `?reminders=overdue` and `x-target` (a query string the code style
  avoids, and a controller argument for a view concern).
- **Markup**: `x-tabs` and `x-tabs.item` as buttons, `x-bind:aria-selected` over the item's static
  value, and the rows in a `role="tabpanel"` labelled by the selected tab.

## R4. Answers and refusals

- **Decision**: reuse `app.vaults.reminders._answers`. `ReminderOccurrenceAnswerController` already
  answers with `back()` and flashes `error` on refusal, which `x-toaster` shows.
- **Rationale**: FR-004 holds with no server change.

## R5. The row menu

- **Decision**: move the menu out of `contacts/reminders/_row.blade.php` into
  `contacts/reminders/_menu.blade.php`, included by that row and by the panel.
- **Rationale**: the menu carries a contract (the `x-actions-menu` state, the modal trigger's focus
  hand-off, the warning copy). `components.md` extracts such markup at its second file.

## R6. Where a deletion returns

- **Decision**: `ContactReminderController::destroy` redirects `back()` instead of to the Reminders
  screen.
- **Rationale**: FR-007. Every caller of `contacts.reminders.destroy` is a list (the contact's screen,
  the vault's page, now the panel), so going back never lands on the deleted reminder.

## R7. Layout from the design

| Design | Application |
| --- | --- |
| Panel with icon and title | `x-panel icon="reminder" :flush="true"` |
| `Tabs` with counts | `x-tabs` / `x-tabs.item :count` |
| `EmptyState` | `x-empty-state` with the `reminder` icon |
| `Badge tone="danger"` | `x-badge tone="danger"` |
| Answers under the text, menu top right | the panel's own row markup; the screen's row keeps its layout |
| Foot link | `x-panel` `footer` slot |
