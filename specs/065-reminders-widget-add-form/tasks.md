# Tasks: Add a reminder from the Reminders widget

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md). Tests are required by
constitution III.

## Phase 1: Foundational

- [X] T001 Add to `ContactDeadlinesBlock`: `canAdd()` (false when the contact `is_archived`), `isAdding()` returning `old('cadence') !== null` (research R3), `form(): ContactReminderForm` built with the block's user, vault and contact, memoised with `once()`, `repeats(): array<string, string>` keyed by `ReminderCadenceEnum` value with "Once", "Weekly", "Monthly", "Yearly", `aboutLine()` ("About :name" with the full name), and `intervalWordings(): array<string, array<string, string>>` keyed by the three recurring cadences, each `CountedPhraseWordings::of('Every :count week|Every :count weeks')` and the month and year equivalents, in app/ViewModels/Vaults/ContactDeadlinesBlock.php
- [X] T002 Test `canAdd`, `isAdding` with and without old input, `repeats`, `aboutLine` and that `intervalWordings` holds `:n` in every wording of every cadence, in tests/Unit/ViewModels/Vaults/ContactDeadlinesBlockTest.php
- [X] T003 [P] Create the Alpine component `reminderInterval(wordings)` with `parts(cadence, value)` returning `{ before, after }`: the wording for `Intl.PluralRules(document.documentElement.lang)` of the number (1 when empty or below 1), split at `:n`, in resources/js/reminder-interval.js, and register it as `Alpine.data('reminderInterval', ...)` in resources/js/app.js

## Phase 2: User Story 1, write a reminder without leaving the page (P1)

**Independent test**: a reminder submitted from the widget exists about the contact and the reader is back on the overview with it listed.

- [X] T004 [US1] In `create()`, redirect to `contacts.show` for this vault and contact when `url()->previous()` equals that URL, otherwise to `contacts.reminders.index` as today, both with the existing status, in app/Http/Controllers/App/Vaults/ContactReminderController.php
- [X] T005 [US1] Test a creation sent from the overview lands on the overview and one sent from the new form lands on the Reminders screen, in tests/Feature/Controllers/App/Vaults/ContactReminderControllerTest.php
- [X] T006 [US1] Give the wrapper `id="contact-reminder-panel"`, extend its state to `{ filter: 'all', adding: @js($deadlines->isAdding()) }`, show the list panel with `x-show="!adding"` and put `x-button.secondary size="small"` Add in its `action` slot when `canAdd()`, with `x-ref="addButton"`, `aria-controls="contact-reminder-form"`, `x-bind:aria-expanded`, and a click that sets `adding = true` then focuses `#contact-reminder-name` in `$nextTick`, in resources/views/app/vaults/contacts/_reminders.blade.php
- [X] T007 [US1] Add the form panel, `x-show="adding"` and `x-cloak`, `id="contact-reminder-form"`, titled "Add a reminder" with `aboutLine()` as subtitle: `x-form` posting to `form()->actionUrl()` with `x-target="contact-reminder-panel"`, `x-input` `name` ("What should we remind you about?", required, placeholder "Ask how the new job is going", maxlength 255), `x-input type="date"` `starts_on` ("Date", required), `x-select` `cadence` ("Repeat") from `repeats()` with `x-model`, and a `fieldset` shown and enabled when the cadence recurs holding the `interval` number input (min 1, max `form()->maximumInterval()`, value 1, `aria-label` "Every so many") between the `before` and `after` of `reminderInterval`, in resources/views/app/vaults/contacts/_reminders.blade.php
- [X] T008 [US1] Put Cancel (`x-button.secondary`, type button: reset the form, `adding = false`, focus `$refs.addButton` in `$nextTick`) and Add reminder (`x-button`, submit) in the form panel's `footer` slot, in resources/views/app/vaults/contacts/_reminders.blade.php
- [X] T009 [US1] Test the view: Add with `aria-controls="contact-reminder-form"`, the form's action and `x-target="contact-reminder-panel"`, the field names `name`, `starts_on`, `cadence`, `interval`, the Alpine hook `reminderInterval(`, and no Add for an archived contact, in tests/Feature/Views/Vaults/ContactRemindersPanelTest.php

## Phase 3: User Story 2, a form the server refuses (P1)

**Independent test**: an empty wording and an empty date each come back as an error inside the open form.

- [X] T010 [US2] Pass the messages `name.required` "Say what the reminder is about." and `starts_on.required` "Pick a date." to `validate()` in `create()` and `update()` (research R4) in app/Http/Controllers/App/Vaults/ContactReminderController.php
- [X] T011 [US2] Test both messages on a creation sent from the overview, that it redirects back to the overview with the input, and that nothing is written, in tests/Feature/Controllers/App/Vaults/ContactReminderControllerTest.php
- [X] T012 [US2] Show each field's error with `:error="$errors->get(...)"` and every value through `old()`, including `time`, `timezone` and `interval`, in resources/views/app/vaults/contacts/_reminders.blade.php
- [X] T013 [US2] Test that the overview rendered with old input and a `starts_on` error shows "Pick a date." and starts with `adding: true`, in tests/Feature/Views/Vaults/ContactRemindersPanelTest.php

## Phase 4: User Story 3, the notification time (P2)

**Independent test**: Change reveals the time and time zone fields and Done folds them away with the values kept.

- [X] T014 [US3] Add the time block under a top rule: "Notification time: {time} · {zone}" written with `x-text` from the two fields, `x-button.ghost size="small"` toggling `timeOpen` and reading Change or Done with `aria-expanded` and `aria-controls="contact-reminder-time"`, the `time` input ("Time", default `form()->time()`) and `timezone` select ("Time zone", from `form()->zones()`, default `form()->timezone()`) in `id="contact-reminder-time"` shown by `timeOpen`, open from the start when either has an error, and the line "We’ll notify you on this date. No advance notifications.", in resources/views/app/vaults/contacts/_reminders.blade.php
- [X] T015 [US3] Test `aria-controls="contact-reminder-time"`, the `time` and `timezone` field names, the default time and the user's time zone selected, in tests/Feature/Views/Vaults/ContactRemindersPanelTest.php

## Phase 5: Polish

- [X] T016 Run `php artisan monica:localize`, translate every missing string in every configured locale (not `lang:update`, which rewrites the files), and pass `php artisan monica:localize --check`, in lang/*.json
- [X] T017 Run `bun run build`, then the whole suite: `php artisan test --compact --parallel`
- [X] T018 Run `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent`

## Dependencies

- T001 before T002, T006, T007. T003 before T007.
- US1 before US2 and US3, which extend the same form. T004 and T010 touch the same method: T004 first.
- Parallel: T003 with T001 and T002; T004 and T005 with T006 to T008.
- T016 to T018 last.
