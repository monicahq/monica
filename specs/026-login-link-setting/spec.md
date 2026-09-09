# Feature Specification: Magic link sign in behind one instance setting

**Feature Branch**: `2026-09-09-login-link-setting`

**Created**: 2026-09-09

**Status**: Draft

**Input**: User description: "make login-link available on all environments and hosts replacing it with a boolean to activate the feature or not"

## Overview

The sign in screen offers two ways in without a password. A direct shortcut into the seeded example account, which signs the visitor in on the spot and works only in the local environment on localhost, a loopback address or a `.test` host, both fixed lists. And the magic link, which emails a link that signs its owner in once, available in every environment and on every host, and always on.

This removes the direct shortcut with its two lists, and puts the magic link behind one setting of the instance. The setting is on unless somebody turns it off, so the magic link keeps working everywhere, as it does today. When it is off, its control, its screens and its routes do not exist on that instance. The magic link never says whether an address has an account, so nothing is revealed by offering it on any host.

## Clarifications

### Session 2026-09-09

- Q: When the setting is on, which accounts may the shortcut sign a visitor into? → A: Any account named in the request, as the underlying mechanism does today once its environment and host checks pass.
- Q: When a request is refused, either because the setting is off or because the address belongs to nobody, what does the requester get back? → A: When the setting is off, the routes and the control do not exist at all. A request must never reveal whether an address has an account, so the direct sign in is replaced by the sign in email the magic link already sends, and the setting switches the whole magic link flow on or off for the instance.
- Q: When nothing is set, is the magic link on or off? → A: On. Every instance keeps the magic link it has today, and turning it off is a deliberate act.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - One passwordless way in, everywhere (Priority: P1)

A visitor on any instance where the setting is on asks for a sign in link from the sign in screen, receives it by email and is signed in by following it. The direct shortcut into the example account is gone from every instance.

**Why this priority**: it is the request. The passwordless way in has to work on every environment and host, and it has to be one that tells nobody whether an address has an account.

**Independent Test**: on an instance whose environment is not local and whose host is not a development one, with the setting on, ask for a link for an existing account, follow it, and arrive signed in. Confirm the sign in screen no longer offers the direct shortcut and its request answers not found.

**Acceptance Scenarios**:

1. **Given** the setting is on, **When** a visitor opens the sign in screen on any environment and host, **Then** it offers the magic link and does not offer the direct shortcut.
2. **Given** the setting is on, **When** a visitor asks for a link for an existing account and follows it, **Then** they are signed in as today, two factor included.
3. **Given** any instance, **When** a request reaches where the direct shortcut used to be, **Then** it is not found and nobody is signed in.

---

### User Story 2 - The operator switches the magic link off (Priority: P1)

An operator who does not want passwordless sign in on their instance turns the setting off. The sign in screen stops offering it, and nothing behind it exists.

**Why this priority**: the setting is the one thing this feature adds, and a way in that is switched off has to be absent, not merely hidden.

**Independent Test**: turn the setting off, open the sign in screen, confirm the offer is gone, then call the request screen, the submission and a link directly and get not found each time, with no email sent.

**Acceptance Scenarios**:

1. **Given** the setting is off, **When** a visitor opens the sign in screen, **Then** no passwordless offer is shown.
2. **Given** the setting is off, **When** a request reaches the request screen, the submission or a link, **Then** it is not found and no email is sent.
3. **Given** the setting holds a value that reads as neither on nor off, **When** the sign in screen is opened, **Then** the magic link is on, which is the default.

---

### User Story 3 - Nobody learns whether an address has an account (Priority: P2)

Somebody with an account on a public instance cannot be found out through the sign in screen. A request for their address and a request for an address that belongs to nobody get the same answer.

**Why this priority**: it is what allows the passwordless way in to be offered on every host at all. The behaviour exists today, and this feature makes it the only passwordless way in.

**Independent Test**: with the setting on, request a link for an existing address and for an unknown one, and compare the two answers.

**Acceptance Scenarios**:

1. **Given** the setting is on, **When** a request names an address that belongs to nobody, **Then** the answer is the same page as for an existing account, and no account is created.
2. **Given** the setting is on, **When** a request names an existing account, **Then** the answer says only that a link is on its way if the address has an account.

### Edge Cases

- A link emailed before the setting was turned off: following it is not found, like everything else behind the setting, and it is not spent.
- A developer who relied on the one click shortcut: they ask for a link and read it in the instance's mail log, which is where a local instance writes its email.
- The example account was never seeded: nothing changes on the sign in screen, since it no longer mentions the example account.
- A request for an inactive or a disposable address: as today, refused without saying which.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The instance MUST expose one setting that turns the magic link sign in on or off for the whole instance. It is on when nothing is set.
- **FR-002**: When the setting is on, the magic link MUST be offered on the sign in screen and MUST work in every environment and on every host name.
- **FR-003**: When the setting is off, the sign in screen MUST NOT offer it, and its request screen, its submission and its link MUST NOT exist. Each answers not found and no email is sent.
- **FR-004**: The direct sign in shortcut MUST be removed: its control on the sign in screen, its request, and its lists of environments and host names. No such list remains as a condition on any way of signing in.
- **FR-005**: A magic link request MUST give the same answer whether or not the address has an account, and MUST NOT create an account.
- **FR-006**: The magic link's behaviour MUST be otherwise unchanged: the link works once, expires, is rate limited, and sends somebody who uses two factor to the challenge.
- **FR-007**: A value of the setting that reads as neither on nor off MUST be treated as on, the default.
- **FR-008**: The setting MUST be documented where an operator configures the instance, stating what it switches and what its default is.
- **FR-009**: The seeded example account and its seeder MUST stay as they are. Only the shortcut into it goes.

### Key Entities

- **Instance setting**: one on or off value held by the instance, on by default, that decides whether the magic link exists on it.
- **Magic link**: the emailed link that signs its owner in once, already in the product, and after this the only passwordless way in.
- **Direct sign in shortcut**: the former control and request that signed a visitor into the example account on the spot. Removed.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: An operator switches the magic link for the whole instance by changing one setting and nothing else.
- **SC-002**: With the setting off, the sign in screen offers zero passwordless ways in, every magic link route answers not found on every environment and host, and zero emails are sent.
- **SC-003**: With the setting on, a request for an address with no account and a request for an address with one get identical answers on every host.
- **SC-004**: Zero ways of signing in without a link or a credential remain reachable on any instance. The former shortcut's request answers not found everywhere.
- **SC-005**: No list of environments or hosts is left anywhere for an operator to maintain.

## Assumptions

- The setting is on unless set, and the shipped sample configuration leaves it on. An instance upgrading from today keeps the magic link without doing anything.
- The example account and its seeder stay. Nothing on the sign in screen refers to it any more.
- The screenshot link is a distinct feature with its own conditions and is not touched.
- Password reset emails are a separate flow, and the setting does not touch them.
- Two alternatives were considered and rejected on 2026-09-09: keeping a direct sign in limited to the example account, and keeping an example account control that requests a magic link.
