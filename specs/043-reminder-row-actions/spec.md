# Feature Specification: Reminder row actions

**Feature Branch**: `2026-09-23-reminders` | **Created**: 2026-09-23 | **Status**: Draft

**Input**: "Redesign the reminders screen based on design 2a of `Reminder actions.dc.html`. Make sure
that actions have a confirmation step as a modal first: as of now, clicking Dismiss does the action
without confirmation. Screen is /vaults/personal/reminders."

## Context

Each row ends in four unlabelled icon buttons: open, remind me later, send it to me now, dismiss. Two
of them (send, dismiss) act on one press. Design 2a draws the row as a closed envelope: a tinted flap,
the date as a perforated stamp on the left with the cadence under it, the stamp and text as one link to
the reminder, then a labelled **Dismiss** button and a **More** menu (Remind me later, Send me a test,
separator, Go to the reminder). This feature adopts it and puts a modal before every action that
changes something.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Dismiss asks before it acts (Priority: P1)

The reader presses Dismiss on Joey Tribbiani's birthday. A modal names the reminder and says this one
will not be sent, and that the next one stays scheduled. Confirming dismisses it; cancelling changes
nothing. This is the reported defect.

**Independent Test**: press Dismiss, check nothing changed; cancel; press again, confirm, check it is
dismissed.

1. **Given** a scheduled reminder, **When** Dismiss is pressed, **Then** a modal opens naming the
   reminder and its contact, and nothing is dismissed yet.
2. **Given** the modal is open, **When** it is cancelled, escaped or clicked outside, **Then** the
   reminder is unchanged.
3. **Given** the modal is open, **When** it is confirmed, **Then** the reminder is dismissed and the
   reader sees today's confirmation message.
4. **Given** a repeating reminder, **Then** the modal says the next one stays scheduled; **given** a
   one time reminder, **Then** it promises no next one.

### User Story 2 - The row reads the way 2a draws it (Priority: P1)

The reader sees the stamp, the cadence, the contact's avatar and name with a chevron, the kind icon,
the text and when it is sent ("Joey’s birthday · in 2 weeks"). Pressing that area opens the reminder.

1. **Given** a reminder, **Then** its row shows month and day, the cadence when it repeats, the
   contact, the text and when it is sent, and its main area links to the reminder.
2. **Given** a deleted contact, **Then** the row says "A deleted contact" and keeps its actions.
3. **Given** a late reminder, **Then** its time is still marked late, and the exact moment shows on
   hover and is spoken as part of the row's link.

### User Story 3 - The More menu, each item confirmed (Priority: P2)

Remind me later opens the existing "move it to" modal. Send me a test opens a modal saying the
reminder will be emailed to the reader now and nothing else changes. Go to the reminder navigates.

1. **Given** a row, **When** More opens, **Then** it lists Remind me later, Send me a test, a
   separator, Go to the reminder.
2. **Given** the menu is open, **When** Escape is pressed or the reader clicks away, **Then** it
   closes and focus returns to More.
3. **Given** Send me a test is picked, **Then** nothing is sent until the modal is confirmed, and
   confirming shows today's message.

### User Story 4 - The reminder's own screen asks too (Priority: P2)

The next deadline card on a reminder's screen carries Dismiss, and the history there reuses the row.
Both follow the same rule.

1. **Given** that card, **When** Dismiss is pressed, **Then** the same dismiss modal opens.
2. **Given** a row on that screen, **Then** its main area is not a link and More omits Go to the
   reminder.

### Edge Cases

- Dealt with in another tab before confirming: the reader gets today's "already dealt with" refusal.
- At most one menu and one modal are open at a time.
- Narrow screens: the actions wrap under the text and the menu stays inside the viewport.
- Switching the grouping replaces the list in place; the new rows' controls work without a reload.
- Keyboard only: every control is reachable, each modal traps focus and returns it on close.

## Requirements *(mandatory)*

- **FR-001**: A row MUST follow 2a: flap, stamp with the cadence under it, avatar and name with a
  chevron, kind icon, text and when it is sent.
- **FR-002**: The stamp and text MUST be one link to the reminder, replacing the open button.
- **FR-003**: The right side of a row MUST carry exactly a labelled Dismiss button and a labelled More
  button.
- **FR-004**: More MUST offer Remind me later, Send me a test, a separator, then Go to the reminder.
- **FR-005**: Dismiss MUST open a modal naming the reminder and its contact, saying this one will not
  be sent and, when it repeats, that the next one stays scheduled.
- **FR-006**: Send me a test MUST open a modal saying the reminder is emailed to the reader now and
  nothing else changes.
- **FR-007**: Remind me later MUST open the existing postpone modal, unchanged.
- **FR-008**: Go to the reminder MUST navigate without a modal.
- **FR-009**: Cancelling a modal MUST leave the reminder unchanged.
- **FR-010**: Confirming MUST perform today's action with today's result and messages.
- **FR-011**: Dismiss on the next deadline card of a reminder's screen MUST open the same modal.
- **FR-012**: On a reminder's own screen the row MUST NOT be a link and More MUST omit Go to the
  reminder.
- **FR-013**: A late reminder MUST stay marked late; the exact moment MUST show on hover and be spoken.
- **FR-014**: More MUST report whether it is expanded; the menu and each modal MUST close on Escape
  and return focus to the row's button they came from (Dismiss or More).
- **FR-015**: Every new wording MUST be translated in every configured locale.

## Success Criteria *(mandatory)*

- **SC-001**: No action that changes a reminder or sends an email completes in one press, on either
  screen.
- **SC-002**: Cancelling any modal leaves the reminder unchanged, every time.
- **SC-003**: No row carries an unlabelled icon button.
- **SC-004**: Every action on a row can be reached, confirmed and cancelled with the keyboard alone.

## Assumptions

- Only 2a is built. The menu takes 2a's short labels; the modals carry the explanation.
- On the list the contact's name stops being its own link, since a link cannot sit in a link. The
  reminder's screen links to the contact, and its rows keep the name linked.
- The late stripe on the row's edge and the year under the stamp are dropped, as 2a draws neither.
  The late group tint, the late tone and the exact moment remain.
- Remind me later keeps its current modal, which already asks first.
- The dismiss and test modals are neutral confirmations: neither destroys anything.
- The Sent tab and the contact screen's reminder block carry neither the row nor these actions.
- What the actions do, refuse and journal is unchanged.
