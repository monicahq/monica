# Feature Specification: Sign in with Apple

**Feature Branch**: `claude/apple-socialite-provider-af30cc` | **Created**: 2026-11-11 | **Status**: Draft

**Input**: "add "apple" socialite provider, make the package optional"

## Clarifications

### Session 2026-11-11

- Q: Which package does "make the package optional" cover? → A: Only Apple's. The components that
  carry sign in through every provider, and through OpenID Connect (106), stay required.
- Q: What does the admin space show without the package? → A: The settings and a notice (FR-009).
- Q: Where does Apple sit among the providers? → A: After Google, before Microsoft (FR-010).
- Q: Is the connection revoked at Apple on disconnect or account deletion? → A: No, out of scope.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Sign in with an Apple account (Priority: P1)

An instance can offer GitHub, Google, Microsoft, Facebook and the OpenID Connect providers of
[106](../106-openid-connect-providers/spec.md), and not Apple. After this change an operator who
installed the Apple component and supplied the credentials Apple issues sees
**Continue with Apple** offered next to the others.

**Why this priority**: It is the reason for the change.
**Independent Test**: Install the component, configure Apple, then create an account with it, sign
out, sign in again, and connect and disconnect it from the security settings.

**Acceptance Scenarios**:

1. **Given** the component is installed and every Apple setting is supplied, **When** a visitor
   opens the sign in or registration screen, **Then** Apple is offered under its own name.
2. **Given** it is offered, **When** a visitor with no account signs in through it, **Then** an
   account is created from the name and email address Apple shares, as for GitHub.
3. **Given** somebody signed in with a password, **When** they open the security settings, **Then**
   Apple is listed and can be connected, then disconnected under the existing rules.
4. **Given** somebody already connected Apple, **When** they sign in through it again, **Then**
   they reach the same account, although Apple shares their name on the first sign in only.

### User Story 2 - An instance without the Apple component runs unchanged (Priority: P1)

The component that carries Apple sign in is not installed with the product. An operator who does
not want Apple installs nothing, and the instance behaves exactly as it does today.

**Why this priority**: The request makes the component optional, and an absent component that
breaks a screen breaks every instance that never asked for Apple.
**Independent Test**: Remove the component, supply the Apple settings anyway, and open every screen
that lists providers.

**Acceptance Scenarios**:

1. **Given** the component is absent, **When** anybody opens the sign in, registration or security
   screen, **Then** Apple is absent and every other provider works.
2. **Given** the component is absent and the Apple settings are supplied, **When** somebody opens
   the address Apple sign in starts from, **Then** it answers as for a provider that is not offered.
3. **Given** the component is absent, **When** an administrator opens the instance settings,
   **Then** the Apple settings are shown with one sentence saying the component is not installed.

### Edge Cases

- The visitor chose Apple's option to hide their email address. The relay address Apple shares is
  treated as their email address, under the existing rules.
- Apple shares no name (every sign in after the first, or the visitor withheld it). The existing
  rule for a provider that shares no name applies.
- The visitor cancels at Apple, or what Apple returns cannot be verified. They return to where they
  started with the error every provider already produces, and nothing is created or changed.
- The operator supplied part of the Apple settings. Apple is absent.
- The component is removed from an instance where accounts hold an Apple connection. The
  connections are kept, Apple is no longer offered, and the refusal to disconnect the last way in
  still counts the Apple connection.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: An operator MUST be able to offer Apple by installing its component and supplying either
  its signing key or a secret generated in advance. The key wins when both are supplied.
- **FR-002**: Apple MUST be offered only when the component is installed and every setting it needs
  is supplied. Otherwise it MUST be absent from the sign in, registration and security screens.
- **FR-003**: The product MUST start, show every screen and pass its tests with the component
  absent.
- **FR-004**: The component MUST be listed as a suggestion with a sentence saying what it is for,
  and MUST stay installed where the product is developed and tested, so both states are covered.
- **FR-005**: Whether the component is present MUST be testable in both states without installing
  or removing it.
- **FR-006**: Apple MUST behave as the existing providers in creating an account, signing in,
  connecting, disconnecting, the refusal to disconnect the last way in, the email that reports a
  connection, and the account's journal.
- **FR-007**: The system MUST verify that an identity was issued by Apple, for this instance, and
  has not expired. A failed verification MUST refuse and change nothing.
- **FR-008**: An operator who supplies the key MUST NOT have to renew a credential by hand for Apple
  to stay offered. A secret generated in advance is theirs to renew.
- **FR-009**: The Apple settings MUST be suppliable through the environment and through the
  instance settings of the admin space, where a secret is treated as a secret, and where a missing
  component is said in one sentence.
- **FR-010**: Apple MUST be placed after Google and before Microsoft. The order of the other
  providers MUST NOT change.
- **FR-011**: The name "Apple" MUST NOT be translated. The copy around it MUST be.
- **FR-012**: The guide MUST name Apple among the providers an instance can offer, say that its
  component is installed separately, and say what an operator supplies.

## Success Criteria *(mandatory)*

- **SC-001**: Every journey that exists for GitHub (create, sign in, connect, disconnect) succeeds
  for Apple, in the same number of steps.
- **SC-002**: With the component absent, Apple is offered on zero screens and zero screens fail,
  whatever settings are supplied.
- **SC-003**: With the component present and incomplete settings, Apple is offered on zero screens.
- **SC-004**: A forged or expired identity creates zero accounts and zero connections.
- **SC-005**: An instance configured with the key still offers Apple a year later with no action.

## Assumptions

- The constitution's rule for an outbound channel's integration package (suggested, kept for
  development, read through configuration) is applied to a sign in provider, which it does not name.
- Apple accepts only a public, secure address it has been told about, so Apple cannot be exercised
  on a local instance. That is Apple's rule and the guide says so.
- The rules for an address already in use, for confirming an address and for the last way in are
  not reopened.
