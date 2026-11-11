# Feature Specification: Sign in through OpenID Connect providers

**Feature Branch**: `2026-11-08-connect-several-providers` | **Created**: 2026-11-09 | **Status**: Draft

**Input**: "Add socialiteproviders/openidconnect and replace socialiteproviders/microsoft"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Sign in with the identity provider an organisation already runs (Priority: P1)

An instance can offer GitHub, Google, Microsoft and Facebook, and nothing else. Somebody who hosts
Monica and already runs a provider that speaks OpenID Connect (Authentik, Zitadel, Authelia and the
like) cannot use it. After this change the operator supplies that provider's address and
credentials, and the sign in screen offers it next to the others.

**Why this priority**: It is the reason for the change. **Independent Test**: Configure one generic provider, then create an account with it, sign out, sign
in again, and connect and disconnect it from the security settings.

**Acceptance Scenarios**:

1. **Given** an operator supplied the address, identifier and secret of a provider, **When** a
   visitor opens the sign in screen, **Then** it is offered under the name the operator gave it.
2. **Given** it is offered, **When** a visitor with no account signs in through it, **Then** an
   account is created from the name and email address it shares, as for GitHub.
3. **Given** somebody signed in with a password, **When** they open the security settings, **Then**
   the provider is listed and can be connected, then disconnected under the existing rules.
4. **Given** the operator supplied nothing, **When** anybody opens either screen, **Then** the
   provider is absent.

---

### User Story 2 - Microsoft stays available (Priority: P2)

Microsoft sign in is carried by a component of its own. This change carries it through OpenID
Connect, so one mechanism serves every provider of that kind. A reader still sees
**Continue with Microsoft**.

**Why this priority**: Replacing the mechanism must not withdraw a provider already offered. **Independent Test**: Configure Microsoft and run the journey of story 1.

**Acceptance Scenarios**:

1. **Given** Microsoft credentials, **When** a visitor opens the sign in screen, **Then** Microsoft
   is offered under its own name, in the same place and at the same address as before.
2. **Given** an operator names one organisation, **When** somebody outside it tries, **Then** they
   cannot sign in. Without it, any Microsoft account may.
3. **Given** the instance settings of the admin space, **When** an administrator opens them,
   **Then** the Microsoft identifier and secret are still editable there.

---

### User Story 3 - Keycloak, Auth0 and Okta by name (Priority: P3)

Three widely used providers are offered by name: the operator fills in what the provider calls its
own settings (a realm, a domain), and the reader sees the provider's name on the button.

**Why this priority**: A convenience over story 1, which already reaches all three. **Independent Test**: Configure each in turn and run the journey of story 1.

**Acceptance Scenarios**:

1. **Given** the settings of Keycloak, Auth0 or Okta, **When** a visitor opens the sign in screen,
   **Then** that provider is offered under its own name.
2. **Given** several are configured at once, **When** somebody opens the security settings,
   **Then** each is listed once and can be connected independently.

### Edge Cases

- The provider cannot be reached, its address is insecure, or what it returns cannot be verified
  (wrong issuer, bad signature, expired). The visitor returns to where they started with the error
  every provider already produces, and nothing is created or changed.
- The provider shares no email address, or one another account uses. The existing rules apply.
- The operator supplied part of a provider's settings. The provider is absent.
- The generic provider has no name set. It is offered as "OpenID Connect".

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: An operator MUST be able to offer one generic OpenID Connect provider by supplying its
  address, an identifier, a secret and, optionally, the name shown to readers.
- **FR-002**: An operator MUST be able to offer Microsoft, Keycloak, Auth0 and Okta by supplying the
  settings each names itself, without working out an address.
- **FR-003**: Microsoft MUST stay offered as "Microsoft", and MUST be restrictable to one organisation.
- **FR-004**: A provider MUST be offered only when every setting it needs is supplied. Otherwise it
  MUST be absent from the sign in, registration and security screens.
- **FR-005**: Every provider added here MUST behave as the existing ones in creating an account,
  signing in, connecting, disconnecting, the refusal to disconnect the last way in, the email that
  reports a connection, and the account's journal.
- **FR-006**: The system MUST verify that an identity was issued by the configured provider, for
  this instance, and has not expired. A failed verification MUST refuse and change nothing.
- **FR-007**: The system MUST NOT talk to a provider over an insecure address, except locally.
- **FR-008**: The settings added here MUST be suppliable through the environment and through the
  instance settings of the admin space, where a secret is treated as a secret.
- **FR-009**: The order of providers MUST be fixed: the existing four in their current order, then
  Keycloak, Auth0, Okta and the generic one.
- **FR-010**: The name of a provider MUST NOT be translated. The copy around it MUST be.
- **FR-011**: The component that carried Microsoft sign in MUST be removed, with whatever existed
  only for it.
- **FR-012**: The guide MUST name the providers an instance can offer and what an operator supplies
  for each.

## Success Criteria *(mandatory)*

- **SC-001**: An operator with a client registered at their provider makes it appear by supplying at
  most four settings.
- **SC-002**: Every journey that exists for GitHub (create, sign in, connect, disconnect) succeeds
  for each of the five providers, in the same number of steps.
- **SC-003**: A provider with incomplete settings is offered on zero screens.
- **SC-004**: A forged or expired identity creates zero accounts and zero connections.
- **SC-005**: Nothing in the product or the guide refers to the removed component.

## Assumptions

- "Replace" means Microsoft stays, carried by the OpenID Connect component. Google, GitHub and
  Facebook are untouched.
- One generic provider per instance. Several at once is out of scope.
- No released instance holds a Microsoft connection, so none is migrated. Microsoft identifies a
  person differently through OpenID Connect, so an older connection would not match.
- The component becomes a regular dependency: availability depends on configuration alone.
- The rules for an address already in use, for confirming an address and for the last way in are
  not reopened. Signing out of the provider when signing out of Monica is out of scope.
