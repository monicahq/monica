# Feature Specification: Sign in with an outside provider

**Feature Branch**: `2026-09-11-socialite-login`

**Created**: 2026-09-11

**Status**: Draft

**Input**: User description: "Add Laravel Socialite login"

## Overview

The sign in screen offers a password, a link sent by email and a passkey. This adds one more way in: a person chooses an outside provider they already have an account with, confirms there, and comes back signed in here. The instance operator owns the arrangement with the provider and holds its credentials. An instance that configures none shows no trace of this, controls and routes included.

## Clarifications

- Q: What happens when a provider sign in matches no user here? → A: It creates the account, giving it what registering gives today.
- Q: What happens when the address matches a user who signs in another way? → A: It is refused. That user connects the provider from their own security screen while signed in, so nobody reaches an existing account by proving control of an address at a provider.
- Q: Which providers may an instance offer? → A: A fixed set the product names and supports, narrowed to those whose package is actually installed. GitHub, Google, Microsoft and Facebook first.
- Q: Does connecting a provider replace the way the user signed in before? → A: No. An account created through a provider or a passkey never had a password. An account that has one keeps it, and only its owner takes it away.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Sign in with a provider (Priority: P1)

Somebody chooses a provider on the sign in screen and confirms there. If they already have an account connected to it they land on their vaults. If they have none, one is created for them and they land there signed in.

**Why this priority**: it is the feature, and nothing else here has a purpose without it.

**Independent Test**: configure one provider, choose it from the sign in screen, confirm there, and arrive signed in with an account. Repeat and arrive at the same account.

**Acceptance Scenarios**:

1. **Given** a user connected to a configured provider, **When** they confirm there, **Then** they are signed in as that user.
2. **Given** a person the instance has never seen, **When** they confirm at the provider, **Then** an account is created with everything registering creates, and they are signed in.
3. **Given** the same, **When** they cancel or the provider refuses, **Then** they return to the sign in screen with an explanation and nothing is created.
4. **Given** a user with two factor enrolled, or a deactivated user, **When** they sign in through their provider, **Then** the second factor is still asked for, and the deactivated user is refused.

---

### User Story 2 - The operator decides what is offered (Priority: P1)

Whoever runs the instance installs what a provider needs, registers the application with it and supplies its credentials, and the sign in screen starts offering it. An operator who supplies nothing changes nothing.

**Why this priority**: a provider nobody configured must not appear, and the credentials belong to the instance. Both have to hold before the first story is safe to ship.

**Independent Test**: with nothing configured, confirm the sign in screen is unchanged and the new routes answer not found. Configure one provider, confirm exactly that one is offered.

**Acceptance Scenarios**:

1. **Given** no provider is configured, **When** a visitor opens the sign in screen, **Then** none is offered and the routes this feature adds are not found.
2. **Given** two are configured, **When** a visitor opens the sign in screen, **Then** exactly those two are offered, each named so the visitor knows which is which.
3. **Given** a provider the product supports whose package is absent, **When** the operator configures it, **Then** it is reported unavailable and never offered.

---

### User Story 3 - A user connects and disconnects a provider (Priority: P1)

A user signed in already opens their own security screen, connects a provider to their account, sees which one it is, and can disconnect it while another way in remains.

**Why this priority**: with an address match refused, this is the only way an account that already exists ever reaches a provider. Without it the feature serves new accounts only.

**Independent Test**: sign in with a password, connect a provider from the security screen, sign out, sign in through the provider and arrive at the same account. Disconnect and confirm the password still works.

**Acceptance Scenarios**:

1. **Given** a signed in user, **When** they connect a provider and confirm there, **Then** the provider is named on their security screen and signs them in, and whatever signed them in before still does.
2. **Given** a provider already connected to another user, **When** somebody tries to connect it, **Then** they are refused and no account changes.
3. **Given** a user whose provider is their only way in, **When** they ask to disconnect it, **Then** they are refused and told what to set up first.
4. **Given** a user who also has a password, **When** they disconnect, **Then** they are warned first, the connection goes, and the password still signs them in.

---

### Edge Cases

- The address is missing, unverified, already another user's here, or changed at the provider since the last sign in.
- The provider is configured but unreachable, and the other ways in must still work.
- A confirmation is replayed, or somebody returns with one nobody asked for.
- An instance removes a provider's credentials, or its package, while users are connected to it.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The sign in screen MUST offer one named control per available provider, and none when the instance has none available.
- **FR-002**: Choosing a provider MUST send the visitor there, and MUST sign them in here once the provider confirms who they are.
- **FR-003**: The product MUST NOT ask a person for a credential of their own at the provider, and MUST NOT store one.
- **FR-004**: A sign in MUST be refused when the provider returns no email address, or returns one it does not state is verified.
- **FR-005**: A provider sign in matching no user here MUST create the account, giving it everything registering gives today, and MUST treat the address as verified since FR-004 already refuses one that is not.
- **FR-006**: A provider sign in whose address matches a user who signs in another way MUST be refused, and that user MUST be able to connect the provider from their own security screen while signed in.
- **FR-007**: The product MUST name the providers it supports, and a provider MUST be offered only when its package is installed and the instance has configured its credentials.
- **FR-008**: Whether a provider's package is installed MUST be read through configuration rather than tested where it is used, so both states are covered by the suite, and a missing package MUST NOT be named anywhere as though it were available.
- **FR-009**: A provider sign in MUST satisfy every other condition on signing in: two factor when enrolled, and refusal when the user is deactivated.
- **FR-010**: The product MUST record which provider a user signs in with, and MUST show it to that user on their own security screen.
- **FR-011**: Disconnecting a provider MUST warn first, and MUST be refused when it would leave the user with no way to sign in.
- **FR-012**: A refused, cancelled or failed sign in MUST return the visitor to the sign in screen with an explanation, and MUST NOT reveal whether an address has an account here.
- **FR-013**: A provider sign in MUST be rate limited, and recorded the way the other ways in are.
- **FR-014**: Every string added MUST be translatable in every configured locale, and every control operable with the keyboard alone and announced by a screen reader.
- **FR-015**: Connecting a provider MUST NOT remove or disable any other way the user signs in. A password goes away only when its owner removes it, and an account created through a provider or a passkey never had one.

### Key Entities

- **User**: gains the provider they sign in with. The place for it exists already. An account created through a provider holds no password; one that connects a provider later keeps whatever it had.
- **Provider configuration**: belongs to the instance, never to an account. It names a provider and holds its credentials, and a provider is available when that configuration and its package are both present.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: No provider sign in reaches an account that is not the signer's, including when the address is unverified, already taken, or changed at the provider since last time.
- **SC-002**: An instance with no provider available behaves exactly as it does today. Every existing sign in test still passes, except those asserting that a user who signs in through a provider may not also hold a password or a passkey, which this change reverses on purpose.
- **SC-003**: A user who holds a password and connects a provider can still sign in with either, and the suite proves it in both directions.
- **SC-004**: The application boots, renders every screen and passes its suite with a provider's package absent.

## Assumptions

- The arrangement with each provider belongs to the instance, which is what the constitution requires of an outside service. A self hosted operator registers the application themselves.
- A user signs in through at most one provider, since the place reserved for it holds one name. An account created through a provider is metered and billed like any other, so nothing about the free tier or the paywall changes.
- The redirect belongs to the sign in screen, so no endpoint mirrors it. Connecting and disconnecting get none either, which follows what the API already does with passwords, passkeys and two factor, none of which it exposes. Its own sign in refuses a user who has no password, and that is the one place it changes.
- The mechanism that talks to providers is an ordinary dependency. A package needed by one provider and no other is suggested instead, kept where the suite still covers the working path, following what the constitution requires of an integration package.
