# Feature Specification: Remembering Every Sign In

**Feature Branch**: `072-remember-every-sign-in`

**Created**: 2026-10-08

**Status**: Draft

**Input**: User description: "add remember cookie for all logins"

## Clarifications

### Session 2026-10-08

- Q: Is remembering offered as a choice on every way in, or always applied? → A: Always applied, with no choice, for a passkey, a link by email, an outside provider and a new account. The password form keeps its choice, ticked by default.
- Q: Should changing a password end remembered access on other devices? → A: Yes. Changing, removing or resetting a password ends it.
- Q: Signing out on one device ends remembered access on every device, since an account holds one remembered credential. Should signing out become per device? → A: No. Signing out keeps its current reach, and this specification records it as a decision.
- Q: How are the other devices' live sessions ended? → A: By the framework's own session check, which compares the password each session started with. It cannot see an account without a password, so removing a password ends remembered access elsewhere but leaves a live session running for as long as it is used. Accepted.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Every way in keeps somebody signed in (Priority: P1)

Somebody who signs in with a passkey, a link sent by email or an outside provider is signed out today
when their session runs out, about two hours after they stop using the application, while somebody who
typed a password could ask to be remembered. After this change every way in keeps somebody signed in
on that browser for the remembered duration the instance sets, without asking.

**Why this priority**: it is the request. Choosing a passkey over a password currently costs a shorter stay.

**Independent Test**: sign in with a passkey, a link by email and a provider, wait for the session to
expire, and confirm each browser is still signed in.

**Acceptance Scenarios**:

1. **Given** a person with a passkey, **When** they sign in with it, **Then** they stay signed in on that browser for the remembered duration.
2. **Given** a person who asked for a link by email, **When** they open the link, **Then** the browser that opened it stays signed in for the remembered duration.
3. **Given** a person who connected an outside provider, **When** they sign in through it, **Then** they stay signed in for the remembered duration.
4. **Given** a person signing in with a link by email or a provider who also has two factor authentication (a passkey asks for no code), **When** they complete the code challenge, **Then** they stay signed in for the remembered duration.
5. **Given** a visitor creating an account, with a password, with a passkey or through an outside provider, **When** the account is created and they are signed in, **Then** they stay signed in for the remembered duration.
6. **Given** a person whose account has no password, **When** they come back after their session expired, **Then** they are still signed in.

### User Story 2 - The password form proposes to remember, ticked by default (Priority: P1)

The password form keeps its "Remember me for N days" choice, and the box is now ticked when the screen
opens. Somebody on a shared computer unticks it and is signed out when the session runs out, as today.

**Why this priority**: it is the one way in where the person keeps a say, and its default changes for everybody.

**Independent Test**: confirm the box is ticked on arrival, sign in once ticked and once unticked, and
compare the two browsers after the session expires.

**Acceptance Scenarios**:

1. **Given** the sign in screen, **When** it opens, **Then** the remember box is ticked.
2. **Given** the box is ticked, **When** the person signs in with their password, **Then** they stay signed in for the remembered duration.
3. **Given** the box is unticked, **When** the person signs in with their password, **Then** they are signed out when the session runs out.
4. **Given** the box is ticked and the person has two factor authentication, **When** they complete the code challenge, **Then** they stay signed in for the remembered duration and no longer.
5. **Given** the person unticked the box and the password was wrong, **When** the screen comes back with the error, **Then** the box is still unticked.

### User Story 3 - Changing the password signs out the other devices (Priority: P2)

Somebody who suspects their account is reached by somebody else changes their password. Today the
remembered devices lose their access, but a session still open on another device carries on for as
long as it is used, so the change does not lock anybody out. After this change a new password signs
out every other device, and the device that made the change stays signed in and remembered.

**Why this priority**: remembering every sign in makes a forgotten device last much longer, and the
person needs a way to end that.

**Independent Test**: sign in on two browsers, change the password on the first, and confirm the
second is signed out at its next request while the first carries on.

**Acceptance Scenarios**:

1. **Given** a person signed in on two devices, **When** they change their password on one, **Then** the other is signed out at its next request and the one that made the change stays signed in and remembered.
2. **Given** a person remembered on two devices, **When** they remove their password on one, **Then** the other loses its remembered access, and the one that made the change stays signed in and remembered.
3. **Given** a person signed in on several devices, **When** they reset a forgotten password, **Then** every device is signed out at its next request, since resetting signs nobody in.

### Edge Cases

- The person unticks the box, then signs in with a passkey or a provider from the same screen. They are remembered: the box belongs to the password form only.
- The instance changes the remembered duration. A browser already remembered keeps its end date; the new duration applies from the next sign in.
- The person signs out on one device. Remembered access ends on every device, each signed out when its current session runs out. This is today's behaviour and it stays, by decision.
- The person removes their password while another device has a session open. That session continues while it is used, and stops at its next expiry. Accepted in Clarifications.
- A person without a password sets one, in settings or through a forgotten password link. Remembered access on other devices ends; sessions open there continue, as for a removal.
- A link by email is opened on another device than the one that asked for it. The device that opened it is remembered.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Signing in with a passkey MUST remember the browser for the remembered duration, with no choice offered.
- **FR-002**: Signing in with a link by email MUST remember the browser that opened it for the remembered duration, with no choice offered.
- **FR-003**: Signing in through an outside provider MUST remember the browser for the remembered duration, with no choice offered.
- **FR-004**: Creating an account, with a password, with a passkey or through an outside provider the instance has not seen before, MUST remember the browser for the remembered duration.
- **FR-005**: A remembered browser MUST be recognised whether or not the account has a password.
- **FR-006**: The password form MUST keep its remember choice, ticked when the screen first opens, and MUST keep the person's choice when it comes back with an error.
- **FR-007**: When two factor authentication follows any way in, the remembered state decided before the challenge MUST apply once the code is accepted.
- **FR-008**: Every remembered sign in MUST last the duration the instance sets, whatever the way in. Today a password sign in followed by a two factor challenge is remembered for the framework's 400 days, whatever the instance sets.
- **FR-009**: Changing the password MUST sign out every other device at its next request, remembered or not, and the device that made the change MUST stay signed in and remembered.
- **FR-010**: Removing the password MUST end remembered access on every other device, and the device that made the change MUST stay signed in and remembered.
- **FR-011**: Resetting a forgotten password of an account that had one MUST sign out every device at its next request; for an account that had none, it behaves as setting a first password (Edge Cases).
- **FR-012**: Signing out MUST keep its current reach: remembered access ends on every device of the account. The remembered duration is the existing instance setting, 400 days by default.
- **FR-013**: Signing in through the API, and the sign in links that exist only outside production, MUST be left unchanged.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A person who signs in by any of the five ways in (password with the box ticked, passkey, link by email, outside provider, new account) is still signed in when they come back after their session expired and before the remembered duration ends.
- **SC-002**: No remembered sign in outlasts the instance's remembered duration, whatever the way in.
- **SC-003**: After a password change, every other device of the person is signed out on its next request, with no exception.
- **SC-004**: A person who unticks the box on the password form is never remembered.

## Assumptions

- The remember box keeps its current wording. Only its initial state changes.
- Taking away a passkey, disconnecting a provider or turning on two factor authentication signs no other device out.
- No other screen of the sign in flow gains a control. A screen listing signed in devices is out of scope.
