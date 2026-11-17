# Feature Specification: Clearer MCP tool descriptions and a redesigned MCP documentation page

**Feature Branch**: `2026-11-17-mcp-description-audit`

**Created**: 2026-11-17

**Status**: Draft

**Input**: User description: "make all the changes on mcp methods that you've identified in this audit. then for the mcp doc https://my.monicahq.com/docs/mcp, change the design completely and implement the Claude Design project `MCP Docs.dc.html` instead." Origin: monicahq/monica#7990.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - An assistant does not misread an empty answer (Priority: P1)

Somebody asks their assistant for "all reminders of my vault". The assistant learns from the tool description that dates are listed for a window (7 days unless told otherwise), says so, and offers to look further, or lists the reminders themselves through a tool made for that.

**Why this priority**: it is the reported bug; a wrong "you have no reminders" is a false answer about the user's own data.

**Independent Test**: connect an assistant, keep one birthday 30 days away, ask for every reminder of the vault.

**Acceptance Scenarios**:

1. **Given** a vault whose only reminder falls in 30 days, **When** the assistant lists the vault's upcoming dates with no window, **Then** the tool description says the window is 7 days and that an empty list does not mean no reminders exist.
2. **Given** the same vault, **When** the assistant asks for the vault's reminders, **Then** a tool lists every reminder of the vault, about contacts and groups, without any window.

---

### User Story 2 - Every term means one thing (Priority: P2)

An assistant reads the tool catalogue and never meets one word for two concepts, or two words for one concept.

**Why this priority**: the ambiguities (address, group, journal, activity) make an assistant pick the wrong tool or pass a URL where an id is wanted.

**Independent Test**: read every tool and parameter description; each term in the table below appears only in its chosen sense.

**Acceptance Scenarios**:

1. **Given** any tool taking a vault, contact, group, possession or life event, **When** its parameter is read, **Then** it is called the "id" of that record, as the `id` field of a listing gives it.
2. **Given** the life event and relationship category tools, **When** described, **Then** they say "category", never "group".
3. **Given** any write tool, **When** described, **Then** it names where the change is recorded with the same words as the tool that reads it.

---

### User Story 3 - Every parameter can be filled in (Priority: P2)

An assistant can always learn the accepted values of a parameter, and can always find the identifier a parameter asks for through another tool.

**Independent Test**: for every parameter taking a fixed set of values, the schema lists them; for every parameter taking an identifier, a tool returns it.

**Acceptance Scenarios**:

1. **Given** `update-contact-name`, `update-possession-type`, `update-possession-condition` and the two loan tools, **When** their schema is read, **Then** `name_order`, `icon` and `deposit_currency` list their accepted values.
2. **Given** `list-contacts` with categories, **When** the assistant needs a category id, **Then** a tool lists the vault's contact categories.
3. **Given** a template field offering choices, **When** the assistant needs a value list id, **Then** a tool lists the account's value lists, through an API endpoint that also exists.

---

### User Story 4 - A redesigned MCP documentation page (Priority: P3)

A visitor at `/docs/mcp` sees the page as designed in the Claude Design project "MCP Docs", built with the Monica design system, and finds there how to connect and what every tool does.

**Independent Test**: open `/docs/mcp` signed out and compare it with `MCP Docs.dc.html` at desktop and phone widths.

**Acceptance Scenarios**:

1. **Given** a visitor, **When** they open `/docs/mcp`, **Then** the layout, sections and components match the design.
2. **Given** a tool added to the server, **When** the page renders, **Then** it lists that tool with no hand edit of the page.
3. **Given** `/docs/mcp.md`, **When** fetched, **Then** it still holds the same content as plain Markdown.

### Edge Cases

- A tool parameter whose name differed from the API field takes the API name; an assistant sending the old name is refused with the endpoint's validation message.
- An update sending a field as null clears it; leaving the field out keeps it, through the API and through MCP alike.
- A tool on a flag that is off (groups) is described only where the flag is on, as today.
- The design shows content the server does not have (a tool, a count): the page shows the real catalogue, not the design's sample data.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: `list-vault-reminder-occurrences` MUST state in its tool description the default window and that an empty answer only covers that window.
- **FR-002**: A new tool MUST list every reminder of a vault (contacts and groups), mirroring a new API endpoint.
- **FR-003**: The `interval` parameter of the four reminder writing tools MUST say it counts cadence units, with an example.
- **FR-004**: Every identifier parameter MUST be called the record's "id" and say which listing gives it; "address" MUST only mean a postal or email address.
- **FR-005**: "Group" MUST only mean a group of contacts or a possession field group; categories MUST be called categories, and `search-records` MUST describe its `group` parameter as a result group.
- **FR-006**: Write tools MUST name where a change is recorded with the same words `list-logs` uses, and the server instructions MUST agree.
- **FR-007**: Group life event tools MUST say "life event", not "activity".
- **FR-008**: A tool parameter MUST carry the name of the API field it fills; API field names do not change (research R2).
- **FR-009**: Every parameter with a fixed set of values MUST declare them in its schema.
- **FR-010**: Every update, through the API and through MCP, MUST keep what is left out and clear what is sent as null; the API endpoints that replace today MUST change to this.
- **FR-010a**: Every MCP tool MUST answer through the API endpoint it mirrors, holding no validation, paging or update rule of its own, so the server instructions' promise holds.
- **FR-010b**: Every change of behaviour or wording MUST be reflected in the API reference and in the MCP documentation in the same change.
- **FR-011**: The wording flagged as unclear (`create-possession`, `delete-possession`, `list-possession-location-stays`, "told on its day") MUST be rewritten.
- **FR-012**: A tool MUST list a vault's contact categories, mirroring the existing endpoint.
- **FR-013**: A tool MUST list the account's value lists, mirroring a new API endpoint.
- **FR-014**: `/docs/mcp` MUST be rebuilt to the "MCP Docs" design: a getting started page, one page per tool family with every tool's description and parameters, search across tools, and a mark on tools that change data; the names, descriptions and parameters MUST be the ones the server gives assistants, never a second hand-written copy.
- **FR-015**: The page MUST meet WCAG 2.2 AA and keep the localisation it has today.

### Key Entities

- **Tool description**: the sentence an assistant reads to choose a tool, and the description of each parameter.
- **Vault reminder**: a reminder about a contact or a group of one vault, listed without a date window.
- **Value list**: a named list of choices a template field takes its values from.
## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Asked "list all reminders of my vault" with reminders only beyond 7 days, an assistant never answers that there are none.
- **SC-002**: 0 tool or parameter descriptions use "address" for an identifier, "group" for a category, or "journal"/"activity" for the account log.
- **SC-003**: 0 parameters with a fixed set of values lack a declared list, and 0 identifier parameters lack a tool returning the identifier.
- **SC-004**: The redesigned page matches the design at desktop and phone widths, and lists 100% of the tools the server offers.

## Assumptions

- The update rule changes in place in API v1, without a new version: a client sending the whole record is unaffected, and one that cleared a field by leaving it out now sends null. `.claude/rules/mcp.md` changes with it.
- Renaming a parameter needs no alias: assistants read the schema at every connection. Scripted clients calling MCP by hand are not supported.
- "id" is the term for identifiers because it is the field name every listing returns.
- The design was read on 2026-11-17 (project 670b2827, `MCP Docs.dc.html`); its 20 tool families and their order are kept, and `mcp-data.js` is sample data only.
- The 19 nested parameter descriptions not covered by the audit are reviewed under the same rules during implementation.
