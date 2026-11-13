# Research: Relatives without a record and possessions through a connected assistant

## R1. Why the assistant said the capabilities do not exist

**Finding**: both capabilities are registered (`app/Mcp/Servers/MonicaServer.php`) and tested
(`tests/Unit/Mcp/Tools/CreateContactRelationshipTest.php`, `CreatePossessionTest.php`). The action
`CreateRelationship` already writes the other side down as a partial contact unless asked otherwise,
named or not (`tests/Unit/Actions/CreateRelationshipTest.php`, `it_writes_two_separate_people_down_when_neither_name_is_known`).

Three causes are visible, none confirmed against the account's MCP history (`mcp_calls` records
`tools/call` only, never `tools/list`, so the history cannot show what the client was listed):

| Cause | Evidence |
| --- | --- |
| Catalogue paged at 15 | `laravel/mcp` `Server::$defaultPaginationLength = 15`, 222 tools, so 15 pages. `create-contact-relationship` is 166th, `create-possession` around 70th. A client that stops after one page sees neither. |
| Guidance silent | The server instructions describe possession types but not recording a possession, and call a relative "somebody not written down yet". |
| Deployment lag | `create-possession` reached main on 2026-11-12 (b9344638). |

Whether ChatGPT follows `nextCursor` is not documented anywhere I could check offline. Unverified.

## R2. Return the whole catalogue in one page

**Decision**: override `$defaultPaginationLength` and `$maxPaginationLength` on `MonicaServer`, both
set to 500.

**Rationale**: the package reads both from the server and caps the requested page size at the
maximum, so one page holds everything and no cursor is announced. Two property overrides, no new
method. 500 leaves room for the catalogue to more than double; a test asserting the full catalogue on
one page fails the day it is outgrown, which is the signal to raise it.

**Size**: the 222 tool definitions serialise to 183,266 bytes (measured with each tool's `toArray()`).
Large, but a single HTTP response well within what MCP clients accept.

**Alternatives rejected**:
- Fewer, broader tools: a redesign of the whole server for an unconfirmed cause.
- A `per_page` hint left to the client: the client that does not page does not send it either.
- Setting the length to `count($this->tools)` at boot: a computed value for no gain over a constant.

## R3. Guidance wording

**Decision**: rewrite the relationship paragraph of the server instructions and add one paragraph for
possessions; change the first sentence of `create-contact-relationship` and `create-possession`.
Exact wording in [contracts/mcp-guidance.md](contracts/mcp-guidance.md).

**Rationale**: an assistant matches the person's words ("wife", "child", "without a record",
"object") against descriptions. The current text answers the question only for a reader who already
knows Monica's vocabulary.

**Alternative rejected**: a dedicated `add-relative` tool. It would duplicate
`create-contact-relationship` and add a 223rd entry to a catalogue that is already the problem.

## R4. Gender of a relative without a record

**Decision**: out of scope. Gender is a template field (`ValueList`), not a contact column, and a
partial contact shows no template. "Milo, a boy" is expressed through the relationship type the
account offers. Recorded in the spec's assumptions.

## R5. Test placement

| Rule | Layer | Test |
| --- | --- | --- |
| Whole catalogue on one page | MCP server | `tests/Feature/Mcp/McpCatalogueTest.php`, new test |
| Tool keeps the other person without a record by default | Tool | `CreateContactRelationshipTest`, new test |
| Partial contact named or unnamed | Action | Already covered, no change |
| Possession recorded | Tool | Already covered, no change |

The guidance wording gets no test: asserting prose is asserting copy nobody reads in a test, and the
acceptance is the replay in [quickstart.md](quickstart.md).
