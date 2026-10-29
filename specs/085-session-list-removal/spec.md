# Feature Specification: List every session of an account and remove any of them

**Feature Branch**: `claude/speckit-session-list-removal-d142fc`
**Created**: 2026-10-26
**Status**: Draft
**Input**: User description: "list all sessions and allow removing them"

## Context

A session is one browser or device where the account is signed in. Since 072 every way in keeps a browser signed in for the remembered duration, 400 days by default, so a forgotten device stays signed in for over a year. Nothing shows a person where their account is signed in, and nothing ends one device's access short of changing the password, which signs out every other device at once. 072 left a screen listing signed in devices out of scope. This is that screen.

The instance already keeps, for every session, which account it belongs to, its network address, what browser and system it is, and when it was last used.

**Out of scope**: the sessions of other accounts, which an administrator might want to see; API keys and connected assistants, which the screen already lists and removes; a history of past sign ins; remembering per device and the API (see Assumptions).

## Clarifications

### Session 2026-10-26

- Q: Whose sessions does the screen list: the signed in person's own, or every session on the instance for an administrator? → A: The signed in person's own sessions, on Security and access.
- Q: When a session is removed, how far does the loss of remembered access reach? → A: Every device, the one in hand included, as signing out does today. Live sessions that were not removed carry on until they run out, and each then asks to sign in.
- Q: Should the screen also offer to end every other session at once? → A: Yes, decided 2026-10-29: one control signs out every device but the one in hand, behind the account password (User Story 3).
- Q: Should listing and ending sessions ship through the API in this feature? → A: No. Screen only; the logic sits in actions so an endpoint later is transport. This is a deliberate exception to Constitution Principle X, decided by the maintainer on 2026-10-29 and stated in the pull request.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - See where the account is signed in (Priority: P1)

Pam opens Security and access and sees every browser or device where her account is signed in: what it is, where it came from, when it was last used, and which one is the device she is on.
**Why this priority**: without the list there is nothing to remove, and an unknown device in it is the alarm the rest of the feature answers.
**Independent Test**: sign in on two browsers, open the screen on one, check both are listed, the one in hand is marked, and the other shows when it was last used.

1. **Given** an account signed in on two browsers, **When** the screen opens on one, **Then** both are listed, and the one in hand is marked as this device and comes first.
2. **Given** a session whose browser cannot be told, **When** it is listed, **Then** it is named an unknown device, not left blank.
3. **Given** a session that ran out, **When** the screen opens, **Then** it is not listed.
4. **Given** another account signed in on the same instance, **When** the screen opens, **Then** none of that account's sessions appear.

### User Story 2 - Remove a session (Priority: P1)

Pam sees a browser she does not recognise. She removes it, confirms her password or passkey as for every other change on this screen, accepts the warning, and that browser is signed out at its next request and cannot come back on its own.
**Why this priority**: it is the request.
**Independent Test**: sign in on two browsers, remove the second from the first, make a request on the second, check it is asked to sign in and is not signed back in silently.

1. **Given** two sessions, **When** one is removed and confirmed, **Then** it leaves the list, that device is sent to sign in at its next request, and the device in hand stays signed in.
2. **Given** a removed device that was remembered, **When** it comes back, **Then** it is asked to sign in rather than signed back in silently.
3. **Given** the warning is shown, **When** the person cancels, **Then** nothing changes.
4. **Given** the session in hand, **When** it is listed, **Then** no removal is offered on it; signing out is the way to end it.
5. **Given** a session of another account, **When** its removal is attempted, **Then** it is not found and nothing changes.
6. **Given** a password or passkey not confirmed recently, **When** the person removes a session, **Then** they are asked to confirm first, as for the other changes on this screen.

### User Story 3 - Sign out everywhere else (Priority: P2)

Pam does not know which device is the stranger. She signs out every device but the one she is on at once, typing her password in the warning.
**Independent Test**: sign in on two browsers, sign out everywhere else from the first, make a request on the second, check it is asked to sign in.

1. **Given** sessions on three devices, **When** the person signs out everywhere else with their password, **Then** the two others leave the list and are asked to sign in at their next request, remembered or not, and the device in hand stays signed in and remembered.
2. **Given** a wrong password, **When** it is submitted, **Then** nothing changes and the person is told.
3. **Given** an account with no password, **When** the screen opens, **Then** the control is not offered; removing devices one at a time still is.

### Edge Cases

- A browser that typed its password and not yet its two-factor code is not signed in, and is not listed.
- Removing a session ends remembered access on every device, the one in hand included, since the account holds one remembered credential for all of them (072). Live sessions that were not removed carry on until they run out, and each then asks to sign in.
- A removed device signs in again with its password: it is listed again, as a new session. Nothing bars a device.
- Two removals of one session (two tabs, a double click): the second finds it already gone and changes nothing.
- A removal changes nothing a device had saved; only its access ends.
- The instance keeps sessions somewhere they cannot be listed: the section says so and offers nothing.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Security and access MUST list every live session of the account: what the device is, in words a person recognises; its network address; when it was last used; and which is the device in hand.
- **FR-002**: The device in hand MUST be marked, listed first, and offered no removal.
- **FR-003**: Sessions that ran out MUST NOT be listed. Another account's sessions MUST NOT be listed nor reachable.
- **FR-004**: Every other session MUST offer a removal, which MUST warn first, naming the device and saying that every device of the account, this one included, will ask to sign in again once its session runs out.
- **FR-005**: Removing a session MUST sign that device out at its next request and MUST stop its remembered access signing it back in.
- **FR-006**: Removing one session MUST end remembered access on every device, the one in hand included, as signing out does today; live sessions that were not removed carry on until they run out, and the device in hand MUST stay signed in.
- **FR-007**: Removing MUST sit behind the same password or passkey confirmation as the other changes on the screen.
- **FR-008**: Every removal MUST be written in the account's journal as a session ended, naming the device; signing out everywhere else is also written once as such.
- **FR-009**: When the instance keeps sessions where they cannot be listed, the section MUST say so and offer nothing.
- **FR-010**: Every new phrase MUST be translated in every configured locale.
- **FR-011**: Security and access MUST offer to sign out every device but the one in hand at once, behind a warning and the account password typed in it; it ends every other session and remembered sign in, and the device in hand keeps its own.
- **FR-012**: That control MUST be offered only to an account with a password, since the sign out works by renewing it; a passkey alone cannot confirm it.

### Key Entities

- **Session**: one browser or device where the account is signed in. It belongs to one account, carries where it came from, what it is and when it was last used, and ends when it runs out, when the person signs out on it, or when it is removed. It exists today; nothing new is recorded.

## Success Criteria *(mandatory)*

- **SC-001**: A person signed in on two devices sees both listed on one screen and can tell which is the device in hand.
- **SC-002**: A session is removed in three actions at most: the removal, the password or passkey confirmation when asked, and the warning.
- **SC-003**: A removed device is asked to sign in at its very next request, with no exception for a remembered one.
- **SC-004**: The device that removes a session is never signed out by it.
- **SC-005**: No session of another account is ever listed or ended.

## Assumptions

- A session is a browser sign in. API keys and connected assistants are not sessions and keep their own lists on the screen.
- The instance already keeps each session's account, address, browser and last use; the feature records nothing new.
- One remembered credential per account stays, as 072 decided; remembering per device is not in this feature.
- No API endpoint ships in this feature, as an exception to Constitution Principle X (see Clarifications): a browser sign in is not the API's to end. The logic sits in two actions, so an endpoint later is transport and nothing more.
