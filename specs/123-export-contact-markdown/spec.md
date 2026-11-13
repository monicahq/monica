# Feature Specification: Export a contact as Markdown

**Created**: 2026-11-13 | **Status**: Draft

**Input**: User description: "je veux une option sur la fiche d'un contact qui permet d'exporter le contact dans un fichier md. l'idee est de prendre l'ensemble de ces données, et de les mettre dans un fichier md avec qu'on puise facilement l'exporter dans un llm si on veut. avec ca, on a besoin aussi d'une methode mcp, api, et de toutes les docs pour refleter ce besoin."

## Clarifications

### Session 2026-11-13

- Q: Do sensitive values go into the document? → A: as in 039. The interface file always holds them; the API and MCP follow the key's sensitive fields scope.
- Q: Download only, or also copy? → A: both. The screen offers to download the file and to copy the same text to the clipboard.

Precedent: `039-export-contact-vcard`. Where this spec says nothing, that spec's decisions hold (journal entry, no share link access, file produced on request and never stored).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Download one contact as a Markdown file (Priority: P1)

Somebody reading a contact wants everything the product knows about that person in one plain text document they can hand to an assistant of their choice, to ask questions about the person or prepare a meeting.

**Why this priority**: It is the request, and the only slice that delivers value on its own.

**Independent Test**: Open a contact holding fields, life events, relationships and reminders, export it, and confirm the file reads top to bottom as a document holding each of them.

**Acceptance Scenarios**:

1. **Given** a contact with a name, field values, life events, relationships, groups, tags, reminders and loans, **When** the reader asks for the Markdown export, **Then** a `.md` file downloads, named after the contact, holding one titled part per kind of data.
2. **Given** that file, **When** it is read without the product, **Then** every value carries its label, every date shows the precision it is known to, and nothing refers to an internal identifier the reader cannot interpret.
3. **Given** a contact reached through the wrong vault, or through another account, **When** the export is requested, **Then** the answer is not found and no file is produced.
4. **Given** the contact screen, **When** the reader asks to copy the Markdown, **Then** the same text is on the clipboard and the screen announces that it got there, or that it did not.

### User Story 2 - Fetch the same document through the API and MCP (Priority: P2)

A script holding an API key, or an assistant connected over MCP, wants the same document without driving a browser, so the assistant can read a contact in one call instead of a dozen.

**Why this priority**: The constitution requires API coverage, and MCP is the most natural consumer of a document written for an assistant. The interface alone is already useful.

**Independent Test**: Call the API endpoint and the MCP tool for one contact and compare both with the file the interface downloads.

**Acceptance Scenarios**:

1. **Given** an API key with the sensitive fields scope, **When** it requests the Markdown of a contact in its account, **Then** the response body is the document itself, identical to what the interface downloads.
2. **Given** an API key without that scope, **When** it requests the same contact, **Then** the document comes back with sensitive values left out.
3. **Given** an MCP client, **When** it calls the export tool for a contact, **Then** it receives the same document as text, under the same scope rules.
4. **Given** a key or client of another account, **When** it requests that contact, **Then** the answer is not found.

### User Story 3 - Find the capability in the documentation (Priority: P3)

Somebody building on the API, connecting an assistant, or looking for help learns that the export exists and what it contains.

**Why this priority**: The capability works without its documentation, but nobody outside the interface would find it.

**Independent Test**: Open the API reference, the MCP page, the API changelog and the help centre, and find the export described in each.

**Acceptance Scenarios**:

1. **Given** the API reference, **When** a developer reads the contact endpoints, **Then** the export endpoint is described with its response type and scope behaviour.
2. **Given** the MCP page, **When** a reader lists the tools, **Then** the export tool is named and described.
3. **Given** the API changelog and the help centre, **When** a reader looks for the export, **Then** each holds an entry for it.

### Edge Cases

- A contact holding only a name: the document holds the name and no empty part.
- A value written by the user that contains Markdown syntax: it stays readable and cannot break the structure of the document.
- A field value that is a file: the document names the file and does not embed its content.
- A deactivated field value: it is left out, as the contact screen leaves it out.
- A related contact the reader cannot reach any more: it is named as the relationship shows it on screen and never followed.
- A name a file system refuses (empty, very long, reserved characters): the download still gets a usable name.
- The Me contact: it exports like any other contact.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: A reader MUST be able to export a contact as Markdown in one interaction from the actions of the contact screen.
- **FR-002**: The document MUST hold every kind of data the contact screen shows for that contact: structured name and its other representations, field values grouped as the structure groups them, life events, relationships, groups, tags, reminders and possession loans.
- **FR-003**: The document MUST be written in the reader's language for its headings and labels, and MUST leave user content in the language it was written in.
- **FR-004**: The document MUST present approximate and partial dates with the precision they are known to, and MUST NOT invent one.
- **FR-005**: The interface document MUST hold the values of fields marked sensitive, with no choice offered and no credential asked, as in 039.
- **FR-006**: The export MUST answer not found for a contact in another account or reached through the wrong vault.
- **FR-007**: The same document MUST be available through the API as the raw response body, with the Markdown content type and a download file name, and MUST honour the sensitive fields scope of the key.
- **FR-008**: The same document MUST be available through an MCP tool, going through the same rules as the API.
- **FR-009**: For one contact, one reader and one moment, the interface file, the clipboard copy, the API and the MCP tool MUST produce the same text.
- **FR-010**: Every export MUST be recorded in the account journal, naming the contact and the surface used.
- **FR-011**: A share link MUST NOT offer the export, and the shared contact page MUST stay unchanged.
- **FR-012**: The API reference, the API changelog, the MCP documentation page and the help centre MUST describe the export.
- **FR-013**: Every string the feature shows MUST be translatable and present in every configured locale.
- **FR-014**: The controls MUST be reachable with the keyboard alone and announced by a screen reader.
- **FR-015**: The contact screen MUST also offer to copy the document to the clipboard, and MUST announce whether the copy succeeded.

### Key Entities *(include if feature involves data)*

- **Contact**: the person exported, with everything the contact screen shows for them.
- **Markdown document**: plain text produced on request, never stored, the same on every surface.
- **Field value**: one value of the contact, which its field may mark sensitive.
- **API key scope**: what decides whether a program reaches sensitive values.
- **Journal entry**: the record that the export happened.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A reader reaches the document, as a file or on the clipboard, in one interaction from the contact screen.
- **SC-002**: Every kind of data shown on the contact screen appears in the document of a contact that holds it.
- **SC-003**: A contact holding 100 field values, 50 life events and 50 relationships exports in under 2 seconds.
- **SC-004**: For one contact and one reach, the interface file, the API body and the MCP text are identical.
- **SC-005**: A document fetched with a key lacking the sensitive fields scope holds no sensitive value.
- **SC-006**: Every export appears in the account journal.
- **SC-007**: The document renders without broken structure in a standard Markdown viewer for contacts whose values contain Markdown syntax.

## Assumptions

- One contact at a time. Exporting a vault, a group or a selection is out of scope.
- The product sends the document nowhere. Passing it to an assistant is the reader's act, consistent with the rule that no artificial intelligence is built into the product.
- The vCard export and its flag stay as they are; the Markdown export is a second action beside it.
- The changelog of the contact (its modification history) and technical data stay out of the document.
- Whether the capability ships behind a flag is a planning decision, governed by `.claude/rules/flags.md`.
