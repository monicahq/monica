# Feature Specification: MCP tools for templates, sections and fields

**Feature Branch**: `2026-09-25-mcp-stuff` | **Created**: 2026-09-25 | **Status**: Draft

**Input**: "add missing mcp methods to manage templates, sections and fields in the user account.
add proper doc in /docs/mcp"

## Context

The MCP server lets an assistant read templates (`list-templates`, `list-template-versions`) and
change nothing about them. The JSON API can already create, change, order, duplicate, activate and
delete a template, open and publish a draft version, and add, change, order and remove the sections
and fields of that draft. The constitution (principle X) says the MCP server exposes the API and
never bypasses it, so every tool below mirrors an API endpoint, and the one thing the web does that
the API cannot (creating a brand new section inside a template) is added to the API first.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Shape a template's sections and fields with an assistant (Priority: P1)

Somebody asks their assistant "add a Hobbies section to my Person template, with a text field for
the hobby and a date field for when they started". The assistant finds the template, opens a draft,
adds the section and its fields, and publishes the draft when asked to.

**Why this priority**: sections and fields are what a template is made of; without them the other
tools only move empty shells around.

**Independent Test**: through MCP alone, open a draft of an existing template, add a new section with
two fields, reorder the fields, publish, and see the published version carry them.

**Acceptance Scenarios**:

1. **Given** a template, **When** the assistant asks for one of its versions, **Then** it receives the
   version's status, its pages, and every section with its fields, each with the identifier the other
   tools take.
2. **Given** a template whose latest version is published, **When** the assistant opens a draft,
   **Then** a new draft version exists and is what the section and field tools act on.
3. **Given** a draft, **When** the assistant adds a new section by name (optionally with an icon, a
   colour and a page), **Then** the section exists in the account and sits in the draft.
4. **Given** a draft, **When** the assistant adds a section the account already has (from another
   template), **Then** that section sits in the draft, and adding it twice is refused.
5. **Given** a section in a draft, **When** the assistant changes its displayed title or whether it
   starts collapsed, moves it, or removes it, **Then** the draft reflects it.
6. **Given** a section in a draft, **When** the assistant adds a field of any type the API accepts
   (including a list, a relation, a file, an image and a repeater with fields inside it), changes its
   label or settings, moves it, or deletes it, **Then** the draft reflects it, and a repeater nested
   in a repeater is refused with the API's wording.
7. **Given** a draft, **When** the assistant publishes it, **Then** it becomes the published version,
   and **When** it discards it instead, **Then** the draft is gone and the published version stands.
8. **Given** a published version, **When** the assistant tries to change a section or field in it,
   **Then** it is refused with the API's wording, and nothing changes.

### User Story 2 - Manage templates themselves (Priority: P2)

Somebody asks their assistant to create a template for a kind of record, rename it, duplicate it as a
starting point, reorder the list, switch one off, or delete one.

**Why this priority**: needed to start from nothing, but less frequent than editing what exists.

**Independent Test**: through MCP alone, create a template, duplicate it, rename the copy, deactivate
it, move it first, delete it.

**Acceptance Scenarios**:

1. **Given** the account, **When** the assistant creates a template for contacts or for an entity
   type, **Then** it exists with a first draft, and an entity template without an entity type is
   refused.
2. **Given** a template, **When** the assistant reads it, renames it, changes its description,
   duplicates it, activates or deactivates it, or moves it to a position, **Then** the account
   reflects it exactly as the API would.
3. **Given** a template, **When** the assistant deletes it, **Then** the API's refusals apply
   unchanged (for example a template still describing records), and the tool is marked to clients as
   destructive so they ask before calling it.

### User Story 3 - Know what the assistant can now change (Priority: P3)

A visitor to `/docs/mcp` reads which tools exist, which of them change data or destroy it, and how a
template is edited through a draft that has to be published.

**Why this priority**: a key hands an assistant the whole account; the page is where somebody decides
whether to allow that.

**Independent Test**: compare the page's tools table with the server's registered tools, and read the
page's explanation of drafts against what the tools do.

**Acceptance Scenarios**:

1. **Given** "Available tools", **Then** every new tool is listed by its exact name with one sentence,
   grouped (templates, template versions, sections, fields), the groups that change data are badged
   so, and the tools that delete are named as deleting.
2. **Given** the page, **Then** a short section explains that sections and fields are changed on a
   draft, that nothing reaches records until the draft is published, and walks through one request
   ("add a section with two fields") as the sequence of tools an assistant calls.
3. **Given** `/docs/mcp.md`, **Then** it carries the same tools and the same explanation.
4. **Given** the tool count in the prose, **Then** it matches the table and the server.
5. **Given** the API reference, **Then** the new endpoint for creating a section is documented there.

### Edge Cases

- A template, version, section or field of another account answers not found, never forbidden.
- A section identifier reached through the wrong template or version answers not found.
- A field id given as a parent that is not a repeater, or belongs to another section, is refused.
- A value list or entity type of another account is refused by validation.
- Opening a draft when one already exists answers as the API does rather than creating a second.
- Changing a field of a section shared by several templates follows the API's rule (the field change
  applies to the section's editable version); the tool invents no rule of its own.
- Every change is written to the account's journal as arriving from an assistant.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The server MUST offer tools mirroring each of these API capabilities, one tool each:
  - Templates: read one, create, update, delete, set activation, set position, duplicate.
  - Template versions: read one (with pages, sections and fields), open a draft, publish, discard.
  - Sections in a version: add (new or existing), update, remove, set position.
  - Fields in a section: create, update, delete, set position.
- **FR-002**: The JSON API's add section endpoint MUST also create a new section directly inside a
  draft version, from a name and optional icon, colour and page, matching what the web builder
  offers, and MUST refuse a version that is not a draft before writing anything. The MCP tool for
  adding a section mirrors that one endpoint in both forms.
- **FR-003**: Each tool MUST validate, refuse and answer exactly as its API endpoint does, return the
  same resource shape, and name the endpoint it mirrors.
- **FR-004**: Every tool MUST be confined to the key's account; a record outside it MUST be reported
  as not existing, in a translated sentence.
- **FR-005**: Tools that only read MUST be marked read only; tools that delete (template, draft
  version, section from a version, field) MUST be marked destructive to the client.
- **FR-006**: Every change MUST be recorded in the account journal with the assistant as its source,
  as the existing changing tools are.
- **FR-007**: `/docs/mcp` and `/docs/mcp.md` MUST list every registered tool, and the suite MUST fail
  when the page and the server disagree (the existing check, extended to the new tools).
- **FR-008**: `/docs/mcp` MUST explain the draft and publish sequence for templates, with one worked
  example, in both the page and its Markdown.
- **FR-009**: The API reference MUST document the new section creation endpoint.
- **FR-010**: A change to a section or field of a published version MUST be refused by the API as a
  validation error carrying the frozen version message, not a server error, and by the tool with the
  same message.
- **FR-011**: The server's instructions MUST tell the assistant that template contents change only on
  a draft and reach records only once published.

### Key Entities

- **Template**: what the account shapes records with; has versions, one published.
- **Template version**: a draft (editable) or published/retired (frozen); holds pages and sections.
- **Section**: an account level block of fields, placed in a version; may be shared by templates.
- **Field**: one question inside a section, of a type; a repeater holds fields of its own.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Everything the web template builder can do to templates, sections and fields (outside
  pages, import, export and sharing) can be done through MCP.
- **SC-002**: An assistant can turn "add a section with two fields and publish it" into tool calls
  without any identifier the tools themselves did not hand it.
- **SC-003**: Every tool name on `/docs/mcp` matches the server; the suite fails otherwise.
- **SC-004**: For each new tool, the same input sent to the tool and to its API endpoint produces the
  same record and the same refusal.

## Assumptions

- Pages (creating, renaming, ordering, deleting them, and moving a section between pages) are out of
  scope; a section can still be placed on an existing page when added. A follow up can add them.
- Import, export and public sharing of templates and sections stay out of scope.
- Changing a section's icon or colour after creation is out of scope: the API has no endpoint for it
  today and the request did not ask for one.
- Deleting is exposed because "manage" includes it and the API allows it; clients are told the tool is
  destructive so they confirm with the person.
- The branch stays `2026-09-25-mcp-stuff`, the one checked out for this work.
- Tool copy is English, like the existing tools; refusals reuse the API's translated sentences.
