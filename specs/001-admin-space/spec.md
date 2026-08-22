# Feature Specification: Admin Space

**Feature Branch**: `2026-08-20-admin-space`

**Created**: 2026-08-20

**Status**: Draft

**Input**: User description: "Admin space for the project"

## User Scenarios & Testing *(mandatory)*

The admin space is a separate area of the application for the people who run the instance, not
for the people who use it. It exists because decisions that belong to the operator (letting a
marketplace submission into the catalogue, answering a support question about an account,
knowing how much the instance is holding) currently have nowhere to happen. Today the
marketplace moderation decisions exist as code with no way for a human to reach them.

Nothing in this area gives an administrator access to what a user wrote about the people in
their vaults. Administrators see accounts, not contacts.

### User Story 1 - Moderate marketplace submissions (Priority: P1)

An administrator opens the admin space, sees every marketplace submission waiting for a
decision, reads what the author submitted (name, description, category, the payload of the
version), and either lets it into the catalogue or rejects it with a reason the author will
read. The decision is recorded against their name.

**Why this priority**: the marketplace cannot function at all without it. Authors can already
submit, and a submission sits forever because nobody can decide. This is the one slice that
turns existing dead code into a working feature, and it is valuable on its own even if no other
part of the admin space is ever built.

**Independent Test**: sign in as an administrator, submit a listing as a regular user, approve
it from the queue, and confirm it appears in the catalogue and that the author is told. Repeat
with a rejection and confirm the reason reaches the author.

**Acceptance Scenarios**:

1. **Given** a submission awaiting moderation, **When** an administrator opens the moderation
   queue, **Then** the submission is listed with its author, category, submitted date and how
   long it has been waiting.
2. **Given** an administrator viewing a submission, **When** they approve it, **Then** the
   version enters the catalogue, the listing becomes publicly installable, and the decision is
   attributed to that administrator with a timestamp.
3. **Given** an administrator viewing a submission, **When** they reject it without giving a
   reason, **Then** the rejection is refused and they are told a reason is required.
4. **Given** an administrator viewing a submission, **When** they reject it with a reason,
   **Then** the listing is not published, the author can read the reason, and the author can
   submit a new version afterwards.
5. **Given** a submission already decided by another administrator, **When** an administrator
   tries to decide it again, **Then** the attempt is refused and they are shown the decision
   that already stands.
6. **Given** an approved version already installed by users, **When** a later version of the
   same listing is approved, **Then** existing installations are unchanged and remain on the
   version they installed.

---

### User Story 2 - Look up an account to answer a support question (Priority: P2)

Somebody writes in with a problem. An administrator searches for their account by email
address, opens it, and sees the facts needed to answer: when the account was created, whether
it is self-hosted, whether it has lifetime access and when it was purchased, how much storage
it uses against its limit, how many vaults and contacts it holds as counts, and for the person
on the account their name, email, whether the email is confirmed, whether they sign in through
a single sign-on provider, whether two factor authentication is on, whether the account is
active, and when they last signed in.

**Why this priority**: it is the difference between answering a support email in a minute and
asking an engineer to run a database query. It depends on nothing in Story 1 and can ship
separately.

**Independent Test**: create an account with a vault and some contacts, search for its email
address in the admin space, and confirm every listed fact is shown and correct, and that no
contact name, field value or uploaded file is reachable from anywhere in that screen.

**Acceptance Scenarios**:

1. **Given** an administrator in the admin space, **When** they search for a full email
   address that exists, **Then** the matching account is found and can be opened.
2. **Given** an administrator in the admin space, **When** they search for a partial name or
   partial email, **Then** matching accounts are listed, and searching for something that
   matches nothing says so plainly rather than showing an empty screen with no explanation.
3. **Given** an administrator viewing an account, **When** the account holds vaults and
   contacts, **Then** only counts and totals are shown and no contact, field value, file or
   note content is displayed or linkable.
4. **Given** an administrator viewing an account whose person was deleted, **When** the screen
   loads, **Then** the account is still shown with the deletion made explicit rather than
   failing.
5. **Given** an administrator viewing an account, **When** they look at storage, **Then** usage
   is shown against the limit in a unit a human reads, and an account with no limit is shown as
   unlimited rather than as zero.

---

### User Story 3 - Act on an account when support requires it (Priority: P2)

An administrator, from an account they are viewing, can take the small number of actions
support genuinely requires: deactivate a user so they can no longer sign in, reactivate them,
change the storage limit, and grant or withdraw lifetime access. Every one of these actions
warns before it is applied and is recorded against the administrator who took it.

**Why this priority**: reading an account without being able to act on it leaves the operator
still needing an engineer for the cases that matter, such as an abusive user or a customer whose
purchase did not register. It is P2 rather than P1 because a read-only lookup already removes
most of the pain.

**Independent Test**: deactivate a user from the admin space, confirm they can no longer sign
in and that their existing session no longer works, then reactivate them and confirm they can.

**Acceptance Scenarios**:

1. **Given** an administrator viewing an active user, **When** they deactivate them, **Then**
   they are warned first, and on confirming the user can no longer sign in and any current
   session of that user stops working.
2. **Given** a deactivated user, **When** an administrator reactivates them, **Then** the user
   can sign in again and nothing they owned was lost.
3. **Given** an administrator changing a storage limit, **When** they set a limit below what
   the account already uses, **Then** they are warned about what that means for the user, and
   on confirming no existing file is deleted while new uploads are refused.
4. **Given** an administrator granting lifetime access, **When** they confirm, **Then** the
   account reflects it immediately and the grant is attributed to that administrator.
5. **Given** any of these actions, **When** it completes, **Then** it appears in the record of
   administrator actions with who, what, which account and when.
6. **Given** an administrator viewing their own account, **When** they attempt to deactivate
   themselves, **Then** the attempt is refused with an explanation, because an administrator
   must not be able to lock themselves out.
7. **Given** an administrator viewing their own account, **When** they change their own storage
   limit or grant themselves lifetime access, **Then** the action is applied and recorded
   against them like any other, so a single-administrator instance is never blocked.

---

### User Story 4 - Manage who administers the instance (Priority: P2)

An administrator can see who else holds the administrator capacity, grant it to an existing user
by email address, and withdraw it from someone who should no longer have it. The first
administrator of an instance is designated outside the application, and that route stays
available so an instance left with nobody can be recovered.

**Why this priority**: without it, every change to the operator team needs server access, which
is exactly the friction the admin space exists to remove. It is P2 rather than P1 because the
bootstrap route alone is enough to get a first administrator moderating.

**Independent Test**: with two users on an instance, grant the capacity to the second from the
admin space, confirm they can reach it and the first cannot withdraw their own capacity, then
withdraw the second and confirm the admin space is closed to them again.

**Acceptance Scenarios**:

1. **Given** an administrator, **When** they open the list of administrators, **Then** everyone
   currently holding the capacity is shown.
2. **Given** an administrator, **When** they grant the capacity to an existing user by email
   address, **Then** they are warned first, and on confirming that user can reach the admin
   space and the grant is recorded against the administrator who made it.
3. **Given** an administrator, **When** they enter an email address belonging to nobody,
   **Then** the grant is refused and they are told no such user exists.
4. **Given** two administrators, **When** one withdraws the capacity from the other, **Then**
   they are warned first, and on confirming the other administrator can no longer reach the
   admin space, including on a screen they already had open.
5. **Given** an administrator, **When** they attempt to withdraw their own capacity, **Then**
   the attempt is refused with an explanation.
6. **Given** an instance with exactly one administrator, **When** anyone attempts to withdraw
   that capacity, **Then** the attempt is refused and the reason states an instance must keep at
   least one administrator.

---

### User Story 5 - See the state of the instance at a glance (Priority: P3)

An administrator opens the admin space and, before doing anything else, sees how the instance
is doing: how many accounts and users exist, how many signed in over the last week, how many
vaults and contacts are held, total storage used, how many marketplace submissions are waiting,
and how many accounts were created over the last thirty days.

**Why this priority**: useful for knowing whether the instance is growing or stuck, but nobody
is blocked without it, and it is the part most likely to change shape once the operator has
used the rest.

**Independent Test**: seed a known number of accounts, users and pending submissions, open the
overview, and confirm every number matches.

**Acceptance Scenarios**:

1. **Given** an administrator, **When** they open the admin space, **Then** the overview is the
   first thing they see and every figure states what period it covers.
2. **Given** an empty instance, **When** the overview loads, **Then** it shows zeros and reads
   as an empty instance rather than as an error.
3. **Given** submissions waiting for moderation, **When** the overview loads, **Then** the
   count is shown and leads to the moderation queue in one step.

---

### Edge Cases

- An author withdraws a submission while an administrator has it open. The decision must be
  refused with an explanation, not applied to a withdrawn submission.
- Two administrators decide the same submission at the same time. Exactly one decision takes
  effect and the second administrator is told which.
- A rejected listing is resubmitted. The queue shows it as a new submission and the earlier
  rejection reason remains readable.
- A user is deactivated while signed in. The next thing they do must fail as not signed in.
- An administrator loses administrator rights while in the admin space. The next screen they
  open must be unreachable to them.
- The capacity is granted to a user who is deactivated, or a user is deactivated after being
  granted it. A person who cannot sign in cannot administer, and the list must make that plain
  rather than showing them as an active administrator.
- Two administrators withdraw the capacity from each other at the same time, or an instance is
  down to two administrators and both withdrawals arrive together. At least one must survive.
- A regular user guesses an admin space address. It must be indistinguishable from an address
  that does not exist.
- A self-hosted account appears in the lookup. Fields that only make sense for a hosted account
  (billing email, purchase) must read as not applicable rather than as empty.
- An account whose person was soft deleted, and an account holding no vaults at all.

## Requirements *(mandatory)*

### Functional Requirements

**Access**

- **FR-001**: The system MUST recognise a distinct administrator capacity, held by a person
  independently of the account and vaults they use as a regular user.
- **FR-002**: The system MUST make every screen of the admin space unreachable to anyone
  without that capacity, and MUST make the refusal indistinguishable from an address that does
  not exist.
- **FR-003**: The system MUST require an administrator to be signed in as themselves, with no
  separate shared credential, so that every action in the admin space is attributable to a
  named person.
- **FR-004**: The system MUST NOT allow anyone to gain the administrator capacity through the
  regular application, including by signing up, by editing their own profile, or by any
  self-service route.
- **FR-005**: Administrators MUST be able to grant the administrator capacity to an existing
  user of the instance, and to withdraw it, from inside the admin space.
- **FR-006**: The first administrator MUST be designated outside the application by whoever
  runs the instance, and that route MUST remain available so an instance left with no
  administrator can be recovered.
- **FR-007**: The system MUST refuse to withdraw the capacity from the last remaining
  administrator, so an instance can never end up with nobody able to moderate.
- **FR-008**: An administrator MUST NOT be able to withdraw their own capacity. Another
  administrator does it, for the same reason an administrator cannot deactivate themselves.
- **FR-009**: The system MUST list who currently holds the administrator capacity, and MUST
  warn before a grant or a withdrawal is applied.
- **FR-010**: The system MUST keep the admin space visually and structurally separate from the
  regular application, so that an administrator always knows which of the two they are in.

**Marketplace moderation**

- **FR-011**: The system MUST list every marketplace submission awaiting a decision, oldest
  first, showing author, listing name, category, submitted date and waiting time.
- **FR-012**: Administrators MUST be able to read everything the author submitted for a version
  before deciding, including the description and the content of the version being submitted.
- **FR-013**: Administrators MUST be able to approve a submission, which publishes the listing
  and makes that version the one new installations receive.
- **FR-014**: Administrators MUST be able to reject a submission, and the system MUST require a
  reason and make that reason readable by the author.
- **FR-015**: The system MUST refuse any decision on a submission that is no longer awaiting
  one, and MUST state what its current state is.
- **FR-016**: The system MUST record, for every decision, which administrator took it and when.
- **FR-017**: The system MUST leave existing installations untouched when a later version of the
  same listing is approved.

**Account lookup**

- **FR-018**: Administrators MUST be able to find an account by email address, by partial email
  address and by person name.
- **FR-019**: The system MUST show, for a found account: creation date, whether it is
  self-hosted, lifetime access and purchase date, billing email, storage used against its
  limit, and counts of vaults and contacts.
- **FR-020**: The system MUST show, for the person on that account: name, email, whether the
  email is confirmed, whether they use single sign-on, whether two factor authentication is
  enabled, whether they are active, and when they last signed in.
- **FR-021**: The system MUST NOT expose any content a user entered inside a vault, including
  contact names, field values, notes and uploaded files, anywhere in the admin space, and MUST
  NOT provide a link that leads to it.
- **FR-022**: The system MUST NOT provide any way for an administrator to sign in as, or act as,
  a user.

**Account actions**

- **FR-023**: Administrators MUST be able to deactivate and reactivate a user, and deactivation
  MUST take effect on any session that user already has open.
- **FR-024**: Administrators MUST be able to change an account's storage limit, including
  setting no limit, and lowering a limit below current usage MUST NOT delete anything.
- **FR-025**: Administrators MUST be able to grant and withdraw lifetime access on an account.
- **FR-026**: The system MUST warn the administrator before applying any action that a user
  would experience as a loss, and MUST require an explicit confirmation.
- **FR-027**: The system MUST NOT provide a way to delete an account or a user from the admin
  space in this feature.

**Record of administrator actions**

- **FR-028**: The system MUST record every action taken in the admin space with the
  administrator who took it, what was done, what it was done to, and when.
- **FR-029**: The system MUST make that record readable inside the admin space, most recent
  first, and filterable by administrator and by account.
- **FR-030**: The system MUST keep that record append only. No administrator may edit or delete
  an entry.
- **FR-031**: The system MUST keep the record of administrator actions separate from the
  per-account activity a user sees, so that operator activity never appears to a user as
  something they did.

**Instance overview**

- **FR-032**: The system MUST show counts of accounts, users, vaults and contacts, total storage
  used, users who signed in over the last seven days, accounts created over the last thirty
  days, and submissions awaiting moderation.
- **FR-033**: Every figure on the overview MUST state the period it covers, and the overview
  MUST read correctly on an instance with no data.

**Across the whole area**

- **FR-034**: Every string in the admin space MUST be translatable and MUST be translated in
  every locale the application ships.
- **FR-035**: Every list in the admin space MUST remain usable as the instance grows, showing
  results in pages rather than everything at once.

### Key Entities

- **Administrator**: a person who holds the operator capacity for the instance. Identified by
  name so that decisions can be attributed. Distinct from the account they use as a user. The
  capacity is granted and withdrawn by another administrator, and carries who granted it and
  when.
- **Moderation queue item**: a marketplace submission awaiting a decision. Carries the author,
  the listing it belongs to, the version submitted, when it was submitted and how long it has
  waited.
- **Moderation decision**: approval or rejection of a submission. Carries the deciding
  administrator, the moment, and for a rejection the reason the author reads.
- **Account summary**: the operator-facing view of an account. Creation date, self-hosted or
  not, lifetime access, purchase, billing email, storage used and limit, counts of vaults and
  contacts. Explicitly excludes anything held inside a vault.
- **Administrator action record**: an append-only entry describing one thing an administrator
  did. Who, what, to which account or submission, and when.
- **Instance overview**: the current counts and totals for the instance as a whole, each tied to
  the period it describes.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: An administrator can decide a marketplace submission, approving it or rejecting it
  with a reason, in under two minutes from opening the admin space, without anyone touching the
  database.
- **SC-002**: 100% of submissions awaiting a decision appear in one queue, and no submission can
  be decided twice.
- **SC-003**: An administrator can answer "what plan is this person on, how much storage do they
  use, and when did they last sign in" for any account within thirty seconds of being given an
  email address.
- **SC-004**: 100% of actions taken in the admin space are attributable to a named administrator
  and remain retrievable afterwards.
- **SC-005**: A user who is not an administrator cannot tell that the admin space exists.
  Every attempt to reach it is indistinguishable from reaching an address that does not exist.
- **SC-006**: No content a user entered inside a vault is reachable from any screen of the admin
  space. Zero paths lead there.
- **SC-007**: Support requests that currently require an engineer to run a database query drop
  to zero for the questions the account lookup answers.
- **SC-008**: The admin space is fully usable in every locale the application ships, with no
  untranslated string.
- **SC-009**: Every list in the admin space returns its first page in under 500 milliseconds at
  the 95th percentile, measured server side, on an instance holding one hundred thousand accounts.
- **SC-010**: Changing who administers the instance takes no server access and under a minute,
  and no sequence of grants or withdrawals can leave the instance with zero administrators.

## Assumptions

- The admin space serves whoever operates the instance. On a hosted instance that is the team
  running it, on a self-hosted instance it is the person who installed it. The same feature
  covers both.
- Administrator status is granted and withdrawn by another administrator inside the admin space,
  and never through the regular application. Nobody promotes themselves.
- The first administrator of an instance is designated outside the application, by whoever runs
  it. That route is also the recovery path if an instance is somehow left with none.
- An administrator cannot withdraw their own capacity. This extends the rule you chose for
  self-deactivation, on the grounds that both are ways of locking yourself out. Changing storage
  limits and lifetime access on your own account stays allowed, so a single-administrator
  instance is never stuck.
- Administrators never see personal data held in vaults. This is treated as a hard boundary
  rather than a default that can be relaxed later, because the product's promise to its users
  depends on it.
- Impersonating a user is out of scope. It is the usual way an operator ends up reading personal
  data by accident.
- Deleting accounts and users is out of scope for this feature. It is a heavier decision than
  the actions here, with data retention consequences worth specifying on its own.
- Billing itself is out of scope. The admin space reads purchase facts and can grant lifetime
  access, but taking payment, refunding and invoicing are not part of it.
- Marketplace moderation continues to attribute decisions to a moderator's name rather than
  binding them to a user record, matching how the existing moderation behaviour already works.
- The instance overview shows figures computed when the screen is opened. Historical trends and
  charts over time are out of scope.
- Email notification of a moderation decision reaches the author through whatever the
  application already uses to email users. No new channel is introduced.
- Regular users keep no visibility of the admin space. It does not appear in their navigation.
