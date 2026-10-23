# Research: Contact relationships over the API and MCP

## R1. Which layer is missing

- Decision: add five API endpoints and five MCP tools; reuse `CreateRelationship`, `UpdateRelationship`
  and `DestroyRelationship` unchanged.
- Rationale: the actions already hold every rule (self link, cross vault, switched off type, ancestry
  loop, dates out of order). Principle X forbids a tool without an endpoint behind it.
- Rejected: an MCP tool calling the actions with no API endpoint (breaks principle X).

## R2. Model to copy

- Decision: copy `Api\ContactReminderController` and the `*ContactReminder` tools: `string` route
  parameters, the vault and the contact found by the controller's own query, `{relationship}` matched
  as a UUID, a paginated list with `meta`.
- Rationale: reminders are the one existing capability addressed under a contact on both surfaces.

## R3. Reaching a relationship

- Decision: `Relationship::query()->forContact($contact)->where('uuid', ...)`. The existing scope
  matches account, vault and either side, so a relationship read through a contact that is neither
  side answers 404 / "That relationship does not exist.".
- Rejected: a typed `Relationship` route binding (API controllers here take strings and query).

## R4. The other side on creation

- Decision: `other_contact` (slug or id, looked up inside the URL's vault) **or** `first_name` and
  `last_name`, with `creates_record`; `prohibits` between them; neither means an unnamed placeholder.
- Rationale: maps the web form's `existing`, `known` and `unknown` modes without a `mode` field an
  assistant could contradict.
- Consequence: a contact of another vault answers "That contact does not exist.", as the web form
  answers 404. `RelationshipWouldCrossVaults` stays unreachable from both surfaces.

## R5. Which side holds the first role

- Decision: one boolean, `holds_role_a`, on create and update: whether the contact in the URL holds
  `role_a`. Ignored for a type reading the same both ways, as the actions ignore it.
- On create it maps to `swapped: ! holds_role_a`, default true. On update it defaults to the current
  orientation seen from the URL's contact.
- Rejected: the web form's `"{typeId}-{a|b}"` string (an HTML select artifact, unreadable in JSON).

## R6. Update keeps what is left out

- Decision: every update field is optional. Absent keeps the value; `null` clears a date. Setting
  `status` to `current` clears `ended_on`, as the action does.
- Rationale: an assistant ending a relationship must not wipe its start date by omission. The template
  tools already work this way, and the MCP docs say so.
- Rejected: full replacement like `PUT reminders` (silent data loss for a partial call).

## R7. Naming the other side on update

- Decision: `first_name`, `last_name`, `creates_record` accepted only when the other side is a
  placeholder; otherwise 422 on `first_name`, "Only somebody without a record of their own can be named
  here.". The controller checks `is_partial` before calling the action.
- Rationale: the action throws `ModelNotFoundException` for that case, which over the API reads as an
  unrelated 404.

## R8. Refusal mapping

| Exception | Field (API 422) |
| --- | --- |
| `CannotLinkSomebodyToThemselves` | `other_contact` |
| `RelationshipTypeIsNotAvailable`, `RelationshipWouldLoopParentage` | `relationship_type` |
| `RelationshipDatesAreOutOfOrder` | `ended_on` |

MCP returns the same message through `Response::error()`, as `CreateContactReminder` does.

## R9. Journal origin

- Finding: MCP requests log as `mcp` (`SetMcpLogSource`). Nothing sets `api`, so API requests log as
  `web`, the default in `LogUserAction`. This feature inherits that and does not fix it; the gap
  concerns every API endpoint and belongs to its own change.

## R10. Link to the web page

- Finding: branch `2026-10-23-web-links-in-api-resources` (unmerged) adds links to the interface in
  several resources. `RelationshipResource` carries `links.self` now. Whichever branch merges second
  adds the web link to the other's resources.
