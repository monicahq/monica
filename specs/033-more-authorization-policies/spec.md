# Feature Specification: More Authorization Policies

**Created**: 2026-09-14

**Status**: Draft

**Input**: User description: "More policy and authorizations based on https://laravel.com/framework/docs/authorization capabilities."

## Starting point

[028](../028-authorization-policies/spec.md) gave three capabilities one home each and settled how an
ability is declared, asked and proven. A sweep of the rest of the application found eleven more places
where a screen and an action each hold a copy of one rule, and on every one of them the screen removes
the control rather than explaining it. This takes all eleven. Everything 028 decided stays decided,
and is linked rather than repeated.

## Clarifications

### Session 2026-09-14

- Q: How many of the eleven pairs the sweep found does this feature take? → A: All eleven, including the contact merge and the marketplace moderation.
- Q: Should a refusal given to a request arriving by its address come from the same rule the screen asks? → A: Yes, and it keeps answering exactly as it does today, as if the thing were not there. Stated by FR-004; the separate requirement asking this is removed.
- Q: On a screen of many rows, does every refused row need its own new reason? → A: No. Text the row already carries is the reason, provided the refused control points at it. A reason is added only where none is written.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - The settings screens say why (Priority: P1)

Relationship types, value lists, the values inside them and entity types each carry a rule about whether they may be deleted, and each screen removes the control rather than explaining it.

**Why this priority**: five capabilities on one screen family, the largest group in the set, and one of them is already written twice inside a single screen, which is the drift this exists to prevent.

**Independent Test**: put a relationship type in use, confirm the screen marks the control unavailable with its reason, then change the rule in its one home and confirm both surfaces follow.

**Acceptance Scenarios**:

1. **Given** a relationship type that relationships are written with, **When** its form renders, **Then** the delete control is marked unavailable and carries as its reason that the type is in use.
2. **Given** a value list the product supplied, **When** the list renders, **Then** its delete control is marked unavailable and carries as its reason that the product supplied it.
3. **Given** one that nothing uses and the product did not supply, **When** its form renders, **Then** the control is active and deleting works.

### User Story 2 - The template builder tells the same story everywhere (Priority: P2)

Whether a template may be shared, and whether a page may be removed, are each decided in more than one place. Sharing is decided three times, the only rule in the set that is.

**Why this priority**: the only rule written three times, and the only one where a request arriving by its address, rather than through a control, is refused by a separate copy.

**Independent Test**: take a template that was never published, confirm the builder marks sharing unavailable with its reason and that its share address still refuses, then change the rule once.

**Acceptance Scenarios**:

1. **Given** a template that was never published, **When** the builder renders, **Then** the share control is marked unavailable and carries as its reason that there is nothing frozen to share.
2. **Given** the same template, **When** its share address is requested directly, **Then** it is refused exactly as it is refused today.
3. **Given** a version holding one page, **When** the builder renders, **Then** the control that removes it is marked unavailable and carries as its reason that a version always keeps a page.

### User Story 3 - Giving up a way of signing in (Priority: P3)

Whether the password may be removed, and whether the connected provider may be disconnected, are each written twice, in wordings that agree today and are maintained separately.

**Why this priority**: two real second copies, on the one screen where a wrong answer locks somebody out of their own account.

**Independent Test**: hold an account whose only way in is its password, confirm the removal is marked unavailable with its reason, add a passkey, confirm it becomes active.

**Acceptance Scenarios**:

1. **Given** an account whose only way in is its password, **When** the security screen renders, **Then** removing the password is marked unavailable and carries as its reason that it is the only way in.
2. **Given** an account that also holds a passkey, **When** the screen renders, **Then** the control is active and removing the password works.

### User Story 4 - The last two copies (Priority: P4)

The contact representing somebody cannot be absorbed into another, and a listing version can only be decided on while somebody is waiting for that decision. Each is written once on a screen and once where the work happens.

**Why this priority**: two isolated pairs in unrelated parts of the product. Each is small, and neither blocks the others.

**Independent Test**: open the merge screen and confirm the contact representing you is offered and refused with its reason rather than quietly left out of the list.

**Acceptance Scenarios**:

1. **Given** the contact representing the account holder, **When** the merge screen offers who to absorb, **Then** they appear and are marked unavailable, carrying as their reason that this contact stays.
2. **Given** a listing version nobody is waiting on, **When** the moderation screen renders, **Then** approving and refusing are marked unavailable and carry as their reason that the decision is already made.

### Edge Cases

- A rule that counts records can change between a screen drawing a control and somebody using it. The screen offers, the refusal decides, and the refusal is the one that has to be right.
- A capability reached by its address rather than through a control must refuse in the shape it refuses today. A refusal must never become a way to learn that something exists.
- The contact ceiling counts neither the contact representing somebody nor a partial contact, and the ability added in 028 does not know that, so the work still holds two thirds of its own rule.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Each capability in scope MUST be declared once under a stable name, and that declaration MUST be the only statement of the rule in the application.
- **FR-002**: FR-002 through FR-010 of [028](../028-authorization-policies/spec.md) apply unchanged to every ability added here, and are not restated.
- **FR-003**: Every control a screen removes today because its rule refuses MUST instead stay, marked unavailable and tied to its reason. Where the screen already writes that reason beside the control, that text is the reason and MUST NOT be repeated; where it writes none, one MUST be added.
- **FR-004**: Where a request arriving by its address is refused for the state of the thing rather than for who is asking, that refusal MUST come from the ability the screen asks, and MUST keep the answer it gives today.
- **FR-005**: The contact ceiling ability MUST answer correctly for a partial contact and for the contact representing somebody, so no part of that rule is left behind.
- **FR-006**: A capability joining the set MUST NOT gain a refusal type of its own; the refusal already carries the wording the rule holds.
- **FR-007**: The capabilities in scope are the eleven the sweep found: deleting a relationship type, a value list, a value, an entity type; adding a value to a closed list; sharing a template; removing a page from a version; removing the password; disconnecting the provider; absorbing a contact; deciding on a listing version.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: For every capability in scope the rule is stated in exactly one place, verified by removing it and observing every surface change together.
- **SC-002**: Across every screen in scope, no control that would refuse can be activated, and none is hidden.
- **SC-003**: Every explanation the product gives today is reproduced after the change, proven by tests asserting the wording.
- **SC-004**: The number of screens in scope that withhold a control without saying why falls to zero.
- **SC-005**: For every capability in scope, a refusal reaching it directly and a refusal through the interface carry the same explanation.

## Assumptions

- Whether the product supplied something is treated as an ability like any other. It is a standing property that decides whether a capability is offered, which is what the protection on the contact representing somebody already is.
- Deciding on a listing version belongs to the instance, not to anybody's vaults, so naming it does not bend the invariant that the administrator capacity never reaches inside them.
- Refusals keep the shape they have today. What somebody may not reach stays not found; what plainly exists keeps its explanatory refusal.
- The administrator capacity stays what it is, a guard over the instance as a whole. There is a well known way to grant one kind of person every ability at once, and using it here would contradict that invariant.
- Refusals belonging to what somebody typed, to a third party service, or to a state found while the work runs stay where they are. No screen can ask them in advance.
- No new data and no schema change.
