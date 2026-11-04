# Feature Specification: Banner offering full access to free accounts

**Feature Branch**: `2026-11-01-subscription-banner` · **Created**: 2026-11-01 · **Status**: Draft

**Input**: "Add a banner inviting users without a subscription to sign up for one. The banner should be dismissible (markable as read) so that it no longer appears."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - A free account is told that full access exists (Priority: P1)

Somebody on the free plan signs in and sees a banner at the top of the screens they use every day.
One sentence asks them to subscribe now to lift the limits of the free plan, and a link leads to Billing.

**Why this priority**: it is the purpose of the feature. Today a free account learns that full
access exists only by opening Billing or by reaching a limit.

**Independent Test**: sign in as a free metered account, open the vault list, see the banner,
follow its link and land on Billing.

**Acceptance Scenarios**:

1. **Given** a metered account without full access that never dismissed the banner, **When** it
   opens a signed in screen other than those FR-002 leaves out, **Then** the banner shows above the
   screen's content.
2. **Given** the banner is shown, **When** the reader follows its link, **Then** Billing opens.
3. **Given** the banner is shown, **When** a screen reader reaches it, **Then** it announces a named
   region holding the sentence, the link and the dismiss control.

---

### User Story 2 - The reader dismisses the banner for good (Priority: P1)

The reader does not want the offer, or not now. They press the dismiss control, the screen comes
back without the banner, and it never returns, in any later session or on any other device.

**Why this priority**: the request asks for it; otherwise the banner sits on every screen for good.

**Independent Test**: dismiss the banner, sign out, sign in from another browser, and see no banner.

**Acceptance Scenarios**:

1. **Given** the banner is shown, **When** the reader presses the dismiss control, **Then** they
   stay on the same screen and the banner is gone.
2. **Given** the reader dismissed the banner, **When** they open any screen afterwards, from any
   device, **Then** the banner does not show.

---

### User Story 3 - A paying account never sees the offer (Priority: P2)

An account holding full access, however it holds it, never sees the banner, and an account that
takes full access stops seeing it as soon as the access is recorded.

**Why this priority**: a visible defect for somebody who pays, but not the main use of the feature.

**Independent Test**: sign in as a monthly subscriber, a lifetime holder and an account granted
access by an administrator; none sees the banner.

**Acceptance Scenarios**:

1. **Given** an account with a running subscription, a one time purchase or granted access, **When**
   it opens any screen, **Then** the banner does not show.
2. **Given** a free account that never dismissed the banner, **When** its full access is recorded,
   **Then** the next screen it opens shows no banner.

### Edge Cases

- Self hosted instance: nobody is metered, so nobody sees the banner.
- Hosted account with no limits: not metered, so no banner, since full access would change nothing.
- Subscription ending but still in its paid period: full access is still held, so no banner.
- Subscription ended, refunded or charged back: free again, so the banner shows unless dismissed.
- Back from checkout before Stripe confirms: no full access yet, so the banner may still show.
- Narrow screen: the sentence wraps and nothing scrolls sideways.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The system MUST show the banner to an account only when the instance meters it, it
  does not hold full access, and it has not dismissed the banner.
- **FR-002**: The banner MUST show on every signed in screen except Billing and the screens reached
  from it, the administration space, the marketplace, shared screens opened through a private link
  the welcome screen and the sign in screens.
- **FR-003**: The banner MUST hold one sentence asking the reader to subscribe now to lift the limits of the free plan,
  a link to Billing, and a control to dismiss it.
- **FR-004**: The banner MUST NOT name a price, a trial, a discount or a feature reserved to paying
  accounts, since there is no trial and full access unlocks no feature.
- **FR-005**: Dismissing MUST record the dismissal, ask for no confirmation since it destroys
  nothing, and return the reader to the screen they were on, without the banner.
- **FR-006**: A dismissal MUST be permanent, hold across sessions and devices, and MUST NOT be reset
  by any change of access.
- **FR-007**: The banner MUST be a named region whose link and control work with the keyboard alone,
  and the control MUST carry an accessible name saying what it does.
- **FR-008**: The banner's text MUST be translated in every configured locale.
- **FR-009**: The dismissal MUST be possible through the API, and the API MUST say whether and when
  it happened.

### Key Entities

- **Account**: its metering and full access decide the offer; its one user's dismissal is its own.
- **Banner dismissal**: the moment the user dismissed the offer, or nothing. There is no undo.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100% of free metered accounts that have not dismissed the banner see it on their first
  screen after signing in; 0% of accounts holding full access, unmetered or self hosted see it.
- **SC-002**: Dismissing takes one action, and the banner never shows again for that account.
- **SC-003**: Every part of the banner works with the keyboard alone, and an automated accessibility
  check of a screen showing it reports nothing new.

## Assumptions

- "Without a subscription" means without full access: a one time purchase and access granted by an
  administrator count as a subscription, since the constitution makes them unlock the same product.
- One rule on every deployment; a self hosted instance meets it for nobody.
- Dismissal is permanent and per account, with no setting to bring the banner back.
- Billing (spec 067) is unchanged and is where the reader subscribes.
