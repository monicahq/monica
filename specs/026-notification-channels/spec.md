# Feature Specification: More ways for a notification to reach somebody

**Feature Branch**: `026-notification-channels`

**Created**: 2026-09-09

**Status**: Draft

**Input**: User description: "add more notification channels based on https://laravel.com/framework/docs/notifications"

## Context, and one conflict to settle first

The product already names three channels and delivers one. `spec/spec.md` §10.4 says an account
chooses "the channels enabled (in app, email, push notification)". Today only email leaves the
building: the delivery step asks a single question, "is this one an email?", and returns silently for
every other answer. The in-app channel writes a row that no screen reads. The push channel is
declared undeliverable outright, so a notification is never even written for it, which means a person
can switch push on and receive nothing with no explanation.

So there are two different requests hiding inside "add more channels", and they cost very different
amounts:

1. Make the product able to carry a notification on more than one channel at all. Nothing in the
   product contradicts this and it is a precondition for everything else.
2. Add specific new destinations beyond the three already named.

The second one runs into a decision the product has already written down. `spec/spec.md` §14.2 gives
the reason webhooks exist, and one of the four reasons listed is "allowing somebody to build their
own notification, on a channel we will never provide." That sentence is now wrong, and this feature
is what makes it wrong. **`spec/spec.md` MUST be amended in the same pull request**: §14.2 loses the
claim that the product will never provide a channel, and §10.4 stops listing three channels and
starts saying that the list is open. Webhooks keep every other reason they exist for. Per Governance,
the change that finds the specification contradicting the product says which is wrong and fixes it,
and here the specification is wrong.

Two further constraints bear on which channels are even possible:

- **A feature must not exist on one deployment only** (Product Invariant). Any channel that depends
  on an outside service has to be usable by a self hosted instance, which means the instance or the
  account supplies its own credentials and the channel is simply absent, and visibly absent, when
  nobody has.
- **Server side push has to exist for the native applications** (Technology Constraints, and
  `spec/spec.md` §3.8). Push is the one unbuilt channel the product has already committed to.

This specification therefore has two halves that have to be built in that order. The plumbing, so
that a channel is a thing the product can be handed rather than a branch somebody wrote, and then
four destinations on top of it: a notification bar inside the application, Slack, Telegram and text
message. The plumbing is the part that decides whether the fifth destination costs a day or a month.

**Out of scope**: the webhook subsystem of `spec/spec.md` §14, which is unbuilt and is not a
notification channel. Channels supplied by community modules, which the constitution puts behind an
amendment that has not been written. Any change to how a deadline is computed, postponed or
dismissed.

## Clarifications

### Session 2026-09-09

- Q: Which new destinations should a notification be able to reach, beyond the in-app and email channels that exist today? → A: Slack, Telegram, text message, and a notification bar inside the application. The set must be extensible, so adding a further channel touches that channel alone.
- Q: For Slack, Telegram and text message, who supplies the credentials that let the product talk to the service at all? → A: The account holder supplies both the credential and the destination. The instance operator configures nothing and pays for nothing.
- Q: Must a channel's destination prove itself with a successful test message before it is allowed to carry real notifications? → A: Only where a wrong destination fails silently, which today means text message alone. Slack and Telegram refuse an unknown destination loudly, so they are trusted once entered.
- Q: For text message, what should count as proof that the destination is the right one? → A: A code sent by text and typed back into the screen. A successful send proves only that the provider accepted the number, so it is not proof.
- Q: What happens to a notification in the bar once the person is finished with it: is it kept forever, hidden, or actually deleted? → A: Kept, then removed automatically once it passes a stated retention period. Principle VIII permits this under an explicit retention policy, so the policy has to be explicit, configurable and visible.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - A channel carries a message, whatever channel it is (Priority: P1)

Every channel is asked to carry a message the same way, and what came of it is recorded the same way.
A channel that is switched on either delivers or records why it did not. Nothing is switched on and
silently inert.

**Why this priority**: it is the whole of the feature. Today the delivery step names one channel in
an `if` and the daily summary names the same one again. Until a channel is a thing the product can be
handed rather than a branch somebody wrote, every new channel is another branch in two places, and
the push channel stays permanently unreachable.

**Independent Test**: switch on a channel that only records what it was asked to carry, make a
deadline fall due, and find one notification per switched-on channel, each marked sent by its own
channel, the deadline marked notified once, and email behaving exactly as it did before.

**Acceptance Scenarios**:

1. **Given** an account with two channels switched on, **When** a deadline falls due, **Then** one
   notification exists per channel, each carrying the same title, body and language.
2. **Given** a channel that refuses the message, **When** delivery runs repeatedly, **Then** that
   notification is retried up to the configured number of attempts, then abandoned with the reason
   recorded on it, and the other channel's notification is delivered normally.
3. **Given** a channel the account has not finished setting up, **When** delivery reaches a
   notification for it, **Then** nothing is sent, the notification records that the channel is not
   configured, and no exception escapes.
4. **Given** an account in digest mode with two channels switched on, **When** the summary is sent,
   **Then** the account receives one summary per switched-on channel and every folded notification is
   marked as belonging to the summary of its own channel.
5. **Given** a notification recorded before this change, **When** delivery runs, **Then** it is
   carried exactly as it was.

---

### User Story 2 - The account says where each channel reaches it (Priority: P2)

Email knows where to go, because an account is one person with one address. Nothing else does. A
channel that carries a message somewhere needs a destination recorded for it, changeable, and
removable. A channel with no destination is off, and says so.

**Why this priority**: it is what stops the push column's failure from repeating. A switch that can
be turned on while nothing behind it can deliver is worse than an absent feature, because the person
believes they are covered.

**Independent Test**: switch a channel on with no destination and watch the save refused with a
sentence, then record a destination, save, and watch a due deadline reach it.

**Acceptance Scenarios**:

1. **Given** a channel switched on and no destination for it, **When** the preferences are saved,
   **Then** the save is refused and names what is missing.
2. **Given** a recorded destination, **When** it is removed, **Then** the channel is switched off with
   it, notifications already sent stay as history, and notifications still waiting on that channel
   are abandoned with the reason recorded.
3. **Given** a destination belonging to another account, **When** it is read or changed, **Then** it
   fails as if it did not exist.
4. **Given** a destination that has failed on every attempt for a sustained run of notifications,
   **When** the threshold is passed, **Then** the channel is switched off, the destination is kept,
   and the account is told on a channel that still works.
5. **Given** a destination on a channel where a wrong one fails silently, recorded but never proven,
   **When** it is saved, **Then** the channel stays off until the account types back the code sent to
   that destination.
6. **Given** a destination on a channel where a wrong one is refused loudly, **When** it is saved,
   **Then** the channel may be switched on at once, and a wrong destination surfaces as a delivery
   failure with the service's own reason recorded.

---

### User Story 3 - The notification bar, so in-app finally means something (Priority: P3)

Every signed-in screen carries a bar showing how many notifications are unread. Opening it lists them
newest first, each saying what it is about and when it was due. Reading one takes the person to the
thing it concerns. The count goes down as they are read, and there is a way to clear the lot.

**Why this priority**: the in-app channel is switched on by default for every account and writes a row
that no screen has ever read. It is the only destination in this feature that needs no credential, no
outside service, no package and no amendment, and it turns the plumbing of the two stories above into
something a person can see on the day it ships. It is also the channel the other three fall back to
when they fail.

**Independent Test**: make a deadline fall due for an account with only the in-app channel on, sign
in, and see the count rise, the notification listed, and the count fall when it is read.

**Acceptance Scenarios**:

1. **Given** unread notifications, **When** any signed-in screen is opened, **Then** the bar shows how
   many, and the number is the count of unread notifications on the in-app channel and nothing else.
2. **Given** the bar opened, **When** it lists notifications, **Then** each shows its title, what it is
   about, when it was due and whether it has been read, newest first.
3. **Given** a notification about a deadline, **When** it is opened, **Then** it is marked read and the
   person lands on the record it concerns.
4. **Given** several unread notifications, **When** the person clears them all, **Then** they confirm
   first, every one is marked read, and none is deleted.
5. **Given** no notifications at all, **When** the bar is opened, **Then** it says so in a sentence
   rather than showing an empty box.
6. **Given** a notification on the in-app channel, **When** it is written, **Then** it counts as
   delivered at that moment, because the row is the delivery, and it is never retried and never
   abandoned.
7. **Given** the bar, **When** it is reached by keyboard alone and by a screen reader, **Then** the
   count, the list and the read state are announced.
8. **Given** notifications older than the retention period, **When** the removal runs, **Then** they
   are gone from the bar and from the count, the deadlines they were about are untouched, and the bar
   says how long notifications are kept.

---

### User Story 4 - Switching a channel on and off, and proving it works (Priority: P4)

The account opens its notification preferences, sees every channel the product offers, follows the
instructions to set one up, switches it on, sends itself a test, and sees whether it arrived.

**Why this priority**: the action that changes notification preferences exists and no screen calls
it. Channels nobody can reach are channels nobody has, and the three outbound destinations in the
next story cannot be switched on without this. The test send is what turns "I think this is
configured" into "I watched it arrive", and the reminder screens already offer exactly that gesture
for a deadline, so the pattern is settled.

**Independent Test**: open the preferences screen, switch a channel on, save, use the test send, and
see the outcome reported on the screen with nothing scheduled and no deadline touched.

**Acceptance Scenarios**:

1. **Given** the preferences screen, **When** it is opened, **Then** it lists every channel the
   product offers, says which are on, and says of any channel the account has not set up what remains
   to be done.
2. **Given** a channel switched off, **When** a deadline falls due, **Then** no notification is
   written for that channel.
3. **Given** the test send used on a configured channel, **When** it completes, **Then** the outcome,
   arrival or the reason for failure, is shown on the screen, and no deadline and no reminder is
   changed.
4. **Given** every channel switched off, **When** a deadline falls due, **Then** nothing is written,
   the deadline is still marked dealt with, and the account has already been warned on the screen
   that it will hear nothing.
5. **Given** a destructive change, removing a destination or switching off the last channel that
   works, **When** it is submitted, **Then** the account confirms it first.

---

### User Story 5 - Slack, Telegram and text message (Priority: P5)

The account is reached outside the application: in a Slack channel, in a Telegram conversation, or by
text message on a phone.

**Why this priority**: it is the visible half of the request, and it is fifth because every one of the
four stories above is a precondition. It is also the only part that reverses a written product
decision, needs outside services, and needs dependencies approved, so it is the part most likely to
be cut or staged, and putting it last means cutting it costs nothing already built.

**Independent Test**: switch one of the three on for an account, record its destination, make a
deadline fall due, and find the message at the destination in the account's language, with the
deadline marked notified.

**Acceptance Scenarios**:

1. **Given** one of the three switched on and configured, **When** a deadline falls due, **Then** the
   message arrives at the destination carrying the reminder's name, what it is about and the date it
   reads.
2. **Given** a message longer than the channel accepts, **When** it is carried, **Then** it is
   shortened at a sentence boundary rather than refused or cut mid word, and text message is the one
   that forces this.
3. **Given** the account's language, **When** the message is built, **Then** it is written in the
   language recorded on the notification and not in the language of whoever ran the delivery.
4. **Given** a destination outside the account holder's sole control, such as a shared Slack channel,
   **When** it is recorded, **Then** the account is warned in plain words that contact names will
   leave the account, and the warning is shown before the destination is saved.
5. **Given** an outside service that is rate limiting or unreachable, **When** delivery is attempted,
   **Then** the failure is treated as temporary and retried, and only a refusal the service calls
   permanent abandons the notification immediately.
6. **Given** one of the three failing entirely, **When** a deadline falls due, **Then** the in-app
   notification is unaffected, so the person still finds out.
7. **Given** a phone number saved but not yet proven, **When** a deadline falls due, **Then** no text
   message is written for it, and the preferences screen says the number is waiting for its code.
8. **Given** a Slack room or Telegram conversation the service does not recognise, **When** delivery is
   attempted, **Then** the service's own refusal is recorded on the notification and shown to the
   account, rather than the message disappearing.
9. **Given** a code sent to a number, **When** the account types it back correctly and in time,
   **Then** the number is recorded as proven and the channel may be switched on.
10. **Given** a code that has expired, been guessed wrongly too many times, or been asked for again
    too often, **When** the account tries to use it, **Then** it is refused with a sentence saying
    which of the three happened.

---

### User Story 6 - The record says which channel carried it (Priority: P6)

Wherever a notification is shown after the fact, the channel that carried it is named in the reader's
language, and a failure says which channel failed and why.

**Why this priority**: three existing screens already turn a channel into a sentence, each with its
own exhaustive list of three. A fourth channel that is not added to all three shows a reader a blank
or an error. Low priority because nothing breaks until a new channel actually ships, and unskippable
for the same reason.

**Independent Test**: record a notification on each new channel, open the reminder history, and read a
sentence naming that channel in the account's language.

**Acceptance Scenarios**:

1. **Given** a notification on a channel added after this change, **When** any screen that names a
   channel renders it, **Then** it reads as a translated sentence and never as a stored value.
2. **Given** a notification abandoned on one channel and delivered on another, **When** the history is
   read, **Then** both outcomes are visible with their own channel named.
3. **Given** a deadline whose notifications have passed the retention period, **When** its history is
   read, **Then** it still says the deadline was notified, says that the per-channel records are not
   kept beyond the retention period, and does not read as though nothing was ever sent.

---

### Edge Cases

- A channel the account started setting up and abandoned half way, holding a credential but no
  destination, or the reverse.
- A credential that expires or is revoked at the service while the account still believes it is set up.
- An account that pastes the wrong kind of secret into the wrong channel's field.
- A phone number proven once and later reassigned by its operator to somebody else, so proof ages.
- A phone number proven, then edited by one digit, which must fall back to unproven.
- A test message that costs the account money on a channel they are still setting up.
- A code typed back after the number it was sent to has already been edited.
- A code arriving late, after a second one was asked for, so two are briefly outstanding.
- A number proven on one account and then entered on another, where it must be proven again.
- An unread notification reaching the retention period, which leaves without ever having been read.
- A retention period shortened by an operator, which removes a great many notifications at once.
- A deadline whose every notification has been removed, whose history must still read honestly.
- A removal run overlapping with delivery, so a notification is removed between being chosen and being
  carried.
- A destination that worked and stops: a revoked credential, a disconnected number, a deleted room.
- Two channels pointing at the same place, so one deadline arrives twice.
- A channel that cannot confirm delivery, against one that can. The record already separates handing
  over from confirmed arrival and both have to stay honest.
- Quiet hours and the digest applying per account, while a channel's own throttling applies per
  channel.
- A message the channel truncates, or refuses for length, or rewrites.
- A channel whose outside service is rate limiting or unreachable, so failure is transient rather
  than permanent, and retrying is right rather than wrong.
- A notification written for a channel that is removed from the product before it is delivered.
- A deadline that is postponed or dismissed while a notification for it waits on a slow channel.
- The account language changing between the moment a notification is written and the moment it is
  carried. The language recorded on the notification wins.
- A share link viewer, who is never a recipient on any channel.
- A destination entered wrongly, a mistyped number or address, which reaches a stranger with a contact
  name in it.
- A Telegram bot the person has never spoken to first, which cannot open a conversation on its own.
- A Slack channel the bot was removed from, or a workspace whose token was revoked.
- A text message to a number in a country the account has no arrangement for, and the per-message cost
  that follows a runaway loop.
- A notification bar opened on a phone, where the count and the list have to fit.
- A person reading a notification in one browser tab while another tab still shows it unread.
- An account with thousands of unread notifications, where the bar cannot list them all.
- The in-app channel switched off, which leaves the bar with nothing to show and must say so rather
  than look broken.

## Requirements *(mandatory)*

### Functional Requirements

**Carrying a message**

- **FR-001**: The system MUST deliver a notification through the channel recorded on it, for every
  channel the product offers, without the delivery step naming any channel individually.
- **FR-002**: The system MUST record, per notification, that it was handed over, that arrival was
  confirmed where the channel can confirm it, or that it failed, with the reason.
- **FR-003**: The system MUST retry a failed delivery up to the configured maximum number of attempts
  and then abandon it, identically on every channel.
- **FR-004**: A failure on one channel MUST NOT prevent or delay delivery of the same deadline on any
  other channel.
- **FR-005**: The system MUST mark a deadline as notified once any of its notifications is delivered,
  and MUST NOT mark it notified when every channel failed.
- **FR-006**: The daily summary MUST be produced for every switched-on channel that can carry it,
  rather than for email alone.
- **FR-007**: The system MUST apply quiet hours and the delivery mode identically on every channel.
- **FR-008**: The system MUST render a notification in the language recorded on it.

**Where a channel reaches**

- **FR-009**: The system MUST let an account record, change and remove one destination per channel
  that needs one.
- **FR-010**: The system MUST refuse to switch on a channel that needs a destination and has none,
  and MUST say what is missing.
- **FR-011**: The system MUST treat a destination as belonging to exactly one account, and MUST fail
  as if the record did not exist when it is reached from another account.
- **FR-012**: The system MUST store every credential an account supplies encrypted, so that it is not
  readable from the database alone, MUST NOT show it back in full once saved, MUST NOT write it to a
  log, an error message or the changelog, and MUST let the account replace it without first revealing
  it.
- **FR-013**: The system MUST switch a channel off after a sustained run of failures on its
  destination, keep the destination, record why, and tell the account on a channel that still works.
- **FR-014**: The system MUST warn the account, before saving, when a destination is not under the
  account holder's sole control, because contact names will leave the account.

**Choosing channels**

- **FR-015**: Users MUST be able to see every channel the product offers, which are on, and what
  remains to be done on any that are not yet set up.
- **FR-016**: Users MUST be able to switch each channel on and off, and the choice MUST take effect
  on the next deadline that falls due.
- **FR-017**: Users MUST be able to send themselves a test on any configured channel and see whether
  it arrived, without touching any deadline or reminder.
- **FR-018**: The system MUST confirm with the user before removing a destination or switching off the
  last working channel.
- **FR-019**: The system MUST allow every channel to be switched off, and MUST say plainly on the
  screen that this means hearing nothing.

**Availability of a channel**

- **FR-020**: Every channel MUST be offered on every instance. Whether it works depends only on
  whether the account has set it up, and never on what the instance operator did, so hosted and self
  hosted show the same product with no configuration on the operator's part.
- **FR-021**: The system MUST NOT write a notification for a channel the account has not finished
  setting up.
- **FR-022**: Adding a channel to the product MUST NOT require changing the record of any notification
  already written, and an unknown stored channel value MUST render as unknown rather than failing.

**Showing what happened**

- **FR-023**: Every screen that names a channel MUST name every channel the product offers, in the
  reader's language.
- **FR-024**: Every user-facing string added by this feature MUST exist in every configured locale.

**The channels the product offers**

- **FR-025**: The system MUST offer, beyond in app and email, three outbound channels: Slack,
  Telegram and text message.
- **FR-026**: The set of channels MUST be open. Adding a further channel MUST touch that channel's own
  definition and its translated strings, and MUST NOT touch delivery, the daily summary, quiet hours,
  the preferences screen or any screen that names a channel.
- **FR-027**: A channel MUST declare for itself what it needs from the account before it can be
  switched on, whether it can confirm arrival, the longest message it accepts, and whether a wrong
  destination is refused loudly or accepted silently.
- **FR-028**: The system MUST shorten a message that exceeds a channel's limit at a sentence boundary,
  and MUST NOT refuse it or cut it mid word.
- **FR-029**: The system MUST distinguish a temporary refusal from a permanent one. A temporary
  refusal is retried within the configured maximum attempts; a permanent one abandons the notification
  at once with the reason recorded.

**The notification bar**

- **FR-030**: Every signed-in screen MUST carry a notification bar showing the number of unread in-app
  notifications, and no other count.
- **FR-031**: Users MUST be able to open the bar and read their notifications newest first, each
  showing its title, what it is about, when it was due and whether it has been read.
- **FR-032**: Users MUST be able to mark one notification read, to mark every notification read after
  confirming, and to reach the record a notification concerns from the notification itself.
- **FR-033**: Marking a notification read MUST NOT delete it. The bar itself deletes nothing, and a
  notification leaves only by passing the retention period of FR-047.
- **FR-034**: The bar MUST show a sentence, not an empty container, when there is nothing to read, and
  MUST say when the in-app channel is switched off rather than appear broken.
- **FR-035**: The bar MUST list at most a bounded number of notifications at once and MUST offer a way
  to reach the rest, so an account with thousands does not load them all.
- **FR-036**: A notification on the in-app channel MUST count as delivered the moment its record
  exists, MUST NOT be retried and MUST NOT be abandoned.
- **FR-037**: The bar MUST be operable by keyboard alone and MUST announce the count, the list and the
  read state to a screen reader, per Principle IX.

**Keeping notifications, and letting them go**

- **FR-047**: The system MUST remove a notification once it is older than a stated retention period,
  read or unread, so that neither what is stored nor what the bar shows grows without bound.
- **FR-048**: The retention period MUST be an explicit setting of the instance with a documented
  default, which is what Principle VIII requires before anything may be deleted at all.
- **FR-049**: The retention rule MUST be stated to the account, on the notification bar and wherever
  delivery history is shown, so that a missing older record is something the person was told about
  rather than something they discover.
- **FR-050**: Removing a notification MUST NOT touch the deadline it was about. A deadline keeps its
  own record of having been notified, so the fact that somebody was told survives the removal of the
  detail of how.
- **FR-051**: A screen showing delivery history for a period older than the retention window MUST say
  that the records are not kept that long, and MUST NOT imply that nothing was sent.

**Setting a channel up**

- **FR-038**: The account holder MUST supply both the credential and the destination for every
  outbound channel. The instance operator MUST NOT be required to configure anything, MUST NOT hold a
  shared credential, and MUST NOT pay for any message an account sends.
- **FR-039**: The preferences screen MUST carry, per channel, the steps the account has to take at the
  outside service before the channel can work, written for somebody who has never done it.
- **FR-040**: The system MUST let an account replace or remove its credential for a channel at any
  time, and removing it MUST switch the channel off.
- **FR-041**: The system MUST tell the account, on a channel that still works, when a credential stops
  being accepted by its service.

**Proving a destination**

- **FR-042**: A channel that accepts a wrong destination silently MUST stay switched off until the
  account has typed back a code the product sent to that destination, and the destination MUST record
  that it was proven. A successful send is NOT proof, because a provider accepts any well-formed
  number and bills for it. Text message is the only such channel today.
- **FR-043**: A channel that refuses a wrong destination loudly MAY be switched on as soon as it is
  set up. Slack and Telegram are such channels: an unknown conversation or an unknown room comes back
  as an error the account can be shown, so proving it in advance would add a step and prevent nothing.
- **FR-044**: Where proof is required, changing the destination MUST clear the proof and require it
  again. Changing only the credential MUST NOT, because the destination is unchanged.
- **FR-045**: A proof code MUST expire, MUST be refused after a small number of wrong attempts, and
  MUST be limited in how often it can be sent again, so that neither the account's money nor a
  stranger's phone can be spent by repetition.
- **FR-046**: A proof code MUST NOT appear in a log, an error message or the changelog, and MUST NOT
  be readable from the database in a form that lets it be replayed.

### Key Entities

- **Channel**: one way a notification can reach somebody. It has a stable identity stored on every
  notification, a name the reader sees in their language, what it needs from the account before it
  works, whether it can confirm arrival, the longest message it accepts, and whether a wrong
  destination is refused loudly or accepted silently.
- **Notification**: unchanged in meaning. One row per channel per deadline per person, carrying the
  title, body, language, when it was due, when it may go out, and what became of it.
- **Notification preference**: unchanged in meaning, one row per account, extended so that switching a
  channel on is not limited to the three the product named in 2026.
- **Channel destination**: where one account's messages go on one channel, together with the
  credential that account supplied for it. Belongs to exactly one account, may be absent or half
  filled in, holds its credential encrypted, and records whether it has been proven and when.
- **Proof code**: a short-lived secret sent to a destination and typed back to prove somebody is there.
  Belongs to one destination, expires, counts its wrong attempts, and is never readable afterwards.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Once a person holds whatever the outside service requires of them, switching the channel
  on, recording where it reaches them, proving it if proof is required and watching a test arrive takes
  under two minutes and never leaves the preferences screen. The time spent at the outside service is
  the service's, not the product's.
- **SC-002**: No channel can be switched on while nothing behind it can deliver. Every switched-on
  channel either delivers or shows the account a stated reason it did not.
- **SC-003**: Every notification, on every channel, ends in exactly one of three recorded states,
  delivered, abandoned with a reason, or still waiting, with no fourth state and none left silent.
- **SC-004**: A channel failing for an hour delays no notification on any other channel.
- **SC-005**: A self hosted instance whose operator configured nothing beyond mail offers all six
  channels, identically to a hosted one, and every one of them can be made to work by the account
  alone.
- **SC-006**: Adding one further channel later touches only that channel's own definition and the
  translated strings, and no delivery, summary or history logic.
- **SC-007**: Every string the feature shows exists in every configured locale, and the localization
  check passes.
- **SC-008**: Contact names reach a destination outside the account holder's sole control only after
  the account was shown a warning and confirmed it.
- **SC-009**: A person signed in with unread notifications sees the count without looking for it, and
  reaches what a notification is about in one gesture from the bar.
- **SC-010**: An account with ten thousand unread notifications opens the bar as quickly as an account
  with ten.
- **SC-014**: The number of stored notifications for an account stops growing once it reaches the
  retention period, whatever the account's age.
- **SC-015**: Nobody discovers the retention rule by finding a record missing. It is stated on the bar
  and on every screen that shows delivery history, before it removes anything.
- **SC-011**: Every one of the four new destinations can be switched on, configured, tested and
  switched off from the preferences screen alone, without the person going to look for documentation
  outside the product.
- **SC-012**: No credential an account supplies ever appears in a log, an error message, the changelog
  or a screen after it is saved.
- **SC-013**: No text message about a contact reaches a number whose owner did not type back a code,
  so a mistyped digit costs one wasted message and never a disclosure.

## Assumptions

- The account remains the unit of choice. Erica is mono-user, so an account is one person and one set
  of preferences, and no per-user or per-vault channel choice is introduced.
- One destination per channel per account. Two rooms, two numbers or two addresses on one channel are
  out of scope, and adding them later does not invalidate anything here.
- The existing quiet hours, delivery mode and digest rules apply unchanged to every channel. A channel
  does not get its own quiet hours.
- Retry counting and the maximum attempts stay where they are, on the notification row, and stay
  configurable per instance.
- The in-app channel becomes visible in this feature rather than a later one, through the
  notification bar of User Story 3.
- Push stays in the product and stays undeliverable on the day this ships. It is plumbed like every
  other channel so that it stops being a switch that can never deliver, and building what actually
  sends it belongs to the native applications of `spec/spec.md` §3.8. Nothing here blocks that.
- Which company carries the text messages is not decided here. Changing it later touches that one
  channel and nothing else, which is the point of FR-026.
- **Setup friction is accepted, and it is not small.** Under FR-038 a person who wants Telegram
  registers a bot with the service, copies a token, then starts a conversation with their own bot to
  learn its identifier. A person who wants text message opens an account with a provider and gives it
  a card. Most people will switch on the notification bar and email, some will finish Slack, and few
  will finish text message. The three outbound channels are therefore built for the people who want
  them rather than for everybody, and the notification bar of User Story 3 is what the ordinary
  account actually gets. This was chosen over a shared instance credential deliberately: no shared
  secret, no operator paying for somebody else's messages, and no channel that is missing on a self
  hosted instance.
- Slack and text message are channels the platform supports directly. Telegram is maintained by its
  community instead, so it carries a different long-term maintenance risk, and that risk is accepted.
- Three outside dependencies are therefore expected. The project rules require your approval before
  the plan names them, and answering Q1 as you did is read as that approval for these three channels
  and no others.
- No new top-level folder is created. Channels live in the existing application structure.
- The digest currently exists on email only. Producing one per channel is treated as a change of this
  feature, not as pre-existing behaviour.
- Locale coverage runs through the existing localization command, and no locale file is edited by
  hand.
- The retention period defaults to twelve months, long enough that the reminder history covers a full
  yearly cycle of birthdays and anniversaries, which is the case the history exists for. It is a
  setting, so an instance that wants longer or shorter says so.
- Removal runs on a schedule rather than on read, so opening the bar never triggers a deletion.
- The per-channel delivery detail is what the retention period removes. `reminder_occurrences` keeps
  its own record that a deadline was notified, and that record is not part of this retention policy.

## Dependencies

- The reminder occurrence and notification machinery of feature 013, which this extends and does not
  replace.
- **An amendment to `spec/spec.md` in the same pull request.** §14.2 must lose the claim that the
  product will never provide a channel, and §10.4 must stop presenting three channels as the list.
  Without it the feature contradicts the specification it is built against.
- The notification preferences screen, which does not exist. The action behind it does.
- Three outside services, each with its own credential, its own limits and its own failure vocabulary.
  Nothing is required of the instance operator, so no deployment lacks a channel any other has.
- Encrypted storage for per-account credentials, which is what FR-012 rests on.
