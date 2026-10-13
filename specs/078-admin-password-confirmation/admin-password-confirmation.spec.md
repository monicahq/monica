# Feature Specification: Password confirmation on the admin screens

**Feature Branch**: `2026-10-13-admin-password-confirmation`
**Created**: 2026-10-13
**Status**: Draft
**Input**: "Use password confirmation component on admin panels"

## Context

The account security and profile screens ask for a credential in a small card that opens over the page. The administrator submits nothing until the credential is accepted, then the original form goes through unchanged. Nothing they typed is lost.

The admin space asks for the same credential in front of its most sensitive changes: the instance configuration (settings, secrets, removed values, the Telegram registration and webhook secret) and switching a flag for every account. It does this by sending the administrator away to a separate full sign in screen and back. The form they were filling in is left behind.

This feature puts the admin space on the card the rest of the application already uses.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Confirm in place when changing the instance configuration (Priority: P1)

An administrator changes a setting on the configuration screen, clears a secret, puts a value back, removes a value nobody reads any more, registers the Telegram webhook or rotates its secret. When the session has not confirmed a credential recently, the card opens over the configuration screen and asks for the password or a passkey. Once accepted, the change they asked for is saved, with the value they typed, and they stay on the configuration screen.

**Why this priority**: the configuration screen is where a detour costs the most. The administrator has typed a value, and the mail transport set here decides whether sign in and password reset links reach anybody.

**Independent Test**: as an administrator with a password and no recent confirmation, change one setting. The card opens, the right password saves the typed value, and the screen reports the change.

**Acceptance Scenarios**:

1. **Given** an administrator with no recent confirmation, **When** they save a new value for a setting, **Then** the card opens over the configuration screen and nothing is saved yet.
2. **Given** the card is open, **When** they enter their correct password, **Then** the card closes and the value they typed is saved.
3. **Given** the card is open, **When** they enter a wrong password, **Then** the card stays open, says the password is not right, and nothing is saved.
4. **Given** the card is open, **When** they cancel or press Escape, **Then** the card closes, nothing is saved, and the value they typed is still in the field.
5. **Given** an administrator who confirmed a credential a moment ago, **When** they save another setting, **Then** it is saved without the card opening.
6. **Given** a setting that warns before it takes something away, **When** they save it, **Then** the existing warning still comes first and the card opens only once they save again.

### User Story 2 - Confirm in place when switching a flag for every account (Priority: P2)

An administrator activates or deactivates a flag for every account. The screen already asks them to confirm the consequence. After they accept it, the credential card opens when needed, and the switch happens once the credential is accepted.

**Why this priority**: the action is already gated. Only the way it asks changes, and there is no typed value to lose.

**Independent Test**: as an administrator with no recent confirmation, activate a flag for every account. The warning appears, then the card, and the flag is on once the password is accepted.

**Acceptance Scenarios**:

1. **Given** no recent confirmation, **When** the administrator accepts the warning to activate a flag for every account, **Then** the card opens and the flag is still off.
2. **Given** the card is open after that warning, **When** they confirm with a passkey, **Then** the flag is on for every account.
3. **Given** the card is open after that warning, **When** they cancel, **Then** the flag is unchanged.

### User Story 3 - An administrator who holds neither credential is told why (Priority: P3)

An administrator who signs in without a password and without a usable passkey, for example through single sign on, reaches a gated change. The card opens and says there is nothing to confirm with, the same way the security screen does.

**Why this priority**: rare, but without it the administrator meets a button that silently does nothing.

**Independent Test**: as an administrator with no password and no passkey, try to save a setting and read the explanation on the card.

**Acceptance Scenarios**:

1. **Given** an administrator with neither a password nor a passkey, **When** they try a gated change, **Then** the card says the account holds nothing to confirm with and offers no confirm button.

## Edge Cases

- **Confirmation expires between page load and submit.** The card checks again at the moment of submit, not when the page loaded, so an expired confirmation always opens it.
- **Too many wrong passwords.** The card says to wait a minute, as it does on the security screen, and nothing is saved.
- **Passkeys unavailable on this instance.** The passkey button is not offered. A password still works.
- **Two gated forms on one screen.** The card submits the form that opened it and no other.
- **A request reaches a gated change without a recent confirmation anyway** (JavaScript off, a stale tab, a scripted request). The server still refuses it. The card is a convenience in front of the rule, never the rule.
- **The configuration screen's own warnings.** A setting that warns before taking something away keeps its two step save; the credential is asked after the warning, never before it.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Every admin action that already requires a recent credential confirmation MUST ask for it in the card over the current screen, not by sending the administrator to another screen.
- **FR-002**: After the credential is accepted, the original submission MUST go through with exactly the values the administrator entered.
- **FR-003**: When the session already holds a recent confirmation, a gated admin action MUST go through without opening the card.
- **FR-004**: Cancelling the card MUST leave the record unchanged and keep what the administrator typed on the screen.
- **FR-005**: The card MUST offer a password, a passkey, or both, according to what the administrator holds and what the instance supports, and say so when they hold neither.
- **FR-006**: The card MUST describe the admin action in words that fit it: a change to how the instance behaves, not a change to how the administrator signs in.
- **FR-007**: An existing warning before a gated action (a flag for every account, a setting that takes something away, clearing or removing a value) MUST still appear first, with the card after it.
- **FR-008**: The server MUST keep refusing a gated admin action that arrives without a recent confirmation, whatever the screen does.
- **FR-009**: The admin actions that are gated MUST stay the same set as today: changing, clearing and removing configuration values, registering the Telegram webhook, rotating its secret, and switching a flag for every account.
- **FR-010**: The card MUST be usable by keyboard and screen reader to the same standard as on the security screen: named as a dialog, focus held inside it while open, Escape closes it, and the outcome announced.

### Key Entities

- **Recent credential confirmation**: a fact held by the administrator's session saying they proved a credential a short while ago. It decides whether the card opens. It already exists and this feature does not change it.
- **Gated admin action**: one of the admin submissions listed in FR-009.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: On every gated admin action, an administrator without a recent confirmation completes the change without leaving the screen they started on: 7 of 7 actions.
- **SC-002**: A value typed into a configuration setting is saved as typed after confirmation in 100% of attempts.
- **SC-003**: A gated admin request sent without a recent confirmation is refused in 100% of cases.
- **SC-004**: An administrator who confirmed once makes a second gated change within the confirmation window without being asked again.

## Assumptions

- **Scope is the actions gated today.** The request names the component, not new gates. Granting or removing an administrator, deactivating an account, granting lifetime access, changing a storage limit and switching a flag for one account stay ungated. Extending the gate is a separate decision.
- **The full confirmation screen stays.** It remains where the server sends a request that skipped the card, and it serves flows outside the admin space.
- **The card's wording is a variant of the existing one**, chosen per screen, rather than a second card. The security and profile screens keep today's text.
- **Today's detour loses the typed value.** This follows from the server redirecting a form submission to a separate screen and back to an address that only accepts that submission. It was not reproduced in a browser while writing this spec.

## Verbatim Constraints

- The existing component is `x-modal.confirm-password`, opened with the `confirm-password` window event. Reuse it; do not build a second one.
