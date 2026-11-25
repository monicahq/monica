# Feature Specification: Change the template of a contact

**Feature Branch**: `2026-11-25-contact-template-change`

**Created**: 2026-11-25

**Status**: Draft

**Input**: User description: "Make it possible, through the interface, the API and the MCP server, to change the template a contact uses. Write complete and extremely clear documentation in the help centre, and point at it from a help bubble in the interface. Document the API and the MCP tool too. In the interface, the feature lives on the Edit contact screen, following the design `Edit Contact.dc.html`."

## Context

Today a contact stays on the template it was created with. It can move to another version of that same
template (the notice on the contact page, the version history), but nothing in the interface, the API
or the MCP server moves it to another template. The product already promises otherwise in two places:
the new contact form says "It can be changed on the contact afterwards", and the help centre page
`templates.change` tells the reader to "Move those contacts to another template first" before deleting
a template. Both are false until this feature ships.

The work of moving a contact (carrying answers over, setting aside what has no field, stopping and
restarting reminders, keeping a snapshot first) already exists and is used for version changes. This
feature opens it to another template and gives it a screen, an endpoint, a tool and a guide.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Change a contact's template from the Edit contact screen (Priority: P1)

Somebody created Dwight with the Basic contact template, and later built a template called Coworker
with a Work page. On Dwight's Edit contact screen, they pick Coworker, read what will happen to what is
already written about Dwight, and confirm. Dwight's page now shows the pages, sections and fields of
Coworker. Every answer that has a matching field on Coworker is still there; every answer that has none
is listed under Unused answers, not deleted.

**Why this priority**: it is the feature. Without it the other stories have nothing to document or
expose, and two shipped sentences stay false.

**Independent Test**: create a contact on one template with a few answers, change it to another
template that shares some fields and lacks others, and check the contact page and Unused answers.

**Acceptance Scenarios**:

1. **Given** an account with at least two active contact templates that each have a published version, **When** the reader opens a contact's Edit contact screen, **Then** the screen names the template the contact uses and offers the others.
2. **Given** the reader picks another template, **When** they confirm, **Then** the contact uses the published version of that template, the reader returns to the contact page, and a message says which template the contact uses now.
3. **Given** the contact held answers, **When** the change is confirmed, **Then** each answer whose field has a matching field on the new template appears in that field, and each other answer appears under Unused answers with nothing deleted.
4. **Given** a reminder runs on a date that goes to Unused answers, **When** the change is confirmed, **Then** that reminder stops and says why, and it starts again if the contact later moves to a template that has the field.
5. **Given** the account has only one active contact template, **When** the reader opens the Edit contact screen, **Then** no choice of template is offered and the screen does not suggest one exists.
6. **Given** the reader picks the template the contact already uses, **When** they confirm, **Then** nothing changes and nothing is recorded in the version history.

---

### User Story 2 - Know what will happen before confirming (Priority: P1)

Before the change is made, the reader is told, in plain words, what changing the template does to the
contact page: what moves, what is set aside, what stops, what stays, and that nothing is deleted. A
help bubble beside the choice opens a short explanation and links to the full guide in the help centre.

**Why this priority**: the change reshapes a whole contact page. Constitution principle VI requires
that nothing changes under anybody's feet without explicit acceptance, and principle VIII that nothing
disappears silently. A reader who does not understand the consequence has not accepted it.

**Independent Test**: open the screen, pick another template, and check that the consequences are
stated before anything is saved, and that the help bubble opens and links to the guide.

**Acceptance Scenarios**:

1. **Given** the reader has picked another template, **When** they have not yet confirmed, **Then** the screen states that matching answers move, unmatched answers go to Unused answers and are not deleted, reminders on those dates stop, the name and the picture do not change, and the current state is kept in the version history.
2. **Given** the reader has picked another template, **When** the confirmation opens, **Then** it lists, for that template, the answers that will go to Unused answers (each by its field label and section), the answers already set aside that will come back, and the reminders that will stop or run again.
3. **Given** nothing would be set aside, brought back, stopped or restarted, **When** the confirmation opens, **Then** it says so in one sentence instead of showing empty lists.
4. **Given** the confirmation is loaded after the reader picks a template, **When** it appears, **Then** a screen reader announces its content, and the reader can reach it without a mouse.
5. **Given** the reader opens the help bubble beside the template choice, **When** it is open, **Then** it explains the change in at most three sentences and links to the help centre guide on changing a contact's template.
6. **Given** the reader changes their mind, **When** they cancel, **Then** nothing is saved.
7. **Given** a field that will be set aside is sensitive and the reader cannot see sensitive fields, **When** the confirmation opens, **Then** it counts that answer without naming its field.

---

### User Story 3 - Go back to the previous template (Priority: P2)

Having moved Dwight to Coworker, the reader realises it was a mistake. In Dwight's version history, the
entry for the earlier Basic contact version offers to revert to it, as it does for an earlier version of
the same template. Reverting brings the earlier fields back, and the answers set aside by the change
come back with them.

**Why this priority**: constitution principle VI requires the snapshot taken before a move to be
restorable. Today the version history refuses to revert to a version of another template ("This
version belongs to another template"), which would leave a mistaken change one way only.

**Independent Test**: change a contact's template, then revert from the version history, and compare
the contact page and Unused answers with the state before the change.

**Acceptance Scenarios**:

1. **Given** a contact moved from one template to another, **When** the reader opens its version history, **Then** the entry for the earlier template's version offers to revert to it, provided that version is still published and its template is still active.
2. **Given** the reader reverts, **When** the revert is confirmed, **Then** the answers set aside by the change are back in their fields, and the reminders it stopped run again.
3. **Given** the earlier template's version has been retired or the template turned off, **When** the reader opens the version history, **Then** that entry says why it cannot be reverted to.

---

### User Story 4 - Change a contact's template through the API (Priority: P2)

A developer scripting a clean up moves every contact tagged as a coworker onto the Coworker template
with one request per contact, and reads back the contact with its new template.

**Why this priority**: constitution principle X. A capability is not complete until the API offers it
and it is tested.

**Independent Test**: send the request for a contact with a template of the account, and check the
response and the contact; send it with a template of another account, and check the refusal.

**Acceptance Scenarios**:

1. **Given** a contact and an active contact template of the same account with a published version, **When** the request names that template, **Then** the contact uses that template's published version and the response returns the contact with its template.
2. **Given** a contact and a template, **When** the developer asks for a preview of the change, **Then** the answer lists what the confirmation screen lists (answers set aside, answers coming back, reminders stopped and restarted) and nothing is changed.
3. **Given** the request names a template of another account, a template for another kind of record, a template turned off, or a template with no published version, **When** it is sent, **Then** it is refused with a message saying why and the contact is unchanged. The preview refuses the same templates with the same wording.
4. **Given** the request names a specific published version of another template, **When** it is sent, **Then** the contact uses that version.
5. **Given** the existing request that moves a contact to another version of its own template, **When** it is sent as before, **Then** it behaves exactly as it does today.

---

### User Story 5 - Change a contact's template through the MCP server (Priority: P2)

Somebody asks their assistant to "put Dwight on the Coworker template". The assistant lists the
templates, finds Coworker, previews the change, and tells the person what will happen ("Desk number
and Favourite paper go to Unused answers, and the reminder on his work anniversary stops. Shall I?").
Once the person agrees, it changes Dwight's template, answering exactly as the API does.

**Why this priority**: the MCP server exposes the API (principle X) and today offers no tool for
either a template change or a version change. The preview gives the person the same explicit consent
the confirmation screen gives (principle VI).

**Independent Test**: call the preview tool, then the change tool, with a contact and a template, and
compare each answer with the API's for the same request.

**Acceptance Scenarios**:

1. **Given** a contact and a template of the account, **When** the assistant calls the preview tool, **Then** the answer is the API's preview, nothing changes, and the tool is declared as only reading, so an assistant may call it without asking.
2. **Given** a contact and a template of the account, **When** the assistant calls the change tool, **Then** the contact uses the template and the answer is the contact as the API returns it.
3. **Given** the change tool's description, **When** an assistant reads it, **Then** it says to preview the change and show the result to the person before changing anything.
4. **Given** a contact of another account, **When** the assistant calls either tool, **Then** it answers that the contact does not exist.
5. **Given** a refused template, **When** the assistant calls either tool, **Then** it relays the API's refusal word for word.

---

### User Story 6 - Read a guide that leaves no doubt (Priority: P1)

A reader who wonders what will happen to their notes, reminders and fields if they change a contact's
template opens the help centre and finds one page that answers it completely: when to change a
template, how to do it, exactly what happens to each kind of information, how to find what was set
aside, how to go back, and what cannot be done.

**Why this priority**: explicitly asked for, and the consequence of the change is the part readers
fear. A help bubble that points nowhere fails the build.

**Independent Test**: give the page to somebody who has never changed a template, ask them to predict
what happens to three given answers and one reminder, then do it and compare.

**Acceptance Scenarios**:

1. **Given** the help centre, **When** a reader searches for changing a contact's template, **Then** one page explains it, with the steps on the Edit contact screen, what happens to answers, unused answers, reminders, the name and the picture, the version history, and how to go back.
2. **Given** the page, **When** a reader follows its steps, **Then** every label it names is the label on the screen.
3. **Given** the existing pages that mention the change (the template pages and the contact editing page), **When** the feature ships, **Then** they describe it accurately and link to the new page, and no page still says it cannot be done.
4. **Given** the API documentation and the MCP tools list, **When** the feature ships, **Then** each documents the change with its parameters, its answer and its refusals.

---

### Edge Cases

- The chosen template's published version changes between opening the screen and confirming: the change goes to the version that was shown, if it is still published; otherwise it is refused with a message and nothing changes.
- The chosen template is turned off or deleted between opening the screen and confirming: refused with a message, nothing changes.
- A partial contact: it changes template the same way as any other contact.
- The current template has been turned off: the contact keeps using it, the screen names it, and the reader can move to an active template. The turned off template is not offered back.
- A contact whose answers were already set aside by an earlier change: those stay set aside unless the new template has a matching field, in which case they come back.
- The contact is the Me contact: it changes template like any other contact.
- A shared section carried by both templates: its answers stay where they are.
- The reader reaches the screen through a vault the contact is not in: not found, as for every contact screen.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The Edit contact screen MUST name the template the contact uses and, when the account has more than one active contact template with a published version, MUST let the reader choose another.
- **FR-002**: The templates offered MUST be exactly those offered on the new contact form: the account's active templates for contacts that have a published version, in the account's order. For each, the choice MUST list its published versions, the latest marked as such, so that the same choice also moves the contact to another version of its own template.
- **FR-003**: Changing the template MUST put the contact on the published version of the chosen template, unless a specific published version of that template is named through the API or the MCP server.
- **FR-004**: Before the change is saved, the screen MUST state its consequences and MUST require an explicit confirmation. The confirmation MUST list, for the chosen template, the answers that would go to Unused answers (by field label and section), the set aside answers that would come back, and the reminders that would stop or run again. It is loaded for the template the reader picks, not computed for every template when the screen opens.
- **FR-004a**: The preview and the change MUST decide which answer goes where by one and the same rule, so that what the preview lists is exactly what the change does.
- **FR-004b**: The preview MUST NOT name a sensitive field to somebody who cannot see sensitive fields; it counts that answer instead.
- **FR-005**: Each answer MUST follow its field to the matching field of the new template where one exists; each answer without one MUST be set aside, listed under Unused answers and never deleted.
- **FR-006**: A reminder running on a set aside date MUST be stopped with its reason shown, and MUST run again when a later change brings its field back.
- **FR-007**: The state of the contact before the change MUST be recorded in its version history, and the change MUST be recorded in the account's log.
- **FR-008**: Choosing the template the contact already uses MUST change nothing and record nothing, neither a version history entry nor a log entry. Sending the same change twice therefore leaves one entry.
- **FR-009**: The version history MUST offer to revert to a version of an earlier template when that version is still published and its template is active, and MUST say why otherwise.
- **FR-010**: The change MUST be refused, with a message saying why, when the chosen template belongs to another account, describes another kind of record, is turned off, or has no published version, and when a named version is not a published version of that template.
- **FR-011**: The API MUST offer the change, return the contact with its template, and keep the existing move between versions of one template working as it does today.
- **FR-011a**: The API MUST offer the preview as a separate read that changes nothing, answering with what the confirmation screen lists, and refusing the same templates as the change with the same wording.
- **FR-012**: The MCP server MUST offer two tools, each answering through its API counterpart with no rule of its own: one that previews the change, declared as only reading, and one that makes it, whose description tells the assistant to preview first and show the result to the person.
- **FR-013**: The Edit contact screen MUST carry a help bubble beside the template choice, linking to the help centre guide on changing a contact's template.
- **FR-014**: The help centre MUST hold a page dedicated to changing a contact's template, covering when and why, the steps, what happens to each kind of information, Unused answers, reminders, the version history, going back, and what cannot be done.
- **FR-015**: The existing help centre pages that mention the change MUST be corrected and link to the new page, and the new contact form's sentence "It can be changed on the contact afterwards" MUST be true.
- **FR-016**: The API documentation and the MCP tools documentation MUST describe the preview and the change, their parameters, their answers and their refusals, and the order in which to call them.
- **FR-017**: Every new string MUST be translated into every shipped locale, and the screen MUST meet WCAG 2.2 AA, including keyboard operation and announcements of the consequences and the result.
- **FR-018**: What's new MUST carry an entry for the change.

### Prepared for changing many contacts at once

The next feature moves many contacts, possibly thousands, to another template in one decision. This
feature does not build it, but what it builds MUST serve as its unit without being rewritten:

- **FR-019**: The work that decides where each answer goes MUST be split into a part that depends only on the pair of template versions (from and to), computed once for that pair, and a part that resolves one contact against it. Many contacts leaving the same version then share the first part.
- **FR-020**: The change of one contact MUST be callable without writing its own log entries, so that a change of many contacts can write a single entry for the whole decision. Called on its own, as in this feature, it writes the same entries as a version change does today.
- **FR-021**: The change of one contact MUST stay atomic on its own: a failure on one contact MUST NOT undo or block the change of another.
- **FR-022**: The preview MUST be expressible as a total per field across several contacts ("Desk number: 142 contacts"), from the same rule as FR-004a, without listing every contact.

### Key Entities

- **Contact**: a person in a vault. It uses exactly one version of one template, which decides which pages, sections and fields its page shows.
- **Template**: an account's description of what a contact page holds, with versions. Only an active template for contacts with a published version can be chosen.
- **Template version**: a frozen state of a template. A contact points at one and stays on it until somebody moves it.
- **Answer**: a value written in a field of a contact. It follows its field to a matching field or is set aside under Unused answers.
- **Version history entry**: the record of a contact's state before a move, from which the reader can revert.
- **Preview**: what a change to a given template would do to a given contact (answers set aside, answers coming back, reminders stopped and restarted), computed without changing anything.
- **Reminder**: may run on a date answer; stopped when that answer is set aside, restarted when it comes back.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A reader can change a contact's template from the contact page in under one minute, with at most four actions after opening Edit contact.
- **SC-002**: Changing a contact's template and then reverting it from the version history leaves every answer, and every reminder's state, as it was before, in 100% of cases.
- **SC-003**: No answer is deleted by a template change: the number of answers held by the contact, shown or set aside, is the same before and after.
- **SC-004**: What the confirmation lists as set aside is exactly what Unused answers lists after the change, in 100% of cases, including repeated blocks and fields shared between sections.
- **SC-008**: Previewing a change modifies nothing: the contact, its answers, its reminders, its version history and the account's log are identical before and after a preview.
- **SC-009**: Sending the same change twice leaves exactly one version history entry and one log entry for the move.
- **SC-005**: The interface, the API and the MCP server give the same result and the same refusal wording for the same change.
- **SC-006**: A reader who has read only the help centre page predicts correctly what happens to a given answer and a given reminder before making the change.
- **SC-007**: No help centre page, form sentence or API description still says that a contact's template cannot be changed.

## Assumptions

- The design `Edit Contact.dc.html` decides the layout of the template choice and its confirmation on the Edit contact screen (read on 2026-11-25, described in research R2). It shows no lists in the confirmation; FR-004 adds them to the same block.
- The wording of the consequences is the five lines agreed on 2026-11-25, as the design's "Read more" disclosure prints them.
- Answers are matched to fields as moving between versions already matches them: same section, key and type first, then the one field with the same key and type elsewhere. This feature does not change the matching.
- Any user who can reach the contact can change its template. An account carries no role, so no new permission is introduced.
- Records other than contacts (animals and other kinds of record built from templates) are out of scope. They may need the same change later.
- Moving many contacts at once is out of scope here and is the next feature. Until then the API covers it one request per contact. FR-019 to FR-022 only prepare for it: no queue, progress screen, bulk preview or bulk log entry is built in this feature.
- The preview is a separate read rather than an option on the change, so that the MCP preview tool can be declared as only reading.
- Previewing by running the real change and then undoing it was rejected: the change sends work to the queue (files, reminders, log entries) that undoing the database writes does not take back.
- There are no outbound webhooks in the product yet, so none is added for this change.
- The help centre page is written in English. Translating the help centre follows its own process, outside this feature.
