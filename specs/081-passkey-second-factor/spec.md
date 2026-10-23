# Feature Specification: Security keys as a second factor

**Feature Branch**: `2026-10-17-passkey-second-factor`
**Created**: 2026-10-17
**Status**: Draft
**Input**: "Add the ability to set a passkey as 2nd authentication factor, for any webauthn key"

## Context

Two-factor authentication means one thing today: a code from an authenticator app, typed after the password, the emailed link or an outside provider. Passkeys stand apart. They sign in on their own and skip the challenge (spec 002 FR-020), and spec 002 FR-019 forbids offering one as a second factor. Adding a passkey also demands an authenticator that stores the credential itself and checks a PIN or a fingerprint, so a plain security key, or an older one, cannot be added at all.

This feature lets any WebAuthn authenticator answer the two-factor challenge, so touching a key replaces typing a code.

## Clarifications

### Session 2026-10-18

- Q: Can a security key alone turn two-factor authentication on, without the authenticator app? → A: Yes. It is on while the account holds the app, a key, or both.
- Q: Which credentials answer the challenge, and where are keys listed? → A: Keys have their own list under two-factor authentication, and the passkeys on the account answer the challenge too.
- Q: Should the API list, rename and remove security keys? → A: No. Keys are managed on the web screen only.
- Q: On the challenge, does the key prompt open by itself or on a press? → A: It opens as the screen loads, with the code field below as the other way.
- Q: What is a credential registered as a second factor called? → A: "Security key", built-in authenticators included.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Answer the challenge with a key (Priority: P1)

**Why this priority**: the reason for the feature. A typed code works as well on a copy of the sign-in screen as on the real one; a key refuses to answer a site it was not made for.

**Independent Test**: on an account holding a security key, sign in with the password and touch the key.

**Acceptance Scenarios**:

1. **Given** an account holding a security key, **When** the password is accepted, **Then** the key prompt opens as the challenge loads, and touching the key signs the person in.
2. **Given** the challenge, **When** the prompt is cancelled or the key is refused, **Then** the person stays on the challenge, is told why, and can try again or use another way offered there.
3. **Given** an account holding a key and an authenticator app, **When** the challenge opens, **Then** the key prompt opens, and the code field stands below it for the app and the recovery codes.
4. **Given** the emailed link or an outside provider, **When** the first step succeeds, **Then** the same challenge follows.
5. **Given** a security key, **When** it is offered at the passkey sign-in, **Then** the refusal is the sentence a wrong password gets.
6. **Given** an account with two-factor authentication on and a passkey, **When** the challenge opens, **Then** the passkey answers it as a key does.

### User Story 2 - Add and manage security keys (Priority: P2)

**Why this priority**: without it nobody holds a key to answer with, but story 1 can be tested with a key seeded directly.

**Independent Test**: add a key that has no PIN, rename it, then remove it.

**Acceptance Scenarios**:

1. **Given** the security screen, **When** a key is added, **Then** it is listed under its name and an email announces it, whatever kind of authenticator it is.
2. **Given** a listed key, **When** its removal is asked for, **Then** a warning comes first, the key is gone, and an email announces it.
3. **Given** the last second factor on the account, **When** its removal is asked for, **Then** the warning says two-factor authentication turns off and the recovery codes stop working.
4. **Given** a key that has answered a challenge, **When** the list is shown, **Then** it says when it was last used; one that never has says so instead.

### Edge Cases

- An insecure connection: keys cannot be added, and the challenge offers only the other ways the person holds.
- A browser that cannot use a key says so on the challenge instead of offering a dead button.
- A key held by another account, or one made for an address this instance no longer answers on, gets the ordinary refusal.
- An account holding passkeys and no second factor gets no challenge: a passkey answers one but never turns it on.
- An authenticator already holding a passkey or a key for the account is refused as a new key: its passkey already answers, and a second registration would replace it on the device.
- Somebody whose only second factor is a key, and who has lost it, uses a recovery code; past that there is no self-service way back, as with the app today.

## Requirements *(mandatory)*

### Functional Requirements

**Adding a key**

- **FR-001**: Users MUST be able to add a security key from the two-factor part of the security screen, after confirming their password when they have one, and keys MUST be listed there, apart from passkeys.
- **FR-002**: The system MUST accept any WebAuthn authenticator as a security key: a separate key or one built into the device, with or without a PIN or a fingerprint, storing the credential itself or not, and the screen MUST say that a built-in one counts.
- **FR-003**: The system MUST NOT check or record the make and model of a key, nor restrict which keys are allowed, as spec 002 FR-008 already says of passkeys.
- **FR-004**: Users MUST be able to name, rename and remove each key, and removal MUST warn first.
- **FR-005**: Adding, using and removing a key MUST each write to the account journal, and adding and removing MUST each send an email.
- **FR-006**: Keys MUST be offered only over a secure connection, as passkeys are, and the screen MUST say why elsewhere.

**Turning two-factor authentication on and off**

- **FR-007**: Two-factor authentication MUST count as on, everywhere the product reports it, while the account holds the authenticator app or at least one security key.
- **FR-008**: Recovery codes MUST be issued when two-factor authentication turns on, whichever factor turned it on, MUST be kept when another factor is added, and MUST stop working when it turns off.
- **FR-009**: Removing the app or a key MUST leave two-factor authentication on while another factor remains, removing the last MUST turn it off, and each warning MUST say which will happen.

**The challenge**

- **FR-010**: The challenge MUST accept the security keys and the passkeys of the person who passed the first step, and no other credential.
- **FR-011**: The challenge MUST offer every way the person holds, and when that includes a key or a passkey it MUST open the key prompt as it loads, with the code field below and a button that opens the prompt again.
- **FR-012**: The challenge MUST accept a key that checks no PIN and no fingerprint, since the first step already stands as one factor.
- **FR-013**: Where the browser or the connection cannot use a key, the challenge MUST say so and offer the other ways the person holds.
- **FR-014**: The key challenge MUST be throttled, expire, and refuse a deactivated account exactly as the code challenge does today.
- **FR-015**: Passing the challenge with a key MUST record when the key was last used and send the new sign-in email, as a code does.

**What stays as it is**

- **FR-016**: A security key MUST NOT sign anybody in on its own.
- **FR-017**: Passkey sign-in MUST still skip the challenge (spec 002 FR-020), and the emailed link MUST still go through it (FR-022 there).
- **FR-018**: Spec 002 FR-019 MUST be amended in the same change, and the sentences FR-021 there asks for MUST be rewritten to name keys.
- **FR-019**: The API sign-in MUST keep accepting the app code or a recovery code, and nothing else, and the API MUST offer no way to list, add, rename or remove a key.
- **FR-020**: The prompt opening on its own and every outcome of it, a cancelled one included, MUST be announced to a screen reader, and both screens MUST work with the keyboard alone.

### Key Entities

- **Security key**: a WebAuthn credential registered as a second factor for the single person on an account. Carries the name its owner gave it, when it was added and last used, and the credential. Never signs in on its own. Deleted with its user.
- **Second factor**: the authenticator app or a security key. Two-factor authentication is on while the account holds one, and recovery codes exist exactly while it is on. A passkey answers the challenge but is not a second factor.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A person holding a key passes the second step without typing anything, in under ten seconds from the challenge opening.
- **SC-002**: A key without a PIN, a key with a PIN, and an authenticator built into a laptop or phone can each be added and can each answer the challenge.
- **SC-003**: No attempt, in tests or by hand, signs anybody in with a security key as the only credential.
- **SC-004**: An account holding no security key and no passkey sees the same sign-in screens and emails as before, apart from the sentences FR-018 rewrites.
- **SC-005**: The challenge and the key list meet WCAG 2.2 AA with the keyboard alone and with a screen reader.
- **SC-006**: Every new sentence is translated in every configured locale before merge.

## Assumptions

- Native applications do not exist yet, and passkey sign-in has no API counterpart either. A WebAuthn exchange through the API, for passkeys and keys together, is a follow-up; this is the exception principle X asks to be stated. Until then an account whose only factor is a key answers the API sign-in with a recovery code. Keys are managed on the web screen only, as passkeys and the app are, so a leaked API key cannot remove the second factor.
- "Security key" is the product noun for every such credential, distinct from "passkey" and from "authenticator app", and enters the glossary in `.claude/rules/translations.md`.
- No attestation is asked for, as with passkeys; a self-hosted instance cannot keep a list of approved hardware.
- Adding a key costs a password confirmation like every other change to the way somebody signs in, and an account without a password is never asked for one.
