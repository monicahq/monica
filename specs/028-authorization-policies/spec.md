# Feature Specification: Authorization Policies and Gates

**Feature Branch**: `2026-09-12-authorization-policies`

**Created**: 2026-09-12

**Status**: Draft

**Input**: User description: "Add Policies and Gates. Follow https://laravel.com/framework/docs/authorization"

## Starting point

The application holds no policy and no gate. Permission is decided in three unrelated places: route model binding scopes every lookup to the account and to the vault the address names, controllers repeat state checks in 61 places, and actions refuse in 227 places. Screens ask none of the three, so a template deciding whether to offer a control reads the rule a second time from a view model with nothing keeping the two in step. The vault ceiling is the worked example: one copy in `CreateVault`, another in `VaultFormViewModel`. Monica is mono user, so this is not about who may reach whose data. That is settled and stays settled. It is about giving each ability one name, one answer, and every surface the same way to ask it.

## Clarifications

### Session 2026-09-12

- Q: Where should the authorization rules live, given the constitution's fixed `app/` tree lists no Policies folder? → A: Amend the constitution to admit `app/Policies`, then use per record policy classes.
- Q: How much of the existing authorization code should become named abilities? → A: The starting set only. The 61 controller checks and 227 action refusals stay as they are.
- Q: Which layer asks the named ability and performs the refusal? → A: The action. Controllers add no check, and the ability is asked for the action's own user rather than the signed in session.
- Q: When an ability refuses, what does the screen present? → A: The control stays, marked unavailable, with the reason shown beside it. Nothing is hidden.
- Q: How should the API report a record's permitted capabilities? → A: It does not report them. A client attempts the request and reads the explanation in the refusal.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - One rule, asked everywhere (Priority: P1)

A capability's rule is declared once as an ability. The screen offering the control, the action performing it, and the test proving it all ask that ability. Changing the rule changes one place, and no surface keeps an older copy.

**Why this priority**: this is the defect the feature exists to fix. Without it, abilities are only a fourth place a rule can live.

**Independent Test**: drive an account to the free offer vault ceiling, confirm the screen marks the control unavailable and the action refuses, then change the rule in its one home and confirm both surfaces follow.

**Acceptance Scenarios**:

1. **Given** an account at the free offer vault ceiling, **When** the vaults screen renders, **Then** the control that creates a vault is marked unavailable and carries the ceiling as its reason.
2. **Given** the same account, **When** the creation is requested anyway, **Then** it is refused with the same explanation the screen gave.
3. **Given** an account below the ceiling, **When** the screen renders, **Then** the control is active and the creation succeeds.

---

### User Story 2 - The contact representing you is never deletable (Priority: P2)

The contact representing the account holder cannot be deleted. That rule is written twice, once where the screen decides whether to offer the control and once where the action refuses, with nothing keeping the two in step.

**Why this priority**: a real second copy of a rule, but a small one, and the two copies agree today. Fixing it prevents a drift that has not happened yet.

**Independent Test**: change the rule in its one home and confirm the screen and the refusal both follow it without a second edit.

**Acceptance Scenarios**:

1. **Given** the contact representing the account holder, **When** its edit screen renders, **Then** the delete control is marked unavailable and carries as its reason that this contact stays.
2. **Given** any other contact, **When** its edit screen renders, **Then** the delete control is active and deleting works.

---

### User Story 3 - The same refusal through the API (Priority: P3)

A client that is not a Blade screen is refused by the same rule and receives the same explanation, so it can tell its user why rather than showing a generic error. The API does not report in advance what is permitted.

**Why this priority**: it keeps a capability behaving identically whichever surface reaches it. Nothing regresses while it is missing.

**Independent Test**: request a capability through the API for an account at the ceiling and confirm the refusal carries the same translated explanation the screen shows.

**Acceptance Scenarios**:

1. **Given** an account at the vault ceiling, **When** it requests a vault through the API, **Then** it is refused with the same explanation the screen gives.
2. **Given** an account below the ceiling, **When** it makes the same request, **Then** the vault is created.

---

### Edge Cases

- A job or a scheduled command runs an action with nobody signed in. The rule must stay decidable, and must not permit everything because there is no user to ask about.
- A private share link opens a record with no signed in user. Abilities must not become a second way to reach shared content, which FR-006 settles by refusing whenever there is no user, and FR-009 proves.
- An account is metered on the hosted deployment and by nobody when self hosted. One named ability must answer correctly on both.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Each ability in scope MUST be declared once under a stable name, and MUST be the only statement of that rule in the application.
- **FR-002**: A screen MUST decide whether a control is active by asking the ability, never by recomputing the rule.
- **FR-003**: A refused control MUST stay on the screen, marked unavailable and carrying its reason, rather than being hidden. It MUST be announced as unavailable to a screen reader, MUST NOT be activable, and its reason MUST be translated like any other copy.
- **FR-004**: The action performing a capability MUST refuse it by the same ability that decided whether the control was active, MUST carry the explanation the user needs, and MUST NOT reduce an explanation the product gives today to a bare denial. Controllers MUST NOT carry a copy of the check.
- **FR-005**: A refusal MUST NOT reveal that a record exists when the asker may not reach it.
- **FR-006**: An ability MUST be answerable about a named user rather than only about the signed in session, so a job or a command gets the same answer. Asked with no user at all it MUST refuse, without exception: a private share link reaches content by its own path and never by asking an ability.
- **FR-007**: Existing scoping of records to the account and to the vault the address names MUST keep refusing as if the record did not exist, and MUST NOT be weakened or moved.
- **FR-008**: The API MUST refuse a capability by the same ability as the interface and MUST return the same explanation. It MUST NOT report permitted capabilities in advance.
- **FR-009**: Every ability MUST have a test proving both the permitted and the refused answer, including the answer when nobody is signed in.
- **FR-010**: An ability added without the tests FR-009 requires MUST fail the suite rather than pass unnoticed. Whether a screen holds a conditional control with no ability behind it is not mechanically detectable at proportionate cost, and stays a review question.
- **FR-011**: The administrator capacity MUST keep governing the instance only, and MUST NOT become a name that permits reaching the contents of somebody's vaults.
- **FR-012**: This MUST NOT create a role, a membership, or a permission between users.

### Key Entities

- **Ability**: the named question of whether somebody may do one thing, optionally to one record. It is the unit a screen, an action and a test share. It holds no data and is not stored.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: For every capability in scope the rule is stated in exactly one place inside the application, verified by removing it and observing every surface change together.
- **SC-002**: Across every screen in scope, no control that would refuse can be activated.
- **SC-003**: Every explanation the product gives today is reproduced after the change, proven by the action tests asserting the wording.
- **SC-004**: No account can reach, or learn of the existence of, a record of another account, proven by the compartmentalisation tests passing unchanged.
- **SC-005**: For every capability in scope, a refusal through the API and a refusal through the interface carry the same explanation.

## Assumptions

- Ownership scoping stays where it is. Route model binding refuses a record at the query, before anything runs, while a named ability can only refuse after the record is loaded. Moving it would weaken the separation the product requires, so this adds to it rather than replacing it.
- Refusals keep the shape they have today. A record somebody may not reach stays not found. A ceiling, where the record plainly exists and the user knows it, keeps its explanatory refusal.
- The starting set is the three capabilities where a screen and an action each hold a copy of one rule: the free offer ceilings on vaults and contacts, and the protection on the Me contact. The frozen template version was in this list until planning found it already correct, with one source in `VersionStatusEnum` that its screen already honours.
- The administrator stays a middleware guarding its route group. It is a capacity over the instance, not a capability over records, and not the kind of question this feature names.
- No new data and no schema change. Abilities are computed from what the account and the record already hold.
- Because the API reports nothing in advance, a client wanting to draw a control as unavailable must attempt the request or hold its own copy of the rule. `BillingResource` already publishes both `limits` and `usage`, so recomputing a ceiling client side is easy and likely. That copy sits outside the application, is accepted, and is the first thing to revisit if the native clients find it painful.
- The constitution amendment admitting `app/Policies` to the fixed `app/` tree is merged, as version 2.3.0. That prerequisite is met and blocks nothing further.
- The 61 controller checks and the 227 action refusals are out of scope and stay exactly as they are. They hold no duplicated rule, so converting them would add indirection without changing behavior.
