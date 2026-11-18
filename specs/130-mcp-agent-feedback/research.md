# Research: Agent feedback through MCP

## R1. Tool name

- **Decision**: `submit-feedback` (class `SubmitFeedback`).
- **Rationale**: every one of the catalogue's tools is kebab case, derived from its class. One snake case name would be the only exception an agent meets.
- **Alternatives**: `submit_feedback` as written in the request and the design, which needs a `name()` override. This is a one line change if the user insists.

## R2. The tool mirrors an API endpoint, and so does the admin side

- **Decision**: `POST api/v1/agent-feedback` for the agent. `admin/agent-feedback` (list, show, notes, status) for administrators, each with its MCP tool, following the support queue.
- **Rationale**: principle X, `.claude/rules/mcp.md`, and steps 8 to 10 of the definition of done. The support queue already exposes the same four admin operations.
- **Alternatives**: a tool holding its own logic is forbidden by the MCP rule. Leaving the admin side without an API breaks principle X.

## R3. What the system records on its own

- **Decision**: the account, the user, `mcp_connection_name` from the request attributes (the assistant or API key name already shown in the MCP history), and the time. Agents report the model themselves, optionally.
- **Rationale**: a `tools/call` request carries no `clientInfo`, and the HTTP transport is stateless, so there is no session to count calls in. The design's "Tool calls in session" is dropped.
- **Alternatives**: counting the connection's calls over a time window, which would be a number that means nothing precise.

## R4. Vault content (spec FR-005)

- **Decision**: the tool description tells the agent to paraphrase the request and to put placeholders in arguments instead of names, dates and values. The server instructions repeat this once. Nothing scrubs it on the server side.
- **Rationale**: the user chose this (clarification Q1). A server-side scrubber cannot recognise a contact name.
- **Note**: the design's line under "What the person asked" becomes "Paraphrased by the agent, without names or details from the vault."

## R5. Placeholder descriptions

- **Decision**: the API controller validation refuses `observed` when, lowercased and stripped to letters and digits, it is one of Sentry's list (`na`, `none`, `noop`, `null`, `placeholder`, `tbd`, `test`, `todo`, `undefined`).
- **Rationale**: it is input validation, so it is written inline in the controller (testing rule). It is used in one place, so it stays a closure.

## R6. Display number and identifier

- **Decision**: the URL uses the uuid, and the screen shows `#` followed by the primary key.
- **Rationale**: only administrators see reports, so a sequential number leaks nothing. Support's random `MON-` number exists because users quote it, which does not apply here.

## R7. Status history

- **Decision**: there is no history table. Each transition and each note is a row in the administrator log (`AgentFeedbackStatusChanged`, `AgentFeedbackNoteAdded`), as FR-008 asks.
- **Alternatives**: a `support_status_changes` style table, which adds nothing the log does not already hold.

## R8. Rate limit

- **Decision**: `throttle:10,1` on the API route, as changelog comments use. Over MCP, the `/mcp` route limit applies, since `answerThroughApi()` skips the route's middleware.

## R9. Copy as Markdown

- **Decision**: the view model builds the Markdown, and `x-copy-button` copies it.

## R10. Text is stored as sent, and escaped where it is shown

- **Decision**: `SubmitAgentFeedback` trims the text and does not pass it through `TextSanitizer`. Every screen prints it with `{{ }}`, and it is never rendered as Markdown. The person's account log gets one line naming the report.
- **Rationale**: `TextSanitizer::plainText` removes anything shaped like a tag, which would erase the `<contact>` placeholders the agent is told to use and break the proposed code.
