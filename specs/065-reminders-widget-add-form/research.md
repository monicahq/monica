# Research: Add a reminder from the Reminders widget

## R1. Which route the form posts to

- **Decision**: `contacts.reminders.create`, the route the Reminders screen's form posts to. The
  widget's form sends `return_to=contact`, validated with `Rule::in(['contact'])`; the controller then
  redirects to the overview, and to the Reminders screen otherwise. A refusal goes `back()`.
- **Rationale**: SC-002 by construction: one validation, one action, one set of refusals.
- **Alternatives**: comparing `url()->previous()` with the overview URL (shipped first, missed in the
  browser and sent the reader to the Reminders screen); a second controller (a second copy of the rules).

## R2. Sending and redrawing

- **Decision**: the form carries `x-target="contact-reminder-panel"`, the id on the widget's wrapper, with `x-merge="replace"` so Alpine restarts rather than morphing onto the old state. The Reminders screen already uses `contact-reminders`. Alpine
  Ajax follows the redirect and swaps the widget alone, whether it lands on the list or on the form
  with its errors. This is how `_life-event-panel` posts from the same page.

## R3. The form reopens after a refusal

- **Decision**: `ContactDeadlinesBlock::isAdding()` is `old('cadence') !== null`. The widget's Alpine
  state starts from it.
- **Rationale**: the old input only reaches the overview when the widget's form was sent from there
  (R1). `cadence` is a select, so it is never empty; `name` and `starts_on` can be, and empty strings
  are flashed as null. `LifeEventFormBlock` opens its panel the same way, from `old('life_event_type_id')`.

## R4. The error messages the design writes

- **Decision**: `create()` and `update()` pass messages to `validate()`: `name.required` "Say what the
  reminder is about.", `starts_on.required` "Pick a date.". The Reminders screen's form gets them too.
- **Rationale**: one wording per refusal (translations rule), and both are clearer than the defaults.

## R5. "Every [n] weeks" in every language

- **Decision**: the phrases `Every :count week|Every :count weeks` (month, year likewise) are handed to
  the browser with `CountedPhraseWordings::of()`. A small Alpine component,
  `resources/js/reminder-interval.js`, picks the wording with `Intl.PluralRules` and splits it at `:n`:
  what comes before is written before the input, what comes after is written after it.
- **Rationale**: the design puts the number between two words, and the word order and the plural
  differ by language ("7 tygodni", "7週間ごと"). A unit written next to a count in Blade would compare
  the count to one, which `code-style.md` forbids.
- **Alternatives**: a label "Every so many" above the input (loses the design's line).

## R6. Defaults and choices

- **Decision**: the block hands the view a `ContactReminderForm` for the time, the time zone, the
  zones offered, the maximum interval and the action URL. The repeat labels are the design's (Once,
  Weekly, Monthly, Yearly), written in the block.

## R7. Layout from the design

| Design | Application |
| --- | --- |
| Header with title and "About {name}" | a second `x-panel` with `title` and `subtitle`, shown while adding |
| `Input`, `Select`, required mark | `x-input`, `x-select`, `required` |
| Change / Done | `x-button.ghost size="small"`, `aria-expanded`, `aria-controls` |
| Help line | a muted line under the time; `x-help` is a tooltip, not this |
| Footer with Cancel and Add reminder | `x-panel` `footer` slot |
