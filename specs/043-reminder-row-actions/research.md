# Research: Reminder row actions

## R1. Which modal the confirmations use

- **Decision**: `x-modal.form` with `title`, `description`, an empty body, `method` and `action`.
- **Rationale**: it is the neutral confirmation the application already has: a primary confirm button,
  Cancel, Escape, scrim click, `x-trap` and `aria-label` from the title. Neither action destroys
  anything, so the danger tone of `x-modal.warning` would say the wrong thing.
- **Rejected**: a new `x-modal.confirm` (a third modal for what `x-modal.form` already draws).

## R2. The More menu

- **Decision**: `x-actions-menu` with `text="More"` and a chevron in its `glyph` slot, as
  `settings/account/life-event-types/_category-actions.blade.php` does. The component gains a
  `variant` prop (default `secondary`) passed to `<x-button :variant>`, so the row asks for `ghost`
  as 2a draws it, and an `x-ref="menuTrigger"` on its button.
- **Rationale**: `x-button` already takes `variant`; the rule in `components.md` forbids
  `x-dynamic-component` for this, and a prop is what the rule prescribes when call sites differ.
- **Rejected**: keeping the secondary look (two identical buttons side by side, against 2a).

## R3. Menu items that open a modal

- **Decision**: the two modal items are `<button role="menuitem">` using the menu item class string,
  each the `trigger` of its own modal, nested inside the menu. Go to the reminder stays an
  `x-actions-menu.item`. The separator is a `role="separator"` hairline.
- **Rationale**: `components.md` settles it: items that are links on one screen and modal triggers
  on another share a class string, not a component. The modal is teleported to `body`, so the menu
  hiding does not hide it.

## R4. Focus after a modal opened from the menu closes

- **Decision**: the item's handler runs `menuOpen = false; $refs.menuTrigger.focus(); open = true`.
- **Rationale**: `x-trap` returns focus to whatever held it when the trap started. Left alone that is
  the menu item, which is hidden by then, so focus falls to `body`. Focusing More first makes More
  the element returned to. Alpine merges `$refs` across ancestor components (`getArrayOfRefObject`),
  so the modal's scope reaches the menu's ref.
- **Rejected**: a `$watch` on the modal's `open` (the modal's root is inside the shared component and
  takes no attributes from the caller).

## R5. Where the confirmations live

- **Decision**: a new `reminders/_dismiss.blade.php`, included by the row and by the next deadline
  card on `show.blade.php`. `_postpone.blade.php` takes `inMenu` (bool): a menu item trigger in the
  row, today's trigger on the card. The test modal is written once, in the row.
- **Rationale**: dismiss is drawn in two files, like postpone already is; the test is drawn in one.

## R6. What the dismiss modal says

- **Decision**: a new `ReminderRow::dismissalNote()`: when the rule recurs (`cadence->recurs()`),
  "This one will not be sent. The next one stays scheduled."; otherwise "This one will not be sent,
  and nothing comes after it." The title is `Dismiss “:text”?`, naming the contact through the text.
- **Rationale**: the wording depends on the rule, which is presentation logic a view model may hold.

## R7. The link and what sits inside it

- **Decision**: the stamp and text are one `<a>` to `reminderUrl()` when `linkToReminder` is true,
  and a `<div>` otherwise. Inside a link the contact name is plain text and the tooltip on the
  relative time is not focusable; its `role="img"` label puts the exact moment in the link's name.
- **Rationale**: interactive content may not nest in a link. On the reminder's screen, where the row
  is no link, the name stays a link and the tooltip stays focusable.

## R8. Tests that describe the old controls

`ReminderControllerTest` asserts `aria-label="Dismiss this reminder"`, `"Send it to me now"` and
`"Open this reminder"`. Those controls go, so the assertions move to the new copy and the dialogs'
`action` URLs. `IconButtonTest` uses the strings as sample data and is untouched.
