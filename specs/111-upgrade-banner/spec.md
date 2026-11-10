# Feature Specification: Upgrade strip under the top bar

**Feature Branch**: `2026-11-10-upgrade-banner`

**Created**: 2026-11-10

**Status**: Draft

**Input**: User description: "new branch. design a new banner to ask users to upgrade following design 3b" (Claude Design project `b78dc791-8645-424e-b24c-635ab7aea4f3`, `Upgrade Banner.dc.html`, option 3b, "Thin strip under the bar")

## Context

A free account on the hosted instance sees one banner today, above the content of every screen: an
information panel saying "Subscribe now to lift the limits of the free plan!", a link to the plans,
and a close button that hides it for good. It says nothing about which limits, or how close the
account is to them, so it gives nobody a reason to click.

Design 3b replaces it with a thin strip across the full width of the window, directly under the top
bar. From left to right it holds a "Free plan" badge, one meter for people and one for vaults, each
with its count written as "used / limit", and on the far right a single secondary button, "Upgrade
your account". A meter takes a warning tone when the account is close to that limit and a danger
tone when the limit is reached.

## Clarifications

### Session 2026-11-10

- Q: Design 3b draws no way to hide the strip. Does it keep the old banner's "hide for good"? → A: Yes. A quiet close button reuses the existing dismissal, its web route and its API endpoint, so users who hid the old banner never see the strip.
- Q: Does the strip show to every free account, or only near a limit? → A: Every free account, all the time, as 3b draws it.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - A free account sees how much of the free plan it uses (Priority: P1)

Somebody on the free plan opens any screen of the application and sees, under the top bar, how many
people and how many vaults they hold against what the free plan allows.

**Why this priority**: It is the banner the request asks for, and the reason it replaces the old one.

**Independent Test**: Sign in to a free account holding 25 of 30 people and 1 of 1 vault, open the
vault list, and read the strip.

**Acceptance Scenarios**:

1. **Given** a free account with 25 people out of 30 and 1 vault out of 1, **When** its user opens the vault list, **Then** a strip under the top bar shows "Free plan", "People" with "25 / 30", "Vaults" with "1 / 1", and "Upgrade your account".
2. **Given** the same account, **When** the user opens a vault screen or a settings screen, **Then** the same strip shows in the same place.
3. **Given** a screen reader user on that screen, **When** they reach the strip, **Then** it is announced as a region named "Free plan usage", and each meter is announced as a progress bar with its name, its value and its maximum.
4. **Given** the strip, **When** the user activates "Upgrade your account", **Then** they arrive on the billing screen where the plans are offered.

---

### User Story 2 - The meter warns before the limit and says when it is reached (Priority: P1)

The tone of each meter tells the reader whether they can keep adding, are getting close, or have
reached the limit, and the count says the same thing in words.

**Why this priority**: Without it the strip is the old banner with numbers on it. The tone is what
makes 3b say how close the account is.

**Independent Test**: Show the strip for counts below, close to and at each limit, and compare the
tone of each meter and its count.

**Acceptance Scenarios**:

1. **Given** a free account well below a limit, **When** the strip shows, **Then** that meter and its count use the neutral tone.
2. **Given** a free account at 80% or more of a limit but below it, **When** the strip shows, **Then** that meter and its count use the warning tone.
3. **Given** a free account at or above a limit, **When** the strip shows, **Then** that meter and its count use the danger tone, and the meter is full.
4. **Given** any of these states, **When** the reader cannot tell the colours apart, **Then** the written count still tells them how much is used out of how much.

---

### User Story 3 - The old banner is gone (Priority: P2)

Nobody sees both the old information panel and the new strip.

**Why this priority**: Two banners making the same offer would undo the point of redesigning one.

**Independent Test**: Open every screen that showed the old panel and check it is absent.

**Acceptance Scenarios**:

1. **Given** a free account, **When** its user opens a vault screen, the vault list or a settings screen, **Then** the old "Subscribe now to lift the limits of the free plan!" panel does not appear.
2. **Given** the strip, **When** it renders, **Then** it sits outside the content column, edge to edge under the top bar, and does not push the screen's heading inside a panel.

---

### User Story 4 - Hiding the strip (Priority: P2)

A user who does not want to see the strip hides it for good, the way the old banner was hidden. The
hiding belongs to that user, not to the account.

**Why this priority**: Design 3b draws no close button, but the banner it replaces could be hidden,
and taking that away from people who already used it would bring back a banner they chose to remove.

**Independent Test**: Hide the strip, then open other screens, sign out and back in.

**Acceptance Scenarios**:

1. **Given** the strip, **When** the user activates its close button, **Then** the strip disappears and stays hidden on every screen and in every later session for that user.
2. **Given** a user who hid the old banner before this change, **When** they open any screen, **Then** the strip does not show.
3. **Given** a hidden strip, **When** the user wants to see their usage, **Then** the billing screen still shows it.
4. **Given** the close button, **When** a screen reader reaches it, **Then** it is announced as "Hide this offer for good".
5. **Given** a client of the API, **When** it records the hiding through the existing endpoint, **Then** the strip is hidden for that user on the web as well.

---

### Edge Cases

- **Self hosted instance.** No account is on the free plan, so the strip never shows.
- **Full access.** An account holding full access, by purchase, subscription or grant, never sees the strip, and it disappears on the next screen once full access starts.
- **Full access ends.** When a subscription lapses or a payment is refunded, the account is back on the free plan and the strip shows again on the next screen.
- **Above a limit.** An account holding more than a limit allows (an administrator lowered it, or full access ended) shows the real count, for example "34 / 30", in the danger tone, with the meter full rather than overflowing.
- **Counted people.** The people count is the one the free plan limit is measured against: every vault of the account, archived vaults included, leaving out each vault's own record of the user and anybody still waiting to be filled in. It must match the count the billing screen shows.
- **A limit of zero or no vault yet.** A brand new account with no vault shows "0 / 1" in the neutral tone. A limit an administrator set to zero shows the danger tone and a full meter, and never divides by zero.
- **Narrow screen.** On a phone the strip stays on its own row under the bar. The meters may wrap or shorten, but the counts and the button stay readable and reachable, and nothing scrolls horizontally.
- **Print.** The strip does not print.
- **Translation.** "Free plan", "People", "Vaults", "Free plan usage" and "Upgrade your account" are translated in every configured locale. The count "25 / 30" carries no word and needs no plural form.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The strip MUST show on every screen that showed the old full access banner, for every user of an account that is on the free plan of the hosted instance.
- **FR-002**: The strip MUST NOT show on a self hosted instance, nor for an account that holds full access.
- **FR-003**: The strip MUST sit directly under the top bar, across the full width of the window, above the content of the screen.
- **FR-004**: The strip MUST show, in this order: a "Free plan" badge, a meter for people, a meter for vaults, and a single "Upgrade your account" link styled as a secondary button, placed at the far right.
- **FR-005**: Each meter MUST show its label, a bar filled in proportion to what is used, and the count written as "used / limit".
- **FR-006**: The people count and limit MUST be the ones the free plan enforces when somebody adds a person; the vault count and limit MUST be the ones it enforces when somebody adds a vault.
- **FR-007**: A meter MUST use the neutral tone below 80% of its limit, the warning tone from 80% up to the limit, and the danger tone at or above the limit. The bar MUST NOT fill past 100%.
- **FR-008**: The strip MUST be a region named "Free plan usage", and each meter MUST expose itself as a progress bar with its name, its current value, its minimum and its maximum.
- **FR-009**: The tone MUST NOT be the only way the state is given: the written count is always shown next to the meter.
- **FR-010**: "Upgrade your account" MUST lead to the billing screen, where the plans are offered.
- **FR-011**: The strip's button MUST stay secondary, so that the primary action of the screen under it remains the only primary button.
- **FR-012**: The old full access banner MUST be removed from every screen.
- **FR-013**: The strip MUST NOT print.
- **FR-014**: The strip MUST show to every free account whatever its usage, including when every meter is in the neutral tone.
- **FR-015**: The strip MUST carry a quiet close button, after "Upgrade your account", that hides it for good for the user who activates it. It MUST reuse the existing record of that choice, so a user who hid the old banner never sees the strip, and the existing web route and API endpoint keep working unchanged.
- **FR-016**: A hidden strip MUST stay hidden whatever the account's usage later becomes.

### Key Entities

- **Account**: whether it is on the free plan, how many people and vaults it holds, and the limit for each. All of this exists today.
- **User**: the person seeing the strip. Each user carries the moment they hid the banner, if they did, and the strip reads that same moment.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A free user can tell, from any screen and without opening another one, how many people and vaults they hold and how many the free plan allows.
- **SC-002**: The counts on the strip match the counts on the billing screen for 100% of accounts.
- **SC-003**: Every screen that shows the strip shows exactly one banner about the plan, and exactly one primary button.
- **SC-004**: The strip passes WCAG 2.2 AA: its contrast in both themes, its names and values for a screen reader, and its button reachable and visibly focused from the keyboard.
- **SC-005**: On a 375 pixel wide screen the strip causes no horizontal scrolling and its button stays reachable.

## Assumptions

- "People" is the word the design uses for contacts, and the vault list already counts people this way, so the strip uses it.
- The warning threshold is 80% of a limit, which with the default limit of 30 people starts at 24. The design's 25 of 30 sits in the warning tone and its 1 of 1 vault in the danger tone, which this threshold reproduces.
- File storage, the third free plan limit, stays on the billing screen and is not on the strip, as the design draws only people and vaults.
- The strip is built on whichever top bar is current when it ships. Specification 110 replaces the top bar on another branch, and the strip sits under the bar either way.
- The strip shows on the same screens as the old banner: the vault list, the vault screens and the settings screens. The marketplace and the administration space are unchanged.
- The wording of the old banner is removed from every locale once nothing uses it. The close button keeps its existing label, "Hide this offer for good".
- The close button is an addition to design 3b, decided in the clarification session. It is quiet (no border, no fill) so the upgrade button stays the strip's only visible action.
