# Feature Specification: Email the administrators about support requests

**Feature Branch**: `2026-11-24-support-administrator-notifications`

**Created**: 2026-11-24

**Status**: Draft

**Input**: User description: "Sur une nouvelle branche depuis Main. J'aimerais que les administrateurs d'une instance reçoivent un courriel lorsqu'un utilisateur crée un nouveau ticket de support ou qu'il envoie un message en réponse à un thread déjà existant."

**Second input**: "Je veux que le courriel contienne la nature du message qui est envoyé ainsi que le nom
du compte et le numéro de l'ID. Il doit y avoir un lien pour aller sur le thread au complet dans le
panneau d'admin. Cet email ne doit être envoyé que si évidemment la fonctionnalité de support existe."

## Context

Support requests (`specs/114-support-requests`) reach the administrators of an instance only through
the queue in the admin space and the To handle count in its navigation. That spec decided, in its
FR-009, that administrators are never emailed. In practice an administrator has to open the admin
space to learn that somebody is waiting, and a request can sit unseen for days.

This feature reverses the first half of that FR-009: administrators are now emailed when a user opens a
request and when a user writes again in a request that already exists. The To handle count in the
admin navigation stays as it is.

Support is switched on or off for the whole instance in its configuration, and it is off by default.
When it is off, support is absent everywhere, and these emails are never sent.

An account has no name of its own. Everywhere in the admin space, an account is named after the person
on it and identified by its number, and the emails do the same.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - An administrator learns that a new request arrived (Priority: P1)

A user opens a support request. Every active administrator of the instance receives an email saying a
new request is waiting, with its reference, its subject, its category, the account it came from (its
name and its number), who sent it, what they wrote, and a link that opens the full request in the
admin space.

**Why this priority**: a new request is the moment somebody starts waiting. Without the email, the
wait lasts until an administrator happens to open the admin space.

**Independent Test**: with two active administrators, open a request as a regular user and check that
each administrator receives one email linking to that request in the admin space.

**Acceptance Scenarios**:

1. **Given** an instance with support switched on and two active administrators, **When** a user opens
   a request "Cannot import my contacts", **Then** each administrator receives one email saying that a new request was opened, and naming
   the request's reference, its subject, its category, the name and the number of the account it came
   from, the name of the user who opened it and the body of the first message, with a link to the full
   request in the admin space.
2. **Given** the same instance, **When** a user opens a request, **Then** the user still receives the
   copy of their request they receive today, unchanged.
3. **Given** a request opened with two attached files, **When** the administrators are emailed,
   **Then** the email says that files are attached and points to the request to see them, without
   carrying the files.
4. **Given** a request opened through the API or by an assistant over MCP, **When** it is created,
   **Then** the administrators are emailed exactly as for a request opened from the help screen.

---

### User Story 2 - An administrator learns that a user wrote again (Priority: P1)

A user adds a message to a request that already exists, whatever its status: to handle, waiting on
the user, or solved (which reopens it). Every active administrator receives an email saying the user
wrote again in an existing request, with the reference, the subject, the account it came from (its
name and its number), who wrote, what they wrote, and a link to the full request.

**Why this priority**: an answer from the user to a request waiting on them is the other moment
somebody starts waiting again. A solved request reopened by a message is invisible otherwise.

**Independent Test**: with one active administrator, add a message to a request waiting on the user
and check that the administrator receives one email linking to the request.

**Acceptance Scenarios**:

1. **Given** a request waiting on the user, **When** the user adds a message, **Then** each active
   administrator receives one email saying that the user wrote again in an existing request, and
   naming the reference, the subject, the name and the number of the account, the user's name and the
   body of the new message, with a link to the full request in the admin space.
2. **Given** a solved request, **When** the user adds a message, **Then** the email says the request
   was reopened by that message.
3. **Given** a request already to handle, **When** the user adds a second message before anybody
   answered, **Then** the administrators receive one email for that message as well.

---

### User Story 3 - Administrators are not emailed about their own work (Priority: P2)

Replies and internal notes written by administrators, and status changes made by administrators, send
nothing to the administrators. An administrator who uses support from their own account is not
emailed about their own messages.

**Why this priority**: an email that announces what the reader just did is noise, and noise teaches
people to ignore the emails that matter.

**Independent Test**: send a reply and write an internal note as an administrator, then check that no
administrator received an email.

**Acceptance Scenarios**:

1. **Given** an open request, **When** an administrator sends a reply or writes an internal note,
   **Then** no administrator is emailed.
2. **Given** an administrator who also opens a request from their own account, **When** they open it,
   **Then** every other active administrator is emailed and they are not.

---

### Edge Cases

- **No active administrator.** The help screen is not offered then, but a request can still exist and
  receive a message. The message is saved as today and nobody is emailed.
- **A deactivated administrator, or a user whose administrator capacity was withdrawn**, receives
  nothing.
- **Support switched off on the instance.** No email is sent, whichever way a request or a message
  would arrive. Switching support back on does not send the emails that were not sent while it was off.
- **The two emails are told apart before they are opened.** Their subjects differ, so an administrator
  sees from the inbox whether a request is new or a user wrote again.
- **Sending the email fails.** The user's request or message is saved and the user sees the same
  confirmation as today. The failure never reaches the user.
- **The capacity changes between the event and the delivery.** Recipients are the active
  administrators at the moment the user's request or message is saved.
- **An administrator's language.** Each email is written in the language of the administrator who
  receives it, not in the language of the user who wrote. The user's own words are left as they wrote
  them.
- **A long message.** The email carries the message as the user wrote it, as the copy sent to the user
  already does.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: When a user opens a support request, the system MUST send one email to every active
  administrator of the instance, except the user who opened it.
- **FR-002**: When a user adds a message to an existing support request, whatever its status, the
  system MUST send one email to every active administrator of the instance, except the user who wrote
  it.
- **FR-003**: The email about a new request MUST say in its subject and in its first line that a new
  request was opened. It MUST name the request's reference, its subject, its category, the account's
  name and number, the name of the user who opened it, the body of the first message and whether files
  are attached, and MUST link to the full request in the admin space.
- **FR-004**: The email about a new message MUST say in its subject and in its first line that a user
  wrote again in an existing request. It MUST name the request's reference, its subject, the account's
  name and number, the name of the user who wrote, the body of the new message, whether files are
  attached and whether the message reopened a solved request, and MUST link to the full request in the
  admin space.
- **FR-005**: The system MUST NOT email administrators about administrator replies, internal notes or
  status changes.
- **FR-006**: The emails MUST be sent the same way whether the request or message arrives from the help
  screen, the API or the MCP server.
- **FR-007**: Sending these emails MUST NOT delay or fail the user's request or message. The user's
  experience is unchanged.
- **FR-008**: Each email MUST be written in the receiving administrator's language.
- **FR-009**: Each email MUST say that replying to it does not reach the user, and that the answer is
  written from the request in the admin space.
- **FR-010**: Each email MUST be recorded in the receiving administrator's email history, as every
  other email the application sends, under a kind of its own for each of the two emails.
- **FR-011**: The copy sent to the user when they open a request MUST stay as it is today.
- **FR-012**: The system MUST NOT send either email while support is switched off on the instance,
  whichever surface the request or the message arrives from.
- **FR-013**: The account's name and number in the email MUST be the ones the admin space shows for
  that account.
- **FR-014**: The admin navigation MUST keep showing the To handle count. This feature replaces only
  the part of FR-009 of `specs/114-support-requests` that forbade emailing administrators.

### Key Entities

- **Administrator**: a user of the instance holding the administrator capacity and still active. The
  recipients of both emails.
- **Account**: where the request came from. Named after the person on it and identified by its number,
  as in the admin space. Already exists.
- **Support request**: what a user asked, with its reference, subject, category and status. Already
  exists.
- **Support message**: one message from the user in a request, with its body and attached files.
  Already exists.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: For every request opened and every message added by a user, each active administrator
  other than the author receives exactly one email, and no other person receives one.
- **SC-002**: An administrator can go from the email to the full request in the admin space in one
  click.
- **SC-005**: While support is switched off, zero emails about support are sent to administrators.
- **SC-006**: From the subject alone, an administrator can tell a new request from a new message in an
  existing one in every case.
- **SC-003**: Administrators receive zero emails for their own replies, notes and status changes.
- **SC-004**: Opening a request or adding a message succeeds for the user in every case where it
  succeeds today, including when the email cannot be sent.

## Assumptions

- Every active administrator receives both emails. There is no setting to stop receiving them in this
  version; an instance with an administrator who does not want them withdraws the capacity or asks
  for a setting later.
- One email is sent per event, with no grouping or digest. Support volume on an instance is low enough
  for this.
- The email carries the user's message in full. Administrators can already read it in the admin space,
  so it reveals nothing new to them.
- "The number of the ID" is read as the account's number, the one the admin space shows and addresses
  the account by. The request's own reference (`MON-` followed by its number) is carried as well, since
  it is how the admin space names a request.
- The email links to the request, not to the account. The account's number lets an administrator find
  it in the admin space.
- Attached files are not carried by the email; it only says files are attached.
- The existing support screens, API, MCP tools and their documentation do not change, since what they
  answer does not change.
