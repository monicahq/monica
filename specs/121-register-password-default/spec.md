# Feature Specification: Password as the default registration method

**Feature Branch**: `2026-11-12-register-password-default` | **Created**: 2026-11-12 | **Status**: Draft

**Input**: "Default register to password, and propose passkey/passwordless as option".

**Supersedes**: in `specs/002-webauthn-auth/spec.md`, User Story 6, scenario 1 (the passkey as the
primary offer) and the "it says so" of scenario 6. The other scenarios stand.

## Clarifications

### Session 2026-11-12

- Q: Does "passwordless" also cover signing up by emailed link alone? → A: No, passkey only.
- Q: Is the passkey offered in a browser that cannot create one? → A: No, the control is absent.

### Session 2026-11-13

- Q: Does the screen still warn over a connection that is not secure? → A: No. The sign in screen
  and the security settings already say it.
- Q: How is the passkey option presented? → A: A secondary button under the primary one (changed from
  a quiet link after review). No method selector.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Create an account with a password, with nothing to choose (Priority: P1)

The registration screen opens asking for a name, an email address, a password and its confirmation,
and the agreement to the terms. Nobody has to find a password option first.

**Why this priority**: it is the request. Today the password fields appear only after activating
"Other options". **Independent Test**: load the screen, fill it in, submit.

**Acceptance Scenarios**:

1. **Given** a secure connection, **When** the screen loads, **Then** the password and its
   confirmation are shown and required, and the button reads "Next step: validate your email address".
2. **Given** a valid password, **When** the screen is submitted, **Then** the account exists with
   that password and the reader is asked to confirm their email address, as today.
3. **Given** a refused password, **Then** the screen comes back on the password method, refusal shown.

### User Story 2 - Choose a passkey instead (Priority: P2)

Under the button stands one secondary button, "Use a passkey instead". It swaps the password fields for
the sentence saying what a passkey is, and then reads "Set a password instead".

**Why this priority**: an account with no password stays one activation away. **Independent Test**:
choose the passkey, submit, confirm the account holds no password and the passkey step opens.

**Acceptance Scenarios**:

1. **Given** a secure connection and a browser that can create a passkey, **When** the screen loads,
   **Then** "Use a passkey instead" stands under the button.
2. **When** it is activated, **Then** no password is asked or required, a sentence explains a passkey,
   the button reads "Create a passkey" and the control reads "Set a password instead".
3. **Given** the passkey method, **When** the screen is submitted, **Then** the account exists with
   no password and the step that creates the passkey opens, as today.
4. **Given** the passkey method and a refused attempt (email address in use, terms not accepted),
   **When** the screen comes back, **Then** the passkey method is still selected.
5. **When** somebody activates "Set a password instead", **Then** the screen is User Story 1 again,
   with the name, the email address and the agreement to the terms kept.

### User Story 3 - No passkey offered where one cannot be made (Priority: P3)

Over a connection that is not secure, or in a browser that cannot create a passkey, the screen is the
password form with no passkey control and no sentence about passkeys.

**Why this priority**: it removes a warning that now describes every default sign up, and an offer
that fails only after the account exists. **Independent Test**: load the screen over a connection
that is not secure, then in a browser that cannot create a passkey.

**Acceptance Scenarios**:

1. **Given** a connection that is not secure, or a browser that cannot create a passkey, **Then** the
   screen shows the password method, no passkey control, and no sentence about passkeys.
2. **Given** a connection that is not secure and a request naming the passkey method anyway,
   **When** the screen is shown again, **Then** it shows the password method.

### Edge Cases

- Scripts are off: the screen is the complete password form. Today the password fields stay hidden.
- A password typed before switching to the passkey is discarded: the account holds no password.
- A cancelled device prompt belongs to the step after the passkey method, which is not changed.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The screen MUST open on the password method unless an earlier attempt chose the passkey.
- **FR-002**: Creating an account with a password MUST behave exactly as it does today.
- **FR-003**: Where the connection is secure and the browser can create a passkey, the screen MUST
  offer the passkey method as one secondary control under the button, "Use a passkey instead".
- **FR-004**: Choosing the passkey MUST stop asking for a password, explain a passkey, and relabel the
  button and the control as User Story 2 says. Switching MUST keep the name, email address and terms.
- **FR-005**: Creating an account with the passkey method MUST behave exactly as it does today.
- **FR-006**: After a refused attempt, the screen MUST come back on the method the reader had chosen.
- **FR-007**: Where a passkey cannot be made (connection or browser), the screen MUST show the
  password method only and say nothing about passkeys. Today's warning is removed.
- **FR-008**: With scripts off, the screen MUST be the working password form.
- **FR-009**: The screen MUST meet WCAG 2.2 AA: the control is operable from the keyboard and names
  what it does, and the fields of the method not chosen are neither focusable nor announced.
- **FR-010**: Copy the screen stops using MUST leave the translation files.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A password account needs no control beyond the fields and the button, one fewer than today.
- **SC-002**: The passkey method is one activation away, and its accounts hold no password.
- **SC-003**: Over a connection that is not secure, in a browser that cannot create a passkey, or
  with scripts off, no control leads to a failure and an account can be created.
- **SC-004**: The accessibility review of the screen reports no new finding.

## Assumptions

- No setting and no flag: every instance, hosted or self hosted, opens on the password.
- Out of scope and unchanged: the sign in screen, registration through the API (already password
  only), sign up through a provider, the step after the passkey method, and stored data.
- "Use a passkey instead" and "Set a password instead" are already shown and translated. "Other
  options" and the registration warning are shown by this screen only and leave with it.
