# Feature Specification: Admin account screen redesign

**Feature Branch**: `2026-11-09-admin-redesign`

**Created**: 2026-11-09

**Status**: Draft

**Input**: User description: "implement this design for the https://monica.test/admin/accounts/1 screen" (Claude Design project `b78dc791-8645-424e-b24c-635ab7aea4f3`, file `Admin Account.dc.html`). The shell around it is specification [106](../106-admin-redesign/spec.md).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - An administrator understands an account at a glance (Priority: P1)

An administrator opens one account from the account list. A trail leads back to the list. The top of the screen names the person, shows their address, and says in badges whether this is the administrator's own account, whether the person can sign in, and which plan the account is on. The facts sit in two panels side by side: the account (created, where it runs, what it holds, billing address) and the person (name, address and whether it is confirmed, how they sign in, two factor authentication, last signed in). The record of what administrators did to the account is one button away, at the top.

**Why this priority**: support starts by reading. Today the facts are in one long column and the state that matters most (can this person sign in) is a row among twelve.

**Independent Test**: open an account of a person who can sign in and one of a deactivated person, and check the trail, the header, the badges and both panels against the data.

**Acceptance Scenarios**:

1. **Given** Michael Scott's account, **When** the administrator opens it, **Then** the trail reads "Accounts" then "Michael Scott", the header shows his name, his address and the badges "Can sign in" and his plan, and "See the record for this account" leads to the record filtered to this account.
2. **Given** the administrator opens their own account, **When** the screen renders, **Then** a "Your own account" badge shows, and a note explains that they may change their own limits and access, that every change is written down against their name, and that none deletes anything.
3. **Given** a deactivated person, **When** the screen renders, **Then** the badge reads "Deactivated" instead of "Can sign in".
4. **Given** an account whose person was deleted, or which holds nobody, **When** the screen renders, **Then** the badge says so, every control that acts on the person is absent, and the existing deletion notice stays; the person panel keeps what is known about a deleted person, as today.

---

### User Story 2 - An administrator changes limits and access (Priority: P2)

Under the panels, "Limits and access" lists three rows, each with what is true now and, where support can change it, one button: storage (used against the limit, with a bar showing the share used, and "Change limit"), the plan (read only), and lifetime access ("Grant lifetime access" or "Withdraw lifetime access").

**Why this priority**: these are the changes support makes most. Grouping each with its current value removes the hunt through a row of five buttons.

**Independent Test**: change the storage limit and grant then withdraw lifetime access from the new rows, and check each change lands and is confirmed as today.

**Acceptance Scenarios**:

1. **Given** an account holding 40 MB of a 100 MB limit, **When** the screen renders, **Then** the storage row reads "40 MB of 100 MB" and its bar is announced as 40% of the limit used.
2. **Given** an account with no limit, **When** the screen renders, **Then** the storage row says there is no limit and draws no bar.
3. **Given** "Change limit" or a lifetime access button is activated, **When** the administrator confirms, **Then** the change is made exactly as it is today, with the same confirmation and the same wording of its consequence.
4. **Given** a self hosted instance, **When** the screen renders, **Then** the plan row is absent, the badge reads "Self-hosted", and lifetime access is still offered, as today.

---

### User Story 3 - An administrator switches flags and deactivates (Priority: P3)

A "Flags" section lists every declared flag with its state for this account and a "Switch on" or "Switch off" button, with a link to the flags for every account. Last, a red panel "Deactivate this account" says what deactivation does and offers it behind a confirmation. On the administrator's own account the button stays visible but cannot be used, and the reason is given beside it. A deactivated person's panel offers to let them sign in again instead.

**Why this priority**: rarer and heavier actions, already working today; the redesign gives them their own place.

**Independent Test**: switch one flag off and on, deactivate and reactivate another person, and open one's own account to see the refused button and its reason.

**Acceptance Scenarios**:

1. **Given** the `groups` flag is on for an account, **When** the administrator activates "Switch off", **Then** the flag is off for that account only, as today.
2. **Given** another person's active account, **When** the administrator activates "Deactivate account" and confirms, **Then** the person can no longer sign in, as today.
3. **Given** the administrator's own account, **When** the screen renders, **Then** "Deactivate account" is shown as unavailable, cannot be activated, and is described by "You cannot deactivate yourself. Another administrator has to do that, so nobody can lock themselves out."
4. **Given** a deactivated person, **When** the screen renders, **Then** the panel offers to reactivate them behind the existing confirmation.

---

### Edge Cases

- **A billable account**: the billing panel (plan details, renewal, the Stripe link) stays, under the two fact panels. The design, drawn on a free plan account, does not show it.
- **An account over its storage limit**: the storage row keeps today's sentence saying nothing was deleted and no new file is accepted.
- **No flag declared**: the Flags section is absent, as today.
- **A long address or flag name**: wraps rather than widening the page at 320 pixels.
- **A failed deactivation**: the error shows on the screen, as today.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The screen MUST open with a trail ("Accounts", then the account's title) that leads back to the account list, replacing "Back to accounts".
- **FR-002**: The header MUST show the person's initials, the account title, the person's address, and the badges of FR-003, and offer "See the record for this account".
- **FR-003**: The badges MUST be, in order: "Your own account" when it is the viewer's; the person's state ("Can sign in", "Deactivated", "Person deleted" or "Nobody on this account"); the access the account holds ("Free plan", "Lifetime access", "Granted by us" or "Subscription"), or "Self-hosted" on a self hosted instance.
- **FR-004**: On the viewer's own account, a note MUST say that they may change their own storage limit and lifetime access so an instance with one administrator is never blocked, that every change is written down against their name, and that none deletes anything.
- **FR-005**: "The account" panel MUST list Created, Where it runs, What it holds and Billing address; "The person" panel MUST list Name, Address with whether it is confirmed, How they sign in, Two factor authentication and Last signed in. The two MUST sit side by side when there is room.
- **FR-006**: "Limits and access" MUST list Storage (with its bar and "Change limit"), Plan (on a billable instance) and Lifetime access (with grant or withdraw), each row stating the current value.
- **FR-007**: The storage bar MUST carry a text alternative giving the share of the limit used.
- **FR-008**: "Flags" MUST list each declared flag in a fixed width face with an On or Off badge and a "Switch on" or "Switch off" button naming the flag for assistive technology, and link to the flags for every account.
- **FR-009**: A danger panel MUST offer deactivation for an active person and reactivation for a deactivated one, each behind the existing confirmation. Its line MUST say the person would no longer sign in and that their vaults and contacts stay, without assuming the person's gender.
- **FR-010**: On the viewer's own account, the deactivation button MUST stay on the screen, be marked unavailable, be impossible to activate, and point at its reason.
- **FR-011**: The panel "What has been done to this account" MUST be removed; its link moves to the header.
- **FR-012**: Every action MUST keep its current behaviour, confirmation, refusal and record entry; only where it sits changes.
- **FR-013**: Every new wording MUST be translated in every configured language, and the screen MUST meet WCAG 2.2 AA in both themes at 320 pixels and 200% zoom.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Whether the person can sign in, and whether the account is the viewer's own, is readable without scrolling at every width from 320 pixels up.
- **SC-002**: Each limit or access change is reached in one action from the row that shows its current value.
- **SC-003**: Every fact the current screen shows is still on the redesigned screen, and every action still works.
- **SC-004**: The screen passes a WCAG 2.2 AA check in both themes with no violation.

## Assumptions

- **Column width**: the design narrows this screen to a reading column; the admin shell stays as built in specification 106.
- **Plan badge**: names the plan the account holds today ("Free plan", or the full access kind), from the data the screen already reads.
- **Wording**: the design's "Her vaults" is replaced by a wording that does not assume the person's gender.
- **No new action**: nothing is added to what support can do; the API and the actions are untouched.
