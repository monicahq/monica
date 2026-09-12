# Feature Specification: Prove a credential before a sensitive action

**Feature Branch**: `2026-09-11-sensitive-action-verification`

**Created**: 2026-09-11

**Status**: Draft

**Input**: User description: "verify password or passkey on sensitive actions"

## Overview

A signed in session can today do everything the account holder can do, with nothing asked a second time. Somebody who finds an unlocked screen, or who takes a session over, can remove the password, switch two factor authentication off, mint an API key that outlives the session, change the email address every recovery route points at, or delete the account. None of those asks for a credential.

This puts one step in front of them. Before the product carries such an action out, it asks the person at the screen to prove they are the account holder, with the password they sign in with or with a passkey they hold, whichever the account has. A proof lasts fifteen minutes, so a run of changes on the security screen is not a run of prompts. Nothing else about the actions changes.

## Clarifications

### Session 2026-09-11

- Q: Which actions count as sensitive? → A: every action on the security screen (set, change or remove the password, switch two factor authentication on or off, regenerate recovery codes, add, rename or delete a passkey, create or delete an API key), plus changing the email address and deleting the account. Deleting a vault or a record is not one of them and keeps the confirmation it has today.
- Q: How long does a proof last, and what does it cover? → A: fifteen minutes, covering every sensitive action taken in that time.
- Q: What does an account with no password give before adding a passkey? → A: nothing. A passkey is all it could give, and the lost device is exactly when a new one is needed. The sign up flow lands on that screen before any password exists, so it would otherwise be shut too.
- Q: A taken over session reads the recovery codes off the security screen without giving anything. Do they stay there? → A: no. The codes are shown once, on the screen that follows their creation, and are never readable again. The security screen says how many are left and offers to replace them.
- Q: Does a token holder prove anything before a sensitive action? → A: no. The sensitive actions stay available over the JSON API, and the token is the proof: it is named, listed, revocable and journalled, and it is presented on every request. The step is a property of the interactive session.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - An action that changes how somebody signs in asks first (Priority: P1)

The account holder opens the security screen and switches two factor authentication off. Before anything changes, the product asks for their password. They type it, the setting changes, and the screen says so. Somebody sitting at the same screen without the password gets no further.

**Why this priority**: it is the request, and it is the case that closes the takeover. An action that weakens the way in must cost a credential.

**Independent Test**: sign in, submit a sensitive action, and confirm nothing changed until the credential was given. Submit the same request directly, without the prompt, and confirm it is refused.

**Acceptance Scenarios**:

1. **Given** a signed in account holder with a password, **When** they submit a sensitive action, **Then** they are asked for the password and the action is held until it is given.
2. **Given** the prompt is showing, **When** the right password is given, **Then** the action is carried out and they land back where they were, with the usual confirmation.
3. **Given** the prompt is showing, **When** a wrong password is given, **Then** the action is not carried out, the message says the credential was wrong and nothing more, and repeated attempts are rate limited.
4. **Given** a signed in account holder, **When** the request for a sensitive action arrives with no live proof, **Then** it is refused whatever route it came through.

---

### User Story 2 - A passkey holder proves it with the passkey (Priority: P1)

An account holder who gave their password up signs in with a passkey. When they reach a sensitive action, the product asks them for a passkey, not for a password they no longer have. An account holding both is offered both and picks either.

**Why this priority**: the product already lets somebody live without a password. A step that only accepts a password would shut them out of their own security screen.

**Independent Test**: remove the password, hold a passkey, reach a sensitive action, and confirm the prompt asks for the passkey alone and that satisfying it carries the action out.

**Acceptance Scenarios**:

1. **Given** an account with no password and at least one passkey, **When** a sensitive action is submitted, **Then** the prompt asks for a passkey and does not ask for a password.
2. **Given** an account with both a password and a passkey, **When** a sensitive action is submitted, **Then** either credential satisfies the prompt on its own.
3. **Given** an account with neither, **When** a sensitive action is submitted, **Then** it is refused, the reason is said plainly, and the way forward is to set a password or add a passkey.

---

### User Story 3 - One proof covers a short run of changes (Priority: P2)

Somebody tidying their security settings renames a passkey, deletes an old one and creates an API key. They prove themselves once, at the first of the three.

**Why this priority**: a prompt on every action is the thing that trains people to type their password without reading it, which is the habit this feature exists to protect.

**Independent Test**: prove once, carry out a second sensitive action inside the fifteen minutes with no prompt, wait past them, and confirm the third asks again.

**Acceptance Scenarios**:

1. **Given** a proof given a moment ago, **When** another sensitive action is submitted, **Then** it is carried out with no second prompt.
2. **Given** a proof more than fifteen minutes old, **When** another sensitive action is submitted, **Then** the prompt appears again.
3. **Given** a proof given in one session, **When** the account holder signs out and back in, **Then** the proof is gone and the next sensitive action asks again.

### Edge Cases

- A passkey prompt the browser or the device refuses: an account that also has a password falls back to it, and an account that does not is told what happened and can try again.
- A sensitive action submitted from a screen that updates in place rather than reloading: the prompt reaches the person all the same, and the action resumes where it left off.
- Two sensitive actions from two tabs of one session: the single proof covers both, and neither prompt is left stranded. The same person on the web and on the API at once: the web session is asked, the API request is not, and neither waits on the other.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The product MUST refuse a sensitive action from a signed in session that holds no live proof of identity, and MUST ask for one before carrying the action out.
- **FR-002**: The sensitive actions are: setting, changing and removing the password; switching two factor authentication on and off; regenerating recovery codes; adding, renaming and deleting a passkey; creating and deleting an API key; changing the email address; and deleting the account. An account holding no password is the exception for its passkeys: it may add, rename and delete them with nothing asked, because a passkey is the only thing it could give and that passkey may be on the device it lost. No other action asks for a proof, and deleting a vault or a record keeps the confirmation it has today.
- **FR-003**: A proof MUST be the account's password or a passkey the account holds, and nothing else. A two factor code alone, a confirmation typed into a box and a link sent by email are not proofs. The current password a password change already asks for, and the address an account deletion already asks to be typed, both stay, and the proof is asked in addition.
- **FR-004**: An account holding both credentials MUST be offered both and MUST be satisfied by either on its own.
- **FR-005**: An account holding neither MUST be refused the action, told why, and pointed at setting a password or adding a passkey.
- **FR-006**: A proof MUST be held against the session rather than the account, MUST stay live for fifteen minutes, and MUST cover every sensitive action taken in that time. It MUST end when the session ends, and when any credential of the account is changed or removed, whichever one the proof was given with.
- **FR-007**: A refused proof MUST leave the action undone, MUST say only that the credential was wrong, and MUST be rate limited so the prompt cannot be used to try credentials.
- **FR-008**: The prompt MUST NOT be bypassable. A request that reaches a sensitive action with no live proof is refused the same way, whatever route it arrived on.
- **FR-009**: Satisfying the prompt MUST return the person to the action they asked for and carry it out, without them submitting it a second time.
- **FR-010**: The JSON API MUST keep every sensitive action it offers today, unchanged. A valid token is the proof, so no API request is refused for want of one, and this feature MUST NOT become a reason a sensitive action cannot be exposed through the API later.
- **FR-011**: Each proof and each refused attempt MUST be recorded in the account's log, next to the action it guarded.
- **FR-012**: The prompt MUST be operable with the keyboard alone and announced by a screen reader, and every string it shows MUST be translatable and translated in every configured locale.
- **FR-013**: The recovery codes MUST be shown once, on the screen that follows their creation, and MUST NOT be readable anywhere afterwards. This covers both moments they are created, enrolling in two factor authentication and asking for new ones. The security screen says how many are left and offers to replace them, and shows none of them.

### Key Entities

- **Sensitive action**: an operation that changes how somebody signs in, grants access that outlives the session, or destroys the account. The set is fixed by the product, not by the account holder.
- **Proof of identity**: the record that the person at the screen presented a credential, held against the session, with the moment it was given and the moment it stops counting.
- **Credential**: the account's password, or one of its passkeys. An account may hold both, one or neither.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Every one of the actions listed in FR-002 is refused from a signed in session with no live proof, whatever route it is reached through.
- **SC-002**: An account with no password completes every sensitive action using a passkey alone, and is never asked for a password.
- **SC-003**: A run of sensitive actions inside fifteen minutes costs one prompt, not one per action.
- **SC-004**: Somebody who takes over a signed in session, with no credential of the account holder, changes nothing about how the account signs in, deletes nothing, and reads no recovery code.
- **SC-005**: Every API request that worked before this feature works after it, with no request body changed and no endpoint withdrawn.
- **SC-006**: The prompt is completed with the keyboard alone, announced by a screen reader, and shown in every configured locale.

## Assumptions

- The credentials in the product today are a password and a passkey. Sign in through an identity provider is a stored value with no sign in flow yet, so an account holding neither falls under FR-005 rather than getting a third kind of proof. Two factor authentication stays a sign in step and is not a proof here, because somebody holding the session already passed it. A magic link is not a proof either: it proves the mailbox, which is one of the things these actions protect.
- A token holder can still mint further tokens and change the email address, which this feature accepts rather than closes. Narrowing that is the job of token scopes: `abilities` is written today and never read, and the list of scopes is an open question in `spec/models/006-personal-access-token-in-progress.md`, settled when the API surface is. Most of the sensitive actions have no API endpoint at all yet, the password, two factor authentication, passkeys and account deletion among them, and that gap is older than this feature and not closed here either.
