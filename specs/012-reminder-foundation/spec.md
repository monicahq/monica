# Feature Specification: Reminder foundation on a date field

**Feature Branch**: `2026-09-02-reminder-foundation`

**Created**: 2026-09-02

**Status**: Draft

**Input**: Build the foundation for reminders at the contact level, from the design `Reminder from a date field.dc.html`: editing a date field on a contact offers a checkbox, and ticking it reveals the wording, the cadence, one or several moments, the local time and the frozen time zone.

[Reminder](../../spec/models/040-reminder-not-yet.md) has no table and no model. This builds the rule and only the rule: models [041](../../spec/models/041-reminder-occurrence-not-yet.md), [042](../../spec/models/042-notification-not-yet.md) and [043](../../spec/models/043-notification-preference-not-yet.md) stay untouched.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Ask to be reminded of a date (Priority: P1)

Somebody edits the block of a contact holding a date, ticks **Add a reminder**, and the options appear
below it: what it should say, whether it happens once or comes back, one or several moments to be told
at, and the hour it fires in their own zone. Saving the block saves the rule, and the screen says in
one sentence what was agreed to.

**Why this priority**: it is the whole capability. A date nobody is told about is only a record.

**Independent Test**: tick the box on a birthday, choose seven days before and on the day, save, and the rule is stored against that value with both moments.

**Acceptance Scenarios**:

1. **Given** a date field holding 3 February, **When** the box is ticked, the wording left empty and the block saved, **Then** a rule exists against that value, worded `<field label> — <contact name>`, and the block reads back with the box ticked.
2. **Given** the reminder options are open, **When** a second moment is added and the block saved, **Then** the rule holds both moments in the order they were given.
3. **Given** the cadence is set to come back every year at 09:00, **When** the block is saved, **Then** the rule holds that cadence, that hour, and the zone preferences carry.
4. **Given** the box is ticked while the date is empty, **When** the block is saved, **Then** the save is refused under the date, and neither the value nor a rule is written.

---

### User Story 2 - Change it, or stop it (Priority: P2)

Reopening the block shows the box ticked and every option holding what was saved. Changing the date
leaves the rule attached to it. Unticking takes the rule away and leaves the date where it was.

**Why this priority**: a rule nobody can correct is worse than none, and the design promises the box is reversible in so many words.

**Independent Test**: untick the box, save, and the date is still there while the rule is gone.

**Acceptance Scenarios**:

1. **Given** a saved rule, **When** the block is reopened, **Then** the wording, cadence, moments, hour and zone read back as saved.
2. **Given** a saved rule, **When** the date is corrected and the block saved, **Then** the rule survives and reads from the corrected value.
3. **Given** a saved rule, **When** the box is unticked and the block saved, **Then** the rule is gone from the screen and the value is unchanged.
4. **Given** a saved rule, **When** the date is cleared and the block saved, **Then** the rule stops producing anything and says on the screen that the date it read is gone.

---

### User Story 3 - A rule that has gone quiet says why (Priority: P3)

Archiving the contact, deleting them, or taking the field out of the structure stops the rule. It is
not hidden and not deleted: it says which happened, and putting the contact or field back revives it.

**Why this priority**: it is section 3.3, no silent deletion, applied to reminders, and three actions in the code already carry a comment saying this is the missing half.

**Independent Test**: archive the contact, and the rule reads inactive because the record is archived; unarchive, and it reads active again.

**Acceptance Scenarios**:

1. **Given** an active rule about a contact, **When** the contact is archived, **Then** the rule is inactive and says the record is archived; unarchiving makes it active again.
2. **Given** an active rule about a contact, **When** the contact is deleted, **Then** the rule is inactive and says the record is deleted; restoring makes it active again.
3. **Given** an active rule reading a field, **When** that field leaves the structure, **Then** the rule is inactive and says the field was removed, and it is not deleted.

### Edge Cases

- A moment of **on the day** carries no amount and no unit, and cannot be given one. Two identical moments on one rule are refused: one can never say anything the other has not.
- A moment outside 1 to 365, an interval outside 1 to 99, an unknown cadence or unit, or a zone that is not a known IANA identifier is refused and nothing is written.
- A date whose year nobody knows still drives a rule: the day and the month are what it reads, and a next date of 29 February, or of a day a shorter month has not got, resolves to a real day.
- Two rules for one date value are impossible: the box either holds one or holds none.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Every `date` and `recurring_date` field MUST offer to add a reminder while the record is being edited, on any page, supplied or invented, and the capability MUST come from the type rather than from a switch on the field. The obsolete `is_reminder_source` column MUST disappear with its builder wiring, its import and export payload and its factory state.
- **FR-002**: The reminder options MUST be hidden until the box is ticked, and MUST hold: the wording, whether it happens once or comes back, the cadence and its interval when it comes back, one or more moments, the local hour, and the time zone.
- **FR-003**: A moment MUST say whether it falls on the day, before it or after it, and by how much, in days, weeks or months.
- **FR-004**: Somebody MUST be able to add and remove moments, and MUST never be able to remove the last one.
- **FR-005**: Each moment MUST be shown in words as it is set, and the rule MUST be summarised in one sentence naming its moments, its cadence, its hour and its zone.
- **FR-006**: The screen MUST show the next date the rule would fall on, computed from the value and the cadence.
- **FR-007**: An empty wording MUST be stored as the field's label and the contact's name, which is what the screen offers as a placeholder.
- **FR-008**: The time zone MUST be prefilled from the time zone in preferences, MUST be changeable, and MUST be frozen on the rule so that changing the preference later never shifts it.
- **FR-009**: The rule MUST be saved by the same save as the block it sits in, and MUST NOT be written when that save is refused.
- **FR-010**: A rule MUST record the record it is about, the field and the exact value it reads, and there MUST be at most one rule per value.
- **FR-011**: Unticking the box MUST take the rule away and leave the value untouched. Nothing MUST be deleted outright: a rule that has been taken away MUST be restorable, per section 3.3 of the specification.
- **FR-012**: Clearing the value, archiving the record, deleting the record, or removing the field from the structure MUST each deactivate the rule with the reason that applies rather than delete or hide it, and restoring, unarchiving or putting the field back MUST reactivate the rules that action deactivated and nothing else.
- **FR-013**: A rule MUST be reachable only within its own account and its own vault, and MUST never be shown through a share link.
- **FR-014**: Creating, editing, deactivating and removing a rule MUST each be recorded in the account's log.
- **FR-015**: The reminder options MUST satisfy WCAG 2.2 AA (labelled, keyboard operable, announced when they appear, focus visible, legible in both themes and at 200%) and every string they carry MUST be translatable in all nine locales, counted phrases through `trans_choice()`.
- **FR-016**: The specification under `spec/` MUST be brought in step: [Reminder](../../spec/models/040-reminder-not-yet.md) renamed to its new status with a **Not done yet** section, its `kind` and `offset_days` corrected as the Key Entities below describe, and [Field](../../spec/models/017-field-done.md) updated where it names `is_reminder_source` and where it says only a recurring date can drive a rule.

### Key Entities

- **Reminder rule**: what somebody agreed to. It names the account, the vault, the record it is about, the field and the value it reads, its wording, its source, its cadence, its moments, its hour, its frozen zone, whether it is active and why it is not.
- **Source**: where the need comes from. Only *a value on a record* can be built here; a manual entry, an activity and an inactivity are named in the specification and have no screen, so they are not cases yet.
- **Cadence**: whether the rule fires once or comes back, and how often when it comes back. It is a separate axis from the source, which is what lets a complete date drive a yearly rule.
- **Moment**: a direction, an amount and a unit, several per rule. Days alone cannot express three months before a date, so the stored shape carries the unit rather than a signed count of days.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Somebody adds a reminder to a date they are already editing in under twenty seconds, without leaving the block.
- **SC-002**: Reopening the block reads back every option exactly as it was saved, including the zone.
- **SC-003**: Every reason a rule goes quiet is visible on the screen in words, none of them hides or deletes it, and each of the reversible ones puts the rule back exactly as it was.
- **SC-004**: The whole of the reminder options is completed with a keyboard alone and with a screen reader.
- **SC-005**: No queue worker, scheduled command or notification channel is added, and nothing yet arrives anywhere.

## Assumptions

- **Concrete deadlines are out of scope.** [ReminderOccurrence](../../spec/models/041-reminder-occurrence-not-yet.md) asks how far ahead deadlines are generated and the specification does not answer it, so nothing here stores or generates one; the next date is computed on read. Postponing, skipping and completing arrive with that model, and delivery with 042 and 043: no notification, no quiet hours, no digest, no channel.
- **Contacts only.** The record a rule is about is stored so an entity can hold one later, but entities have no screens at all today, so nothing here draws one.
- **The rule lives inside the block's own form and its own save.** A reminder is an answer about a date, not a second screen, which is what the design shows and what keeps the promise that nothing is written until the block is saved.
- **The cadence offered is the design's**: once, or coming back yearly, monthly, weekly, or every so many years. Weekdays, a day of the month and an end date are columns the specification names and no screen asks for yet.
- **The zone list is the one the preferences screen knows**, from feature 011. No second source and no new dependency.
- **The design's palette and components are the application's own**, not the design file's literal values: the checkbox, inputs, select, notice and panel all exist as components.
