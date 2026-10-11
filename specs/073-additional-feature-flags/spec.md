# Feature Specification: Additional feature flags

**Feature Branch**: `2026-10-10-additional-feature-flags` | **Created**: 2026-10-10 | **Status**: Clarified

**Input**: User description: "Add more feature flags"

## Clarifications

### Session 2026-10-10

- Q: Which capabilities get a flag? → A: SMS notifications, CardDAV, vCard export, the MCP server and photo upload, renamed file upload once it was widened to every file upload.
- Q: In which state do they ship, and for how long? → A: The four others ship off and are temporary. MCP ships on and is a permanent switch the administrator may turn off. This replaces two earlier answers ("only CardDAV and vCard ship off", "all five ship off").
- Q: Does the CardDAV flag also withdraw the read only calendar served at the same address? → A: Yes, the whole synchronisation address.
- Q: Does file upload cover the template and section imports? → A: No. They take pasted text, not a file; an earlier "yes" rested on the wrong premise that they took a file.
- Q: How is a form saving several fields refused when it carries a file? → A: The whole save is refused with an error on the file's field, and the form keeps what was typed. A required file field blocks saving and says why. This replaces an earlier "save the rest", which validation cannot do.
- Q: Does a device that already syncs receive not found or forbidden? → A: Not found, as `.claude/rules/flags.md` says, though a device may then treat its address book as deleted.
- Q: Is somebody told when a capability they used is withdrawn? → A: Yes, one sentence where it lived, only for an account holding data under it.

## User Scenarios & Testing *(mandatory)*

The mechanism, the administrator screen, the command and the log exist (specification 068). This
change declares five flags on top of them.

### User Story 1 - CardDAV and vCard export are withdrawn until switched on (Priority: P1)

**Why this priority**: a device that synchronises fails silently from the next request.

**Independent Test**: deploy with nothing stored, prove Jim's phone reaches neither his address book
nor his calendar and his contact page offers no vCard, then switch both on for Jim and prove they return.

1. **Given** the CardDAV flag off for Jim, **When** his device asks for his address book or his
   calendar, **Then** it is answered as not found and his contacts are unchanged.
2. **Given** the vCard export flag off for Jim, **When** he opens a contact or asks the API for its
   vCard, **Then** no download is offered and the direct request answers as not found.
3. **Given** both flags on for Jim and off for Dwight, **Then** Jim synchronises and downloads, Dwight does neither.

### User Story 2 - SMS and file upload are withdrawn until switched on (Priority: P2)

**Why this priority**: both live on screens, so a person notices them gone.

**Independent Test**: deploy with nothing stored, prove Pam gets no text message and cannot upload,
then switch each on for Pam and prove it returns for Pam only.

1. **Given** the SMS flag off for Pam, **When** a reminder is due, **Then** it reaches her other
   channels only, her SMS channel is kept, and her channels screen says text messages are unavailable.
2. **Given** the file upload flag off for Pam, **When** she edits a contact, a life event, an entity
   or her profile, **Then** no upload control is offered, a direct upload answers as not found, and
   her existing files are still shown and removable.

### User Story 3 - The administrator can withdraw the MCP server (Priority: P3)

**Why this priority**: nothing changes on deployment; the value is the ability to withdraw.

**Independent Test**: prove Pam's MCP client works, switch the flag off for Pam, prove it is refused
for Pam only, switch it back on and prove the same client works.

1. **Given** nothing stored, **When** Pam connects an MCP client, **Then** it works as before.
2. **Given** the flag off for Pam, **When** a client presents her token or asks her to authorise it,
   **Then** it answers as not found, and Dwight's client still works.

### User Story 4 - The public documentation follows the flags (Priority: P4)

**Why this priority**: a visitor told how to connect what cannot connect is misled, but loses nothing.

**Independent Test**: with CardDAV off for every account, prove its documentation answers as not
found to a visitor and nothing links to it; switch it on for every account and prove it returns.

### Edge Cases

- Jim's phone synchronised before the deployment. It now receives not found, may empty its local copy,
  and syncs every contact again once the flag is back on. Nothing is lost on the server.
- An SMS queued while the flag was on and sent after it went off is dropped, not kept for later. A
  reminder due while it is off is not sent by text message once it is back on.
- A form with a required file field cannot be completed while uploads are off. The template is not
  made optional (principle VI).
- An MCP token, an SMS channel or a file survives the flag going off and works again once it is on
  (FR-004 of specification 068).

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The system MUST declare five flags: SMS notifications, CardDAV, vCard export, MCP server and file upload.
- **FR-002**: On deployment, the MCP server flag MUST be on for every account and the four others off for every account.
- **FR-003**: With CardDAV off for an account, every request its devices make to the synchronisation address MUST answer as not found, address books and calendar alike.
- **FR-004**: With vCard export off, the system MUST offer no vCard download on any screen, and the download MUST answer as not found on the web and the API.
- **FR-005**: With SMS off, the system MUST offer SMS as a channel on no screen and no API endpoint, MUST send no notification by text message, and the account's SMS channel MUST be kept unchanged.
- **FR-006**: With MCP off, the MCP endpoint and the authorisation of a new MCP client MUST answer as not found for that account, and its tokens MUST be kept.
- **FR-007**: With file upload off, the system MUST offer no upload control on any screen, and every request that accepts a file MUST refuse it: the profile photo, the contact avatar, and every file, image and avatar field.
- **FR-008**: A request whose only purpose is to send a file MUST answer as not found. A request saving several fields MUST be refused with an error on the file's field, saving nothing and keeping what was typed; a required file field MUST block saving and say uploads are unavailable.
- **FR-009**: A photo inside a vCard received over CardDAV MUST be ignored while file upload is off, and the rest of the vCard accepted.
- **FR-010**: Files uploaded before file upload went off MUST still be shown, downloadable and removable.
- **FR-011**: The documentation of CardDAV, the MCP server and the vCard endpoint MUST be shown to a visitor only when its flag is on for every account, and answer as not found otherwise.
- **FR-012**: Each flag MUST give one answer for an account on every surface, appear on the Flags screen and in the flag command, and be switchable for one account or every account (FR-002 and FR-009 of specification 068).
- **FR-013**: The four flags shipping off are temporary: the change releasing a capability MUST delete its flag and stored decisions. The MCP flag is permanent and MUST NOT be deleted while the MCP server exists.
- **FR-014**: An account holding data under a withdrawn capability MUST see one sentence, where the capability lived, saying it is unavailable and the data is kept: an SMS channel set up (notification channels), MCP clients connected (connected assistants), files uploaded (the field or photo holding them). Other accounts see nothing.

### Key Entities

- **Flag**: one of the five switches, declared in the code, with its state on deployment (FR-002).
- **SMS channel**, **MCP token**, **File**: data kept while its flag is off (FR-005, FR-006, FR-010).

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: After deployment with nothing stored, zero accounts synchronise over CardDAV, download a vCard, receive a text message or upload a file, and every account's MCP client works as before.
- **SC-002**: With a flag off for an account, zero screens, API endpoints, MCP tools or synchronisation requests expose its capability to that account.
- **SC-003**: A switch takes effect on the account's next request and changes nothing for another account.
- **SC-004**: Switching a flag off and on again restores 100% of the channels, tokens, files and contacts it touched.

## Assumptions

- This change adds no screen of its own; switching goes through specification 068.
- The MCP flag departs from `.claude/rules/flags.md` (a flag dies with its release). The user chose a standing switch; the rule needs a paragraph admitting one, or the next reader deletes it.
- Shipping four flags off withdraws capabilities in use today (principles VI and VIII). It is the administrator's decision, deletes no data, and FR-014 tells whoever holds data under one. A device that stops synchronising is the one silent case.
- FR-014 departs from specification 068, where a control behind a flag that is off is absent rather than explained. The control stays absent; only the sentence is shown.
- vCard import and the whole address book export over CardDAV belong to the CardDAV flag, not vCard export.
- The SMS provider's instance settings stay outside every flag. No flag reads the plan, payment state or deployment type, though text messages and stored files cost the instance money.
