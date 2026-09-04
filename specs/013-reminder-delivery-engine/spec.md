# Feature Specification: Reminders that actually fire

**Feature Branch**: `2026-09-02-reminder-delivery-engine`

**Created**: 2026-09-02

**Status**: Draft

**Input**: A reminder rule is stored correctly today and does nothing. No deadline is persisted, no notification is produced, no quiet hours are applied. This builds the three missing models ([041](../../spec/models/041-reminder-occurrence-done.md), [042](../../spec/models/042-notification-done.md), [043](../../spec/models/043-notification-preference-done.md)), the generation and delivery machinery, the reconciliation of deadlines with every change that already exists, and the validation fix the reminder form needs. **Out of scope by instruction**: every screen, and every webhook. Decisions and rejected alternatives are in [research.md](research.md).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - A saved rule produces dated deadlines (Priority: P1)

Ticking "Add a reminder" on Rachel's birthday, 12 May, asking for seven days before and on the day at 09:00, leaves the product holding two concrete deadlines for the coming cycle, each stored as the exact instant it falls on. Nothing has been sent. It is the whole of the chain: without a persisted deadline there is nothing to find, notify or postpone.

**Independent Test**: generate for that rule, read back two deadlines at the right absolute instants each carrying the moment that made it, then generate again and watch the count hold.

1. **Given** that rule in `Europe/Paris`, **When** generation runs, **Then** deadlines exist at 05 May 07:00 UTC and 12 May 07:00 UTC.
2. **Given** two workers generating the same rule at once, **When** both finish, **Then** one row exists per cycle and moment.

### User Story 2 - The deadline reaches the person (Priority: P2)

The deadline comes due, appears in the product and arrives by email. Due inside the hours the account said not to be disturbed, it waits and arrives when they end, while the deadline goes on saying it was due at 3am. A deadline nobody is told about is the same as no deadline.

**Independent Test**: make a deadline due, run delivery, find one in-app notification and one email for the account's user, and the deadline marked notified.

1. **Given** quiet hours of 22:00 to 08:00 and a deadline due at 03:00, **When** delivery runs at 03:00 then at 08:00, **Then** nothing goes out first and it goes out second.
2. **Given** a held notification and the contact archived while it waited, **When** it may go out, **Then** nothing is sent and the row records why.

### User Story 3 - Postponing or dismissing one deadline (Priority: P3)

Moving the 5 May deadline to an exact future instant means "not now, remind me then" and leaves the 12 May sibling, next year's cycle and the birthday itself untouched. Dismissing it means "do not interrupt me about this deadline again", not that an action was completed, and still leaves next year. Both keep the deadline and every notification already sent in the record rather than erasing history. Getting this blast radius wrong is the classic bug in this feature.

**Independent Test**: generate a cycle of two, postpone one, assert the sibling and the source value are unchanged and the postponed one keeps its original instant beside its new one.

1. **Given** a postponed deadline, **When** generation runs again, **Then** it is not recreated at its original instant.
2. **Given** a dismissed or cancelled deadline, **When** postponement or dismissal is attempted, **Then** it is refused.
3. **Given** a notification still waiting for a deadline's old instant, **When** that deadline is postponed or dismissed, **Then** the notification is abandoned and never goes out, while one already sent remains as history.

### User Story 4 - Every change reconciles the future, never the past (Priority: P4)

The date is corrected, the cadence changes, a moment is added, the contact is archived and unarchived, the field leaves the structure and comes back. Each time the deadlines still ahead follow, whatever is cancelled says why, and nothing already past moves. The paths that change a rule already exist; leaving them silent about deadlines would fire reminders for dates nobody holds.

**Independent Test**: generate, correct the source date, and assert future deadlines cancelled as source changed, replacements on the new date, past ones identical.

1. **Given** deadlines cancelled because the contact was archived, **When** it is unarchived, **Then** only those still ahead come back, in the state they held.
2. **Given** deadlines cancelled for two different reasons, **When** one cause is reversed, **Then** the other stays cancelled and keeps its own reason.

### User Story 5 - A bad reminder is refused under the form, not five hundred (Priority: P5)

Adding the same moment twice, or asking for "365 months before", brings the block back with a sentence under it. The form declares the shape of a moment but never judges the set, so these reach the action and raise an exception nothing catches.

**Independent Test**: post a section save carrying two identical moments and assert a refused save with a message, not a server error.

1. **Given** a moment of "24 months before", **When** the block is saved, **Then** the save is refused.
2. **Given** two saves racing to put a rule on one value, **When** both finish, **Then** one live rule exists.

### User Story 6 - The account knows how it wants to be told (Priority: P6)

Every account holds one row saying which channels are on, when not to be disturbed, and whether things arrive at once or folded into a daily summary. Delivery reads it on every send, so a missing row would mean a silent account or a default nobody can see.

**Independent Test**: create an account and read back in-app and email on, push off, quiet hours off, delivery immediate.

1. **Given** an account created before this feature, **When** the upgrade runs, **Then** it has that row.
2. **Given** quiet hours whose start and end are equal, **When** anything is judged, **Then** it is held and nothing is lost.

### Edge Cases

- A local time that does not exist, or exists twice, at a daylight saving boundary.
- A yearly rule anchored on 29 February, and a monthly rule anchored on the 31st.
- A `recurring_date` whose year is unknown, stored under a placeholder year.
- A moment reaching back into the previous cycle, or forward out of one already gone by.
- Quiet hours crossing midnight; a digest hour inside them; a digest with nothing in it.
- A deadline postponed past the end of the generation window.
- Generation not having run for longer than the window.
- A share link viewer, who is never a recipient.

## Requirements *(mandatory)*

### Functional Requirements

| # | Area | Requirement |
| --- | --- | --- |
| FR-001 | The deadline | One deadline MUST be persisted per pair of cadence cycle and moment, and a deadline MUST NOT be a notification. |
| FR-002 |  | Each deadline MUST carry an unguessable identifier, its cycle's local date, an immutable snapshot of the moment that made it, the instant it was first due, the instant it is due now, and the local day that instant falls on. |
| FR-003 |  | Each deadline MUST carry a stable identity derived from its rule, cycle date and normalised moment, and a second deadline with that identity MUST be refused. |
| FR-004 |  | Each deadline MUST carry a state among pending, notified, postponed, dismissed and cancelled, and record when it entered each state it reaches. |
| FR-005 |  | A cancelled deadline MUST record which of the seven reasons cancelled it, and the actionable state it held beforehand when the cancellation is reversible. |
| FR-006 |  | Deadlines MUST stay efficiently queryable by the two questions asked constantly: which are due now, and which belong to one rule. |
| FR-007 | Generating them | Deadlines MUST be generated for a rolling window ahead of now whose length is a configuration value. |
| FR-008 |  | Generation MUST be idempotent, and MUST NOT produce two rows for one deadline under concurrent workers. |
| FR-009 |  | Generation MUST find every deadline falling inside the window, including moments reaching into cycles outside it, in both directions. |
| FR-010 |  | Generation MUST NOT create a deadline already in the past, nor one earlier than the instant its rule was created or last had its schedule changed. |
| FR-011 |  | Generation MUST skip inactive rules, removed rules and rules whose value was cleared, and a rule that fires once MUST produce its deadlines once. |
| FR-012 |  | Generation MUST resolve a local time that does not exist or exists twice to one deterministic instant without failing. |
| FR-013 |  | Generation MUST run on a schedule and record each run, so a run that produced nothing is distinguishable from one that never happened. |
| FR-014 | Reconciling them with change | Creating a rule MUST generate its deadlines. |
| FR-015 |  | Correcting the value a rule reads MUST cancel its future deadlines as source changed and generate replacements. |
| FR-016 |  | Changing a rule's cadence, interval, moments, local time or frozen zone MUST cancel its future deadlines as rule changed and generate replacements; changing only its wording MUST do neither. |
| FR-017 |  | Removing a rule MUST cancel its future deadlines as rule disabled, and restoring it MUST restore those still ahead and generate whatever else the window needs. |
| FR-018 |  | Archiving or deleting the record, removing the field and clearing the value MUST each cancel future deadlines with their matching reason. |
| FR-019 |  | Reversing one of those causes MUST restore only the deadlines cancelled for that same reason and only those still ahead; cancellations recorded as source changed or rule changed MUST NOT be reversed. |
| FR-020 |  | No reconciliation MUST ever alter a deadline whose instant has passed, and each reconciliation MUST be atomic. |
| FR-021 | Acting on one deadline | Postponing MUST move only that deadline to an exact future instant chosen by the caller, update its local day, postponed-to instant and postpone count, keep its first generated instant, and touch no sibling and no later cycle. No fixed postponement duration belongs to the model, and postponement MAY happen more than once. |
| FR-022 |  | Postponing MUST abandon every notification not yet sent for the former instant, MUST keep sent notifications as history, and MUST allow the moved occurrence to produce notifications at its new instant. |
| FR-023 |  | Dismissing MUST record when, make no claim that an action was completed, stop that occurrence being actionable, abandon every notification not yet sent for it, keep sent notifications as history, leave its rule active, and allow the next cycle to be produced. |
| FR-024 |  | The product MUST be able to cancel one deadline with a reason, which is what every reconciliation path uses. |
| FR-025 |  | Postponement and dismissal MUST be refused on a deadline already dismissed or cancelled; postponing MUST be refused into the past; a deadline in another account MUST NOT be reachable; and a deadline acted on MUST NOT be regenerated at its original instant. |
| FR-026 | How the account wants to be told | Each account MUST hold exactly one set of preferences: channels, whether quiet hours apply and between which local times, delivery mode, digest hour and days, the zone all of it is read in, and per type exceptions. |
| FR-027 |  | A new account MUST get that row as it is created, and every account that already exists MUST be given one. |
| FR-028 |  | The defaults MUST be in-app and email on, push off, quiet hours off, delivery immediate, in the zone of the account's user. |
| FR-029 |  | Turning every channel off MUST be allowed and MUST mean silence rather than an error. |
| FR-030 |  | The preferences MUST NOT introduce a second lead time, since the rule's moments already became deadlines at the instants they name. |
| FR-031 | Delivering | A due deadline MUST produce one notification per enabled channel, each copying the deadline's current due instant and never applying the moment again. |
| FR-032 |  | At least the in-app and email channels MUST exist, and email MUST go out through the existing email chain. |
| FR-033 |  | A notification MUST record what it is about, who it is for, its channel, the words it carried, the data an interface needs to render it, and the language it was rendered in. |
| FR-034 |  | A notification's shape MUST NOT be limited to reminders. |
| FR-035 |  | Quiet hours MUST hold a notification and never drop it, MUST be evaluated correctly across midnight, and MUST leave the deadline's real due instant alone while the notification separately records when it may go. |
| FR-036 |  | Digest mode MUST fold a period's notifications into one delivery at the chosen hour and days, keeping their rows pointing at it; a digest with nothing in it MUST send nothing. |
| FR-037 |  | Immediately before anything is sent, the product MUST check the rule is still active, the record still present and unarchived, the occurrence still actionable, and the notification still names its current due instant; otherwise it MUST send nothing and record why. |
| FR-038 |  | Delivery MUST record when a notification was sent, when it failed and why, MUST retry a bounded number of times, and MUST NOT delete a row on failure. |
| FR-039 |  | Delivery MUST be idempotent, MUST NOT tell anybody twice, and MUST mark the deadline notified when its first notification actually went out. |
| FR-040 |  | Nothing MUST ever be delivered to somebody viewing a share link, and delivery MUST run on a schedule often enough that a deadline is not noticeably late. |
| FR-041 | Fixing what the form lets through | The reminder form MUST judge the whole set of moments with the same rule the actions behind it use. |
| FR-042 |  | The largest amount a moment may name MUST depend on its unit rather than being one number for all three. |
| FR-043 |  | The actions MUST validate the local time they are given, being the backstop for callers that are not the form. |
| FR-044 |  | A refusal reached through the form MUST come back as a refused save carrying a message and never a server error, and one live rule per value MUST hold under concurrent saves. |
| FR-045 | Keeping the written specification true | Model files [040](../../spec/models/040-reminder-in-progress.md) to [043](../../spec/models/043-notification-preference-done.md) MUST be brought in line with what is built and renamed to the status they reach, with answered questions resolved and deliberately open ones still stated, and [001-account](../../spec/models/001-account-in-progress.md) MUST record that it now carries notification preferences. |
| FR-046 |  | Everything deliberately left undone MUST be written down as not done rather than left to be rediscovered. |

### Key Entities

- **Reminder occurrence**: one concrete deadline from one moment in one cycle. Knows its cycle, its moment, when it was first due, when it is due now, what became of it and why. It is what a person postpones or dismisses; neither action turns it into a task.
- **Notification**: what reaches a person on one channel. Knows its subject, recipient, due instant, when it may go out, when it went, whether it failed and why, and which summary folded it. Not limited to reminders.
- **Notification preference**: one row per account holding channels, quiet hours, delivery mode and the zone they are read in.

## Success Criteria *(mandatory)*

- **SC-001**: Somebody who ticks the reminder box and does nothing else is told about that date, on the day they asked for.
- **SC-002**: A deadline arrives within five minutes of being due, unless quiet hours or a digest deliberately hold it.
- **SC-003**: A deadline due inside quiet hours arrives in the first minute after they end, and never inside them.
- **SC-004**: Postponing or dismissing changes exactly one deadline, with every sibling and later cycle measurably untouched.
- **SC-005**: Generation run any number of times over one window produces what running it once produces.
- **SC-006**: No deadline already past is ever altered by anything happening to its rule, record or field.
- **SC-007**: Every deadline that stops existing names one of the seven reasons.
- **SC-008**: Nobody is told about one deadline twice, on any channel, under any sequence of retries or repeated runs.
- **SC-009**: Nothing is delivered about a record archived or deleted between the deadline falling due and the notification going out.
- **SC-010**: No input to the reminder form produces a server error; every mistake comes back as a message under the block.
- **SC-011**: Every account, new or existing, has notification preferences.
- **SC-012**: No model file under `spec/` claims to be unbuilt once it is built.
- **SC-013**: Nothing prepared for an occurrence's former instant, or for one since dismissed, is delivered; every delivery already sent remains in history.

## Assumptions and Dependencies

Every decision taken where the description left room, with its rationale and the alternatives rejected, is in [research.md](research.md). The load-bearing ones: a 90 day configurable generation window, deadlines already past are never invented, quiet hours reach every channel, a moment is capped per unit, a doubled local time resolves to the later of its two instants, and retention stays undecided.

This depends on the existing rule and its arithmetic, the paths that already silence and revive it, the existing email chain, and the scheduler and queue that already run jobs. None of them is replaced.
