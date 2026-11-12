# Research: MCP Call History

## R1. Where the capture sits (`laravel/mcp` v1.0.1)

- **Decision**: `MonicaServer` maps `tools/call` to `App\Mcp\Servers\RecordedCallTool` through
  `$methods`. That class writes the row, runs the tool, classifies the outcome and closes the row.
- **Rationale**: `Server::handle()` routes every method through `$methods`, so this is the one place
  every tool call passes. `ToolInvoker::callHandler()` turns any exception into `Response::error()`
  text, which loses the kind of failure, so the class does what `CallTool` and `ToolInvoker` do
  (about twenty lines) and overrides `toErrorResponse()` to keep the exception before it becomes text.
- **Alternatives**: HTTP middleware (sees only status 200 and a JSON body, the brief rules it out);
  a wrapper in each of the 92 tools (92 copies); a new `app/Mcp/Methods` folder (needs an amendment).
- **Cost**: it copies package internals. An upgrade of `laravel/mcp` rereads `CallTool` and
  `ToolInvoker`, and a test calling a tool through `MonicaServer::tool()` fails if the copy drifts.

## R2. How an outcome becomes a result

| What happened | Result |
| --- | --- |
| Tool returned without an error response | succeeded |
| `doesNotExist()` in `MirrorsAnApiCapability` (record outside the account or vault) | refused |
| `AuthorizationException`, `AuthenticationException` | refused |
| Tool returned `Response::error()` itself (a business refusal, e.g. "first participant has to stay") | refused |
| Any other `ValidationException`; unknown tool or missing `name` (`JsonRpcException` -32602) | invalid parameters |
| Any other `Throwable` | error |
| Row written, nothing closed it | interrupted |

`doesNotExist()` keeps raising a `ValidationException`, because the package relays only that kind
word for word, but it marks it with the `address` key it already uses. The classifier reads that key,
so no exception class is added. The JSON-RPC error code is stored when there is one, and -32603 for
an error response, the code the package would send in a non-Errable path.

## R3. Operation

- **Decision**: the HTTP verb of the API route each tool mirrors (`$mirrors`): GET read, POST create,
  PUT update, DELETE delete. Unknown route or tool: no operation is guessed, the column is nullable.
- **Rationale**: every tool declares `$mirrors`. Checked on 2026-10-23 over 92 tools: 34 GET all
  `IsReadOnly`, 12 DELETE all `IsDestructive`, 45 PUT/POST none read only. Principle X already makes
  the API the source of truth. Rejected: guessing from the tool name.

## R4. Vault and object

`MirrorsAnApiCapability`'s lookups are where a record is proven to be in the account. `vault()` sets
the request attribute `mcp_vault`, and the record lookups set `mcp_subject`, the same way
`SetMcpLogSource` sets `log_source`. The last record found is the subject. A refused lookup sets
nothing, so a foreign record is never named. The label is snapshotted (contact display name, or the
record's `name`), the vault by foreign key with `nullOnDelete` plus its name.

## R5. Masking and size

- Keys named like a secret, at any depth (`password`, `token`, `secret`, `authorization`, `api_key`,
  `cookie`, case insensitive, as a substring): value replaced by `[masked]`. Headers are never read.
- `fields` of `create-life-event` and `update-life-event` only (in `replace-life-event-type-fields`
  it holds definitions): a value is masked unless its id is a non sensitive field of the account.
- Responses: no tool returns a sensitive value (`GetLifeEvent`, `ListLifeEvents` and `SearchRecords`
  build their resources with `withSensitive: false`). The key rule still runs on them.
- Encoded JSON over 16 384 bytes is cut at that length, on a character boundary, and flagged. The
  screen pretty prints valid JSON and shows the cut text as is.

## R6. Connection

`SetMcpLogSource::callerName()` already reads the name. It is extended to also set the kind
(`api_key`, `oauth_client`) and id (Sanctum token id, Passport client id) as request attributes, so
the history and the changelog name a connection the same way. The row keeps id and name; a revoked
key keeps its row in the history and drops out of nothing.

## R7. Retention

`McpCall` uses `MassPrunable`, past `monica.mcp_call_retention_in_days` (env
`MONICA_MCP_CALL_RETENTION_IN_DAYS`, default 90, at least 1). `model:prune` already runs daily at
01:05 in `routes/console.php`, so nothing is scheduled. Same shape as `Notification`.

## R8. Screen

Follows `AdministratorLogsViewModel`: filters in the query string, `PageSize::paginate()`, default 20.
Each row is a native `<details>`, which gives the expanded state and the keyboard route with no Alpine.
Components: `x-settings.page`, `x-page-header`, `x-select`, `x-badge`, `x-timestamp`,
`x-detail-list`, `x-empty-state`, `x-list-panel`, `x-pagination`. Day headings come from the user's
time zone, as `x-timestamp` does.
